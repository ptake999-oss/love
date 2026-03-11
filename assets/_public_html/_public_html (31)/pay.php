<?php
header("Content-Type: application/json");

// ------------------------------
// PERFORMANCE FIXES
// ------------------------------
ini_set("max_execution_time", 60);
ini_set("memory_limit", "512M");

// ------------------------------
// YOUR BASE URL
// ------------------------------
$base_url = "https://rvcreationstore.in/";  

$payMode = "production";

// ------------------------------
// CASHFREE API CREDENTIALS
// ------------------------------
if ($payMode == 'production') {
    define('client_id', "");
    define('secret_key', "");
    $APIURL = "https://api.cashfree.com/pg/orders";
} else {
    define('client_id', "Your_Test_Client_ID");
    define('secret_key', "Your_Test_Secret_Key");
    $APIURL = "https://sandbox.cashfree.com/pg/orders";
}

// ------------------------------
// GET AMOUNT FROM FRONTEND
// ------------------------------
$data = json_decode(file_get_contents("php://input"), true);
$orderAmount = isset($data['amount']) ? number_format((float)$data['amount'], 2, '.', '') : "199.00";

// ------------------------------
// YOUR ADVANCED ORDER ID LOGIC
// ------------------------------
function generateOrderId($prefix = '') {
    // Faster hashing to reduce server load
    $input = $prefix . microtime(true) . rand(100000, 999999);
    return strtoupper(substr(hash('sha256', $input), 0, 20));
}

$orderId = generateOrderId('ORD_');

// ------------------------------
// RANDOM CUSTOMER LOGIC (YOUR LOGIC)
// ------------------------------
$firstNames = ["Amit", "Priya", "Rahul", "Sneha", "Karan", "Meera", "Ravi", "Anita"];
$lastNames  = ["Sharma", "Patel", "Singh", "Khan", "Mehta", "Gupta", "Iyer", "Das"];

$customer_id = 'CUST' . rand(1000, 9999);
$customer_name = $firstNames[array_rand($firstNames)] . " " . $lastNames[array_rand($lastNames)];

$email_username = strtolower(str_replace(' ', '', $customer_name));
$customer_email = $email_username . rand(10,99) . "@gmail.com";

$customer_phone = '9' . str_pad(rand(0,999999999), 9, '0', STR_PAD_LEFT);

// ------------------------------
// PAYLOAD (YOUR LOGIC)
// ------------------------------
$payload = json_encode([
    "order_id"   => $orderId,
    "order_amount" => $orderAmount,
    "order_currency" => "INR",
    "customer_details" => [
        "customer_id"    => $customer_id,
        "customer_name"  => $customer_name,
        "customer_email" => $customer_email,
        "customer_phone" => $customer_phone
    ],
    "order_meta" => [
        "return_url" => $base_url."/success.php?order_id=".$orderId,
        "notify_url" => $base_url."/callback.php",
        "payment_methods" => "cc,dc,upi"
    ]
]);

// ------------------------------
// cURL (Optimized & 502 Protection)
// ------------------------------
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $APIURL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,

    // ⚠️ MOST IMPORTANT — prevents 502 bad gateway
    CURLOPT_TIMEOUT => 25,
    CURLOPT_CONNECTTIMEOUT => 10,

    CURLOPT_HTTPHEADER => [
        'X-Client-Secret: '.secret_key,
        'X-Client-Id: '.client_id,
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-version: 2023-08-01'
    ],
]);

$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);

// ------------------------------
// ERROR HANDLING
// ------------------------------
if ($error) {
    echo json_encode([
        "error" => "Request Timeout",
        "details" => $error
    ]);
    exit;
}

$resData = json_decode($response);

if (isset($resData->payment_session_id)) {
    echo json_encode([
        "paymentSessionId" => $resData->payment_session_id,
        "mode" => $payMode
    ]);
} else {
    echo $response; // Cashfree error
}
?>
