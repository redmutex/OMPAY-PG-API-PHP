
<?php
/**
 * Checkout Payment Example
 *
 * Demonstrates how to create a checkout payment order and redirect using OMPAY PHP LIBRARY.
 */
require_once 'ompay.php';

$checkoutRedirectUrl = 'http://localhost:8888/checkout_redirect.php?orderId='; // Change as needed

$ompay = new OMPAY();

$orderData = new orderDataCheckout();
$orderData->amount = 0.221;
$orderData->currency = 'OMR';
$orderData->receiptId = 'INV_' . mt_rand(10000000000000, 99999999999999);
$orderData->description = 'Test Order Description from PHP Driver';
$orderData->customerFields = new customerFields();
$orderData->customerFields->name = $ompay->arabicToEnglish('عمر الزدجالي');
$orderData->customerFields->email = 'john@doe.com';
$orderData->customerFields->phone = '91234567';

// Unique transaction reference
$orderData->curn = 'TRX_' . mt_rand(10000000000000, 99999999999999);

$order = $ompay->CreateOrder($orderData);

if (($order['resCode'] ?? 0) != 200) {
    echo 'Error creating order: ' . htmlspecialchars($order['status'] ?? '') . ' : ' . htmlspecialchars($order['errMessage'] ?? '');
    exit;
}

echo 'Order Id: ' . htmlspecialchars($order['orderId']) . '<br />';
// Redirect link for customer
$redirectLink = $ompay->GetCustomerRedirectionLink($order['orderId'], $checkoutRedirectUrl . $order['orderId']);
echo '<a href="' . htmlspecialchars($redirectLink) . '">Click to redirect to OMPAY checkout page.</a><br />';
