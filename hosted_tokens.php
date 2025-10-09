<?php

include_once 'ompay.php';

$driver = new OMPAY();

//PERFORMING A TOKENIZED TRANSACTION USING SAVED CARD

$order = new orderDataHosted();
$order->receiptId = "INV_002";
$order->amount = 6.442;
$order->description = "Test Order USING TOKEN from PHP Driver";
$order->customerFields = new customerFields();
$order->customerFields->name = "John";
$order->customerFields->email = "john@doe.com";
$order->customerFields->phone = "91234567";
$orderId = $driver->CreateOrder($order)['data']['orderId'];

echo "ORDER ID: " . $orderId . "<br />";

$cardData = new cardDataWithToken();
$cardData->digitalCardId = $_SESSION['digitalCardId'];
$cardData->cardCVV = "123";

$encryptedCard = $driver->EncryptCard($cardData);

$payment = $driver->PerformHostedTransaction($orderId, $encryptedCard, true);

echo "PAYMENT ID: " . $payment['data']['paymentId'] . "<br />";
$OTPPage = $payment['data']['redirectionData']['url'];
echo "<a href='" . $OTPPage . "'>Click to redirect to OTP page. </a><br />";
//If customer needs to be redirected to OTP page, redirect them to $OTPPage URL.

?>
<h3>List of customer saved cards</h3>
<table border="1">
    <tr>
        <th>digitalCardId</th>
        <th>network</th>
        <th>cardType</th>
        <th>status</th>
        <th>Masked Card</th>
        <th>Created At</th>
        <th>Modified At</th>
        <th>Action</th>
    </tr>

    <?php
    $listOfCards = $driver->GetListOfCards($_SESSION['customerId']);
    foreach ($listOfCards['data']['digitalCards'] as $card) {
        echo "<tr>";
        echo "<td>" . $card['digitalCardId'] . "</td>";
        echo "<td>" . $card['network'] . "</td>";
        echo "<td>" . $card['cardType'] . "</td>";
        echo "<td>" . $card['status'] . "</td>";
        echo "<td>**** **** **** " . $card['panLastFour'] . "</td>";
        echo "<td>" . $card['createdAt'] . "</td>";
        echo "<td>" . $card['updatedAt'] . "</td>";
        echo "<td><a href='hosted_delete_token.php?token_id=" . $card['digitalCardId'] . "'>Delete</a></td>";
        echo "</tr>";
    }
    ?>


</table>

