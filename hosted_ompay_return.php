
<?php
/**
 * Hosted OMPAY Return Example
 *
 * Handles the return from OMPAY after payment and displays/refunds status.
 */
require_once 'ompay.php';

$paymentId = $_SESSION['paymentId'] ?? '';
if (!$paymentId) {
    echo 'No paymentId found in session.';
    exit;
}

$ompay = new OMPAY();
$paymentStatus = $ompay->CheckStatus($paymentId);
$status = $paymentStatus['data']['status'] ?? '';
echo 'PAYMENT STATUS: ' . htmlspecialchars($status) . '<br />';

if (isset($paymentStatus['data']['securedCardDetails'])) {
    echo 'SECURED CARD DETAILS ARE AVAILABLE.<br />';
    $customerId = $paymentStatus['data']['securedCardDetails']['customerId'] ?? '';
    $digitalCardId = $paymentStatus['data']['securedCardDetails']['digitalCardId'] ?? '';
    echo 'CUSTOMER ID: ' . htmlspecialchars($customerId) . '<br />';
    echo 'DIGITAL CARD ID: ' . htmlspecialchars($digitalCardId) . '<br />';
    // Store for later use
    $_SESSION['customerId'] = $customerId;
    $_SESSION['digitalCardId'] = $digitalCardId;
    echo '<a href="hosted_tokens.php">Perform Tokenized Transaction</a><br />';
} else {
    echo 'STATUS RESPONSE:<br />';
    echo '<pre>';
    print_r($paymentStatus);
    echo '</pre>';
    echo 'SECURED CARD DETAILS ARE NOT AVAILABLE.<br />';
}

// OPTIONAL: Refund transaction
$refundAmount = $paymentStatus['data']['amount'] ?? 0;
if ($refundAmount) {
    $refund = $ompay->RefundTransaction($paymentId, $refundAmount);
    echo 'REFUND RESPONSE:<br />';
    echo '<pre>';
    print_r($refund);
    echo '</pre>';
}
