<?php
include_once 'ompay.php';

$checkout_redirect_url = "http://localhost:8888/checkout_redirect.php?orderId=%s"; //Change this to your desired redirect URL after checkout.

$driver = new OMPAY();

$orderData = new orderDataCheckout();
$orderData->amount = 11.221;
$orderData->currency = "OMR";
$orderData->receiptId = "INV_001";
$orderData->description = "Test Order Description from PHP Driver";
$orderData->customerFields = new customerFields();
$orderData->customerFields->name = "John";
$orderData->customerFields->email = "john@doe.com";
//THE PHONE NUMBER OF THE CUSTOMER WILL IDENTIFY THE CUSTOMER IN OMPAY SYSTEM.
//IF THE CUSTOMER WITH THE SAME PHONE NUMBER ALREADY EXISTS, ANY SAVED CARDS ON THE CHECKOUT PAGE WILL BE SHOWN TO THE CUSTOMER.
$orderData->customerFields->phone = "91234567";

//THIS MUST BE UNIQUE FOR EACH TRANSACTION.
//ONLY FOR THE SAKE OF DEMO WE ARE USING TIME() FUNCTION.
//IN PRODUCTION, USE YOUR OWN LOGIC TO GENERATE UNIQUE TRANSACTION REFERENCE NUMBER.
$orderData->curn = "TRX_" . microtime(); 

$order = $driver->CreateOrder($orderData);

if ($order['resCode'] != 200) {
    echo "Error creating order: " . $order['status'] . " : " . $order['errMessage'];
    exit;
} else {
    echo "Order Id: " . $order['orderId'] . "<br />";
    //At this point you can redirect the customer to OMPAY checkout page using the URL below.
    echo "<a href='" . $driver->GetCustomerRedirectionLink($order['orderId'], sprintf($checkout_redirect_url,$order['orderId'])) . "'>Click to redirect to OMPAY checkout page. </a><br />";
}
