<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Retrieve the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Check if data was received and is valid
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received or invalid JSON."]);
    exit;
}

// Debug received data (optional, can be removed in production)
error_log('Received Data: ' . print_r($data, true));

// Extract user and order details from the received data
$fullName = $data['fullName'];
$email = $data['email'];
$phone = $data['phone'];
$address = $data['address'];
$pincode = $data['pincode'];
$flatno = $data['flatno'];
$paymentMethod = $data['paymentMethod'];
$cartItems = $data['cartItems'];

// Check if cartItems is empty
if (empty($cartItems)) {
    echo "<script>console.log('No cart items received');</script>";
}

// Prepare the email content
$sellerEmail = "sampleUser@gmail.com"; // Replace with the seller's email address
$subject = "New Order Received";
$message = "New order received from $fullName.\n\n";
$message .= "Contact Details:\n";
$message .= "Name: $fullName\n";
$message .= "Email: $email\n";
$message .= "Phone: $phone\n";
$message .= "Address: $flatno, $address, $pincode\n";
$message .= "Payment Method: $paymentMethod\n\n";
$message .= "Order Details:\n";

$totalPrice = 0;

foreach ($cartItems as $index => $item) {
    // Clean the price to remove non-numeric characters like '₹'
    $price = isset($item['price']) ? floatval(preg_replace('/[^\d.]/', '', $item['price'])) : 0;
    $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;

    // Validate price and quantity
    if (!is_numeric($price) || !is_numeric($quantity)) {
        error_log("Invalid data for item $index: " . print_r($item, true));
        continue; // Skip this item if invalid
    }

    // Calculate item total
    $itemTotal = $price * $quantity;
    $totalPrice += $itemTotal;

    // Append to the email message
    $message .= "Item: " . ($item['name'] ?? 'Unknown') . "\n";
    $message .= "Description: " . ($item['description'] ?? 'N/A') . "\n";
    $message .= "Quantity: " . $quantity . "\n";
    $message .= "Price: " . number_format($itemTotal, 2) . " Rs \n\n";
}

// Add delivery charge
$deliveryCharge = 50;
$totalPrice += $deliveryCharge;

$message .= "Delivery Charge: " . number_format($deliveryCharge, 2) . " Rs \n";
$message .= "Total Price: " . number_format($totalPrice, 2) . " Rs \n";

// Send email using SendGrid API// Replace with your actual SendGrid API key

$sendgridApiKey = "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";

$sendgridUrl = "https://api.sendgrid.com/v3/mail/send";

// Prepare the email payload
$emailData = [
    "personalizations" => [
        [
            "to" => [["email" => "aditya.baheti@btech.christuniversity.in"]], // Seller's email
            "subject" => $subject,
        ]
    ],
    "from" => ["email" => "adityabaheti186@gmail.com"], // From email
    "content" => [
        [
            "type" => "text/plain",
            "value" => $message,
        ]
    ]
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sendgridUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $sendgridApiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));

// Send the email request
$response = curl_exec($ch);
curl_close($ch);

// Check if email was sent successfully
if ($response === false) {
    // If email sending failed, return a failure response
    $response = [
        'success' => false,
        'message' => 'Order placed, but failed to send email notification.'
    ];
} else {
    // If the email was sent successfully, return success response
    $response = [
        'success' => true,
        'message' => 'Order placed and email sent successfully!'
    ];
}

// Return JSON response
echo json_encode($response);
?>