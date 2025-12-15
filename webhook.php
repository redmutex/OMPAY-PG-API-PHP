<?php

include_once 'ompay.php';


$rawData = file_get_contents("php://input");
//HARDCODED FOR TESTING PURPOSES
$rawData = "{\"eventType\":null,\"data\":{\"orderId\":\"wb-c5ee147a-e53d-4f3a-a082-326c959e023c\",\"paymentId\":\"pay31ca32314d9a4a77a2273dfcacad7f50\",\"receiptId\":\"INV_0.67620700 1765798341\",\"signature\":\"e861cd6fb46b88eba57c831ef600931d54ed317398cc774ed87992b0ad653220\",\"status\":\"success\",\"amount\":\"0.221\",\"failureReason\":\" \",\"redirectType\":\"redirect\",\"redirectUrl\":\"http://localhost:8888/checkout_redirect.php\",\"timestamp\":\"2025-12-15T11:32:58.885Z\",\"paymentDetails\":{\"cardNetwork\":\"visa\",\"cardType\":\"debit\"}}}";

$decodedData = json_decode($rawData, true);

$driver = new OMPAY();
$message = $decodedData['data']['orderId'] . "|" . $decodedData['data']['paymentId'];
$response = $driver->VerifyWebhookSignature($message, $decodedData['data']['signature']);

if ($response) {
    // Process the webhook event
    // For example, update order status in your database
    http_response_code(200); // Acknowledge receipt of the webhook
    echo "Webhook processed successfully. <br />";
    //The order and payment IDs are verified.
    //However, the actual status of the transaction should be checked via API call to ensure accuracy.

    //FOR MERCHANT HOSTED CHECKOUT
    $orderStatus = $driver->CheckCheckoutStatus($decodedData['data']['orderId']);
    // ******************** ******************** ******************** ******************** //
    // ONLY CONSIDER THE STATUS OF TRANSACTION FROM THE CHECK STATUS API CALLL.
    // ******************** ******************** ******************** ********************//
    if ($orderStatus['data']['status'] === 'success') {
        echo "Transaction successful";
    } else {
        echo "Transaction failed";
    }
} else {
    // Invalid signature
    http_response_code(400); // Bad Request
    echo "Invalid webhook signature.";
}
