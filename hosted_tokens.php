
<?php
/**
 * Hosted Tokenized Transaction Example
 *
 * Demonstrates how to perform a tokenized transaction and list saved cards using OMPAY PHP LIBRARY.
 */
session_start();
require_once 'ompay.php';

$ompay = new OMPAY();

// Create order for tokenized transaction
$order = new orderDataHosted();
$order->receiptId = 'INV_' . microtime();
$order->amount = 1.442;
$order->description = 'Test Order USING TOKEN from PHP Driver';
$order->customerFields = new customerFields();
$order->customerFields->name = 'John';
$order->customerFields->email = 'john@doe.com';
$order->customerFields->phone = '91234567';

$orderResponse = $ompay->CreateOrder($order);
$orderId = $orderResponse['data']['orderId'] ?? null;

echo 'ORDER ID: ' . htmlspecialchars($orderId) . '<br />';


// Prepare card data - WITHOUT CVV EXAMPLE
$cardData = new cardDataWithTokenWithoutCVV();
$cardData->digitalCardId = $_SESSION['digitalCardId'] ?? '';
$cvvFlag = true;

// Prepare card data - WITH CVV EXAMPLE
//$cardData = new cardDataWithToken();
//$cardData->digitalCardId = $_SESSION['digitalCardId'] ?? '';
//$cardData->cardCVV = '123';
//$cvvFlag = false;

echo "\nCARD DATA: ";
print_r(json_encode($cardData));

$encryptedCard = $ompay->EncryptCard($cardData);

$payment = $ompay->PerformHostedTransaction($orderId, $encryptedCard, false, $cvvFlag, "token");

echo "\nRESPONSE: ";
print_r(json_encode($payment));

echo 'PAYMENT ID: ' . htmlspecialchars($payment['data']['paymentId'] ?? '') . '<br />';
$otpPage = $payment['data']['redirectionData']['url'] ?? '';
if ($otpPage) {
    echo '<a href="' . htmlspecialchars($otpPage) . '">Click to redirect to OTP page.</a><br />';
}

// List of customer saved cards
echo '<h3>List of customer saved cards</h3>';
echo '<table border="1">';
echo '<tr>';
echo '<th>digitalCardId</th><th>network</th><th>cardType</th><th>status</th><th>Masked Card</th><th>Created At</th><th>Modified At</th><th>Action</th>';
echo '</tr>';

$listOfCards = $ompay->GetListOfCards($_SESSION['customerId'] ?? '');
foreach (($listOfCards['data']['digitalCards'] ?? []) as $card) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($card['digitalCardId']) . '</td>';
    echo '<td>' . htmlspecialchars($card['network']) . '</td>';
    echo '<td>' . htmlspecialchars($card['cardType']) . '</td>';
    echo '<td>' . htmlspecialchars($card['status']) . '</td>';
    echo '<td>**** **** **** ' . htmlspecialchars($card['panLastFour']) . '</td>';
    echo '<td>' . htmlspecialchars($card['createdAt']) . '</td>';
    echo '<td>' . htmlspecialchars($card['updatedAt']) . '</td>';
    echo '<td><a href="hosted_delete_token.php?token_id=' . urlencode($card['digitalCardId']) . '">Delete</a></td>';
    echo '</tr>';
}
echo '</table>';
