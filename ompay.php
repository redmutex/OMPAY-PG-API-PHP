<?php
@session_start();
/*
 *
 *  Library Name: OMPay
 *  Description: Payment gateway implementation of OMPAY. 
 *  @package OMPAY
 *  @author Danial Jawaid
 *  @version 1.00
*/

/*
 *  CONFIGURATION
 */
//ENUMERATION
enum OMPAY_ENVIRONMENT
{
    case TEST;
    case PRODUCTION;
}

// Client Configuration     (CHANGE THIS WITH YOUR APPLICABLE VALUES)
/* 
 *  - To use OMPAY merchant hosted payment mode, uncomment the below clientId and clientSecret values.
 *  - You can find these values in your OMPAY merchant dashboard.
 *  - Make sure to use the correct values for test and production modes.
 */
define("OMPAY_CLIENT_ID", "");
define("OMPAY_CLIENT_SECRET", "");
define("OMPAY_CARD_ENCRYPTION_KEY", "");

///////////////////////////////////////////////////////////////////////////

define("OMPAY_CARD_MERCHANT_DOMAIN", "https://ompay.com");
define("OMPAY_ENVIORNMENT_MODE", OMPAY_ENVIRONMENT::TEST);
define("OMPAY_DEBUG_MODE", true); //Set to true to enable debug mode

//If using checkout, change it to the URL where the customer will be redirected after completing the transaction. 
define("OMPAY_HOSTED_RETURN_URL", "http://localhost:8888/hosted_ompay_return.php");

// OMPAY Configuration      (DO NOT CHANGE)
define("OMPAY_CURRENCY", "OMR");
define("OMPAY_HOSTED_BASE_URL", (OMPAY_ENVIORNMENT_MODE == OMPAY_ENVIRONMENT::TEST) ? "https://api.uat.gateway.ompay.com/nac/api/v1/merchant-host" : "https://api.gateway.ompay.com/nac/api/v1/merchant-host");
define("OMPAY_CHECKOUT_BASE_URL", (OMPAY_ENVIORNMENT_MODE == OMPAY_ENVIRONMENT::TEST) ? "https://api.uat.gateway.ompay.com" : "https://api.gateway.ompay.com");
define("OMPAY_CHECKOUT_URL", (OMPAY_ENVIORNMENT_MODE == OMPAY_ENVIRONMENT::TEST) ? "https://merchant.uat.gateway.ompay.com/cpbs/pg?actionType=checkout&orderId={0}&&redirectUrl={1}&clientId={2}" : "https://merchant.gateway.ompay.com/cpbs/pg?actionType=checkout&orderId={0}&&redirectUrl={1}&clientId={2}");
define("OMPAY_ENDPOINT_ORDER", "/order");
define("OMPAY_ENDPOINT_CHECKOUT_ORDER", "/nac/api/v1/pg/orders/create-checkout");
define("OMPAY_ENDPOINT_CHECKOUT_STATUS", "/nac/api/v1/pg/orders/check-status?orderId=%s"); //"%s" will be replaced with orderId
define("OMPAY_ENDPOINT_TRANSACTION_INITIATE", "/transaction/initiate");
define("OMPAY_ENDPOINT_TRANSACTION_STATUS", "/transaction/status/");
define("OMPAY_ENDPOINT_TRANSACTION_REFUND", "/transaction/refund/");
define("OMPAY_ENDPOINT_CUSTOMER_TOKENS_LIST", "/customer/%s/digitalCards"); //"%s" will be replaced with customerId
define("OMPAY_ENDPOINT_DELETE_TOKEN", "/customer/%s/digitalCards/%s"); //First %s will be replaced with customerId and second %s with digitalCardId

/*
 *  IMPLEMENTATION
 */
class OMPAY
{

    public function __construct()
    {
        if (OMPAY_DEBUG_MODE) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
    }

    public function CreateOrder($orderData, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        if ($orderData instanceof orderDataHosted) {
            return $this->createHostedOrder($orderData, $clientIPAddress, $clientBrowserFingerprint);
        } elseif ($orderData instanceof orderDataCheckout) {
            return $this->createCheckoutOrder($orderData, $clientIPAddress, $clientBrowserFingerprint);
        } else {
            throw new Exception("Invalid order data type.");
        }
    }

    private function createHostedOrder(orderDataHosted $orderDataHosted, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;
        $payLoad = trim(json_encode($orderDataHosted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $signature = $this->generateSignature(OMPAY_CLIENT_SECRET, OMPAY_ENDPOINT_ORDER, $payLoad);
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-Signature: " . $signature,
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendPostRequest(OMPAY_HOSTED_BASE_URL . OMPAY_ENDPOINT_ORDER, $headers, $payLoad);
        return $response;
    }

    private function createCheckoutOrder(orderDataCheckout $orderData, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;
        $payLoad = trim(json_encode($orderData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendPostRequest(OMPAY_CHECKOUT_BASE_URL . OMPAY_ENDPOINT_CHECKOUT_ORDER, $headers, $payLoad);
        return $response;
    }

    public function GetCustomerRedirectionLink($orderId, $redirectUrl)
    {
        return str_replace(["{0}", "{1}", "{2}"], [$orderId, urlencode($redirectUrl), OMPAY_CLIENT_ID], OMPAY_CHECKOUT_URL);
    }

    public function PerformHostedTransaction($orderId, $encryptedCardData, $secureCard = false, $cvvFlag = false, $paymentMode = "card", $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;

        $payLoad = json_encode([
            "orderId" => $orderId,
            "encryptedCardDetails" => $encryptedCardData,
            "paymentMethod" => "card",
            "cardHolderName" => "ABCD XYZ",
            "redirectionUrl" => OMPAY_HOSTED_RETURN_URL,
            "apiType" => "hosted",
            "paymentMode" => $paymentMode,
            "secureCard" => $secureCard,
            "cvvFlag" => $cvvFlag,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->generateSignature(OMPAY_CLIENT_SECRET, OMPAY_ENDPOINT_TRANSACTION_INITIATE, $payLoad);
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-Signature: " . $signature,
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendPostRequest(OMPAY_HOSTED_BASE_URL . OMPAY_ENDPOINT_TRANSACTION_INITIATE, $headers, $payLoad);
        return $response;
    }

    public function CheckCheckoutStatus($orderId)
    {
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json"
        ];
        $response = $this->sendGetRequest(sprintf(OMPAY_CHECKOUT_BASE_URL . OMPAY_ENDPOINT_CHECKOUT_STATUS, $orderId), $headers);
        return $response;
    }

    public function CheckStatus($paymentId, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;
        $signature = $this->generateSignature(OMPAY_CLIENT_SECRET, OMPAY_ENDPOINT_TRANSACTION_STATUS . $paymentId);
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-Signature: " . $signature,
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendGetRequest(OMPAY_HOSTED_BASE_URL . OMPAY_ENDPOINT_TRANSACTION_STATUS . $paymentId, $headers);
        return $response;
    }

    public function RefundTransaction($paymentId, $amount, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;
        $payLoad = json_encode([
            "paymentId" => $paymentId,
            "amount" => $amount
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->generateSignature(OMPAY_CLIENT_SECRET, "/transaction/refund", $payLoad);
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-Signature: " . $signature,
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendPostRequest(OMPAY_HOSTED_BASE_URL . "/transaction/refund", $headers, $payLoad);
        return $response;
    }

    public function GetListOfCards($customerId, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;
        $signature = $this->generateSignature(OMPAY_CLIENT_SECRET, sprintf(OMPAY_ENDPOINT_CUSTOMER_TOKENS_LIST, $customerId));
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-Signature: " . $signature,
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendGetRequest(OMPAY_HOSTED_BASE_URL . sprintf(OMPAY_ENDPOINT_CUSTOMER_TOKENS_LIST, $customerId), $headers);
        return $response;
    }

    public function DeleteDigitalCardId($customerId, $digitalCardId, $clientIPAddress = "", $clientBrowserFingerprint = "")
    {
        $clientIPAddress = ($clientIPAddress == "") ? $_SERVER['REMOTE_ADDR'] : $clientIPAddress;
        $clientBrowserFingerprint = ($clientBrowserFingerprint == "") ? $this->getBrowserFingerprint() : $clientBrowserFingerprint;
        $signature = $this->generateSignature(OMPAY_CLIENT_SECRET, sprintf(OMPAY_ENDPOINT_DELETE_TOKEN, $customerId, $digitalCardId));
        $headers = [
            "Authorization: Basic " . base64_encode(OMPAY_CLIENT_ID . ":" . OMPAY_CLIENT_SECRET),
            "Content-Type: application/json",
            "X-Signature: " . $signature,
            "X-MERCHANT-BROWSER-FINGERPRINT: " . $clientBrowserFingerprint,
            "X-MERCHANT-USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'],
            "X-MERCHANT-DOMAIN: " . OMPAY_CARD_MERCHANT_DOMAIN,
            "X-MERCHANT-IP: " . $clientIPAddress
        ];
        $response = $this->sendDeleteRequest(OMPAY_HOSTED_BASE_URL . sprintf(OMPAY_ENDPOINT_DELETE_TOKEN, $customerId, $digitalCardId), $headers);
        return $response;
    }

    private function generateSignature($clientSecret, $endpoint, $payLoad = "")
    {
        return hash_hmac("sha256", $endpoint . $payLoad, $clientSecret);
    }

    function encryptCard($data)
    {
        $iv = bin2hex(random_bytes(16));
        $key = hex2bin(OMPAY_CARD_ENCRYPTION_KEY);
        $iv_bin = hex2bin($iv);
        $encrypted = openssl_encrypt(
            json_encode($data),
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv_bin
        );
        $encryptedHex = bin2hex($encrypted);
        return $iv . '.' . $encryptedHex;
    }

    private function getBrowserFingerprint()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        // Detect OS
        if (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'Mac';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
        }

        // Detect Browser
        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Combine and hash for fingerprint
        $fingerprint = hash('sha256', $os . $browser . $userAgent . $_SERVER['REMOTE_ADDR']);
        return $fingerprint;
    }

    private function sendPostRequest($url, $headers, $data)
    {
        if (OMPAY_DEBUG_MODE) {
            echo "URL: " . $url . "\n";
            echo "HEADERS: " . json_encode($headers) . "\n";
            echo "DATA: " . $data . "\n";
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Request Error: ' . curl_error($ch));
        }
        curl_close($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200) {
            throw new Exception('Error: ' . $info['http_code'] . ' Response: ' . $response);
        }
        if (OMPAY_DEBUG_MODE) {
            echo "RESPONSE: " . json_encode($response, true) . "\n";
        }
        return json_decode($response, true);
    }

    private function sendGetRequest($url, $headers)
    {
        if (OMPAY_DEBUG_MODE) {
            echo "URL: " . $url . "\n";
            echo "HEADERS: " . json_encode($headers) . "\n";
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if (curl_errno($ch)) {
            throw new Exception('Request Error: ' . curl_error($ch));
        }
        curl_close($ch);
        if ($info['http_code'] != 200) {
            throw new Exception('Error: ' . $info['http_code'] . ' Response: ' . $response);
        }
        return json_decode($response, true);
    }

    private function sendDeleteRequest($url, $headers)
    {
        if (OMPAY_DEBUG_MODE) {
            echo "URL: " . $url . "\n";
            echo "HEADERS: " . json_encode($headers) . "\n";
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if (curl_errno($ch)) {
            throw new Exception('Request Error: ' . curl_error($ch));
        }
        curl_close($ch);
        if ($info['http_code'] != 200) {
            throw new Exception('Error: ' . $info['http_code'] . ' Response: ' . $response);
        }
        return json_decode($response, true);
    }
}

class orderDataHosted
{
    public $receiptId;
    public $amount;
    public $currency;
    public $description;
    public customerFields $customerFields;
    public $uiMode;

    public function __construct()
    {
        $this->uiMode = "hosted";
        $this->currency = OMPAY_CURRENCY;
    }
}

class customerFields
{
    public $name;
    public $email;
    public $phone;
}

class orderDataCheckout extends orderDataHosted
{
    public $redirectType;
    public $curn;

    public function __construct()
    {
        parent::__construct();
        $this->uiMode = "checkout";
        $this->redirectType = "redirect";
    }
}

class cardData
{
    public $cardNumber;
    public $cardExpMonth;
    public $cardExpYear;
    public $cardCVV;
}

class cardDataWithTokenWithoutCVV
{
    public $digitalCardId;
}

class cardDataWithToken extends cardDataWithTokenWithoutCVV
{
    public $cardCVV;
}
