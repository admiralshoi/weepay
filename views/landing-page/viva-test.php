<?php



use classes\Methods;

$myMerchantId = 'ee6d19b2-8b9e-41ed-874e-044680beeae7';


$specialResellerId = "7e416dfc-aa89-4335-9be5-4b3ce2dc359b";
$specialResellerApiKey = "M3GMZ71Jyp4G1Ka2J6k4hMdH699ZUX";


/*  ------------------------------------------------------- AUTH START ------------------------------------------------------- */

/**
 * oAuth 2.0
 */
$authUrl = "https://demo-accounts.vivapayments.com/connect/token";
$authBody = ['grant_type' => 'client_credentials'];
$scopes = ["urn:viva:payments:core:api:isv", "urn:viva:payments:core:api:redirectcheckout"];
$vivaClientId ='067jg2zsb0g51m923ez87ic03n8isrqughtqpjldn5bu6.apps.vivapayments.com';
$vivaClientSecret = '7nUK8kkFW442A3q9Od4r9FmdP6t31K';

$requests =  Methods::requests();
$requests->basicAuth($vivaClientId, $vivaClientSecret);
$requests->setHeaderContentTypeFormEncoded();
$requests->setBody($authBody);
$requests->post($authUrl);

$responseHeaders = $requests->getHeaders();
$responseBody = $requests->getResponse();

//prettyPrint($responseHeaders);
prettyPrint($responseBody);


$accessToken = $responseBody['access_token'];





/*  ------------------------------------------------------- AUTH END ------------------------------------------------------- */

/**
 * Normal api
 */
$apiBaseUrlDemo = "https://demo-api.vivapayments.com/";
$apiBaseUrlLive = "https://api.vivapayments.com/";
$av = "isv/";
$version = "v1/";
$baseApiUrl = $apiBaseUrlDemo . $av . $version;



/*  ------------------------------------------------------- ACCOUNTS START ------------------------------------------------------- */

/**
 * Merchant creation --------------------------------------
 */
$endpoint = 'accounts';
$accountCreationBody = [
    'email' => 'universeofcoding@gmail.com',
    'returnUrl' => __url('testing/return-url'),
    'branding' => [
        'partnerName' => 'Content Stage',
        'primaryColor' => '#28bfbf',
        'logoUrl' => 'https://app.contentstage.de/public/media/images/logo-icon.png',

    ],
];


//$requests =  Methods::requests();
//$requests->setBearerToken($accessToken);
//$requests->setHeaderContentTypeJson();
//$requests->setBody($accountCreationBody);
//$requests->post($baseApiUrl . $endpoint);
//
//$responseHeaders = $requests->getHeaders();
//$responseBody = $requests->getResponse();
//
//prettyPrint($responseHeaders);
//prettyPrint($responseBody);






/**
 * Merchant info retrieval using account created above
 */
//$accountId = "2a982983-2cef-47e8-b4c3-9d1978d252d9"; //Created from the step above
//$endpoint = "accounts/$accountId";
//
//$requests =  Methods::requests();
//$requests->setBearerToken($accessToken);
//$requests->get($baseApiUrl . $endpoint);
//
//$responseHeaders = $requests->getHeaders();
//$responseBody = $requests->getResponse();
//
//prettyPrint($responseHeaders);
//prettyPrint($responseBody);


/**
 * Create source
 */

$endpoint = "api/sources";
$sourceCreationBody = [
//    'domain' => 'wee-pay.dk',
//    'domain' => 'https://wee-pay.dk',
    'name' => 'wee-pay',
    'sourceCode' =>'3278',
];
//$felixMerchantId = "8094b2ab-f0af-4c9c-9065-8f82630add6e";
//$requests =  Methods::requests();
//$requests->basicAuth("$specialResellerId:$felixMerchantId", $specialResellerApiKey);
//$requests->setHeaderContentTypeJson();
//$requests->setBody($sourceCreationBody);
//$requests->post("https://demo.vivapayments.com/api/sources");
//
//$responseHeaders = $requests->getHeaders();
//$responseBody = $requests->getResponse();
//
//prettyPrint($responseHeaders);
//prettyPrint($responseBody);



/**
 * Create payment
 */

$myMerchantId = 'ee6d19b2-8b9e-41ed-874e-044680beeae7';
$apiBaseUrlDemo = "https://demo-api.vivapayments.com/";
$endpoint = "checkout/v2/isv/orders?merchantId=$myMerchantId";
$paymentBody = [
    'sourceCode' => "3387",
    'amount' => 10000,
    'isvAmount' => 500,
    'dynamicDescriptor' => 'some desc',
    'merchantTrns' => 'merch desc',
];

//prettyPrint([
//    'token' => $accessToken,
//    'url' => $apiBaseUrlDemo . $endpoint,
//    'payload' => $paymentBody,
//]);

//$requests =  Methods::requests();
//$requests->setBearerToken($accessToken);
//$requests->setHeaderContentTypeJson();
//$requests->setBody($paymentBody);
//$requests->post($apiBaseUrlDemo . $endpoint);
//
//$responseHeaders = $requests->getHeaders();
//$responseBody = $requests->getResponse();
//
//prettyPrint($responseHeaders);
//prettyPrint($responseBody);
//
//prettyPrint($apiBaseUrlDemo . $endpoint);


//$vivaPayment = Methods::viva()->createPayment(
//    $myMerchantId, 100, "3387",
//    'some desc', '', 'merch desc',
//    false, false, null, null, 5,
//);
//prettyPrint($vivaPayment);








/**
 * Get payment
 */

//$orderCode = $responseBody['orderCode'];
//$orderCode = '7252067720026946';
//
//$endpoint = "api/transactions/?ordercode=$orderCode";
//$requests =  Methods::requests();
//$requests->basicAuth("$specialResellerId:$myMerchantId", $specialResellerApiKey);
//$requests->get("https://demo.vivapayments.com/" . $endpoint);
//
//$responseHeaders = $requests->getHeaders();
//$responseBody = $requests->getResponse();
//
//prettyPrint($responseHeaders);
//prettyPrint($responseBody);

/*  ------------------------------------------------------- ACCOUNTS END ------------------------------------------------------- */
?>





<div class="mt-5" >
    ddss
    <?php





    ?>
</div>