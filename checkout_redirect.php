<?php

include_once 'ompay.php';

if (isset($_GET['orderId']) == false) {
    echo "No orderId found in the request.";
} else {
    $driver = new OMPAY();
    $orderId = stripslashes($_GET['orderId']);
    $status = $driver->CheckCheckoutStatus($orderId);

    if ($status['resCode'] != 200) {
        echo "Error fetching order status: " . $status['status'] . " : " . $status['errMessage'];
        exit;
    } else {
        print_r($status);
        //You can now update your order status in your database based on the response received.
        //Refer to OMPAY API documentation for understanding the response fields.
    }
}

//RECOMMENDATION:
//It is recommended to implement webhook listener to get real-time updates about the transaction status.

//RECOMMENDATION:
//To be on the safer side, check all the pending transactions in your database every few hours and update their status by calling CheckCheckoutStatus() method.
//This is to avoid any discrepancies in case webhooks are not delivered due to any reason.
//You can implement a cron job to do this task.