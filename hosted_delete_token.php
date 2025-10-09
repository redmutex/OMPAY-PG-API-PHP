<?php

include_once 'ompay.php';

$digitalCardId = stripslashes($_GET['token_id']);

$driver = new OMPAY();
$response = $driver->DeleteDigitalCardId($_SESSION['customerId'], $digitalCardId);

print_r($response);
