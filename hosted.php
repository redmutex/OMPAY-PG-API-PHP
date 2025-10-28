
<?php
/**
 * Hosted Payment Example
 *
 * Demonstrates how to create a hosted payment order and perform a transaction using OMPAY PHP LIBRARY.
 */
session_start();
require_once 'ompay.php';

// Initialize OMPAY driver
$ompay = new OMPAY();

// Create order data
$order = new orderDataHosted();
$order->receiptId = 'INV_' . microtime();
$order->amount = 11.221;
$order->description = 'Test Order Description from PHP Driver';
$order->customerFields = new customerFields();
$order->customerFields->name = 'John';
$order->customerFields->email = 'john@doe.com';
$order->customerFields->phone = '12333211';

$orderResponse = $ompay->CreateOrder($order);
$orderId = $orderResponse['data']['orderId'] ?? null;

echo 'ORDER ID: ' . htmlspecialchars($orderId) . '<br />';

// Prepare card data
$cardData = new cardData();
$cardData->cardNumber = '4012001037490006';
$cardData->cardExpMonth = '12';
$cardData->cardExpYear = '25';
$cardData->cardCVV = '123';

$encryptedCard = $ompay->EncryptCard($cardData);

$payment = $ompay->PerformHostedTransaction($orderId, $encryptedCard, true, false, "card");

echo '<pre>';
print_r($payment);
echo '</pre>';

if (isset($payment['data']['redirectionData']['url'])) {
    $otpPage = $payment['data']['redirectionData']['url'];
    echo 'PAYMENT ID: ' . htmlspecialchars($payment['data']['paymentId']) . '<br />';
    echo '<a href="' . htmlspecialchars($otpPage) . '">Click to redirect to OTP page.</a><br />';
}

// Store paymentId in session for later use
if (isset($payment['data']['paymentId'])) {
    $_SESSION['paymentId'] = $payment['data']['paymentId'];
}
