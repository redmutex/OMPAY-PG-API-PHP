
<?php
/**
 * Checkout Redirect Handler
 *
 * Handles the redirect after checkout and displays order status.
 */
session_start();
require_once 'ompay.php';

$paymentId = isset($_GET['paymentId']) ? stripslashes($_GET['paymentId']) : '';
if (!$paymentId) {
    echo 'No paymentId found in the request.';
    exit;
}

$ompay = new OMPAY();
$status = $ompay->CheckStatus($paymentId);

if (($status['resCode'] ?? 0) != 200) {
    echo 'Error fetching order status: ' . htmlspecialchars($status['status'] ?? '') . ' : ' . htmlspecialchars($status['errMessage'] ?? '');
    exit;
}

echo '<pre>';
print_r($status);
echo '</pre>';
// You can now update your order status in your database based on the response received.
// Refer to OMPAY API documentation for understanding the response fields.

// RECOMMENDATION:
// Implement webhook listener to get real-time updates about the transaction status.
// To be on the safer side, check all the pending transactions in your database every few hours and update their status by calling CheckCheckoutStatus() method.
// This is to avoid any discrepancies in case webhooks are not delivered due to any reason.
// You can implement a cron job to do this task.