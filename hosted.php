<?php
include_once 'ompay.php';

$driver = new OMPAY();

$order = new orderDataHosted();
$order->receiptId = "INV_001";
$order->amount = 11.221;
$order->description = "Test Order Description from PHP Driver";
$order->customerFields = new customerFields();
$order->customerFields->name = "John";
$order->customerFields->email = "john@doe.com";
$order->customerFields->phone = "91234567";
$orderId = $driver->CreateOrder($order)['data']['orderId'];

echo "ORDER ID: " . $orderId . "<br />";

$cardData = new cardData();
$cardData->cardNumber = "4012001037490006";
$cardData->cardExpMonth = "12";
$cardData->cardExpYear = "25";
$cardData->cardCVV = "123";

$encryptedCard = $driver->EncryptCard($cardData);

$payment = $driver->PerformHostedTransaction($orderId, $encryptedCard, false);

print_r($payment);

if (isset($payment['data']['redirectionData']['url'])) {
    $OTPPage = $payment['data']['redirectionData']['url'];
    //If customer needs to be redirected to OTP page, redirect them to $OTPPage URL.

    echo "PAYMENT ID: " . $payment['data']['paymentId'] . "<br />";
    echo "<a href='" . $OTPPage . "'>Click to redirect to OTP page. </a><br />";
}
//At this point the customer should be redirected to the OTP page URL.
//After completing the OTP, the customer will be redirected to the return URL specified in OMPAY_RETURN_URL constant in ompay.php file.
//You can store the paymentId in session or database for later use.
$_SESSION['paymentId'] = $payment['data']['paymentId'];
