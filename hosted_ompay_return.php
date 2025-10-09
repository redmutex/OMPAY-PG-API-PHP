<?php
include_once 'ompay.php';

$paymentId = $_SESSION['paymentId'];

$driver = new OMPAY();

$paymentStatus = $driver->CheckStatus($paymentId);

$status = $paymentStatus['data']['status'];
echo "PAYMENT STATUS: " . $status . "<br />";

if (isset($paymentStatus['data']['securedCardDetails'])) {
    echo "SECURED CARD DETAILS ARE AVAILABLE. <br />";

    $customerId = $paymentStatus['data']['securedCardDetails']['customerId'];
    $digitalCardId = $paymentStatus['data']['securedCardDetails']['digitalCardId'];

    echo "CUSTOMER ID: " . $customerId . "<br />";
    echo "DIGITAL CARD ID: " . $digitalCardId . "<br />";

    //Store these values in session or database for later use.
    //Customer ID can be used to fetch all the tokenized cards of the customer.
    $_SESSION['customerId'] = $customerId;
    //You should store the digitalCardId for tokenized transactions.
    $_SESSION['digitalCardId'] = $digitalCardId;
    echo "<a href='hosted_tokens.php'>Perform Tokenized Transaction</a><br />";
} else {
    echo "SECURED CARD DETAILS ARE NOT AVAILABLE. <br />";
}

/*
//OPTIONAL: If you want to refund the transaction, you can use the RefundTransaction method.
*/
$refundAmount = $paymentStatus['data']['amount']; //Refund full amount. You can also specify a partial amount.
$refund = $driver->RefundTransaction($paymentId, $refundAmount);
echo "REFUND RESPONSE: <br />";
print_r($refund);
