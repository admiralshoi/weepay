<?php

use classes\Methods;

$clientId  = "sandbox-tricky-turtle-183";
$clientSecret  = "aB8b34QPuOeGd7Sym4f7UbyHUBkTP7OwGma7KbGLrH8oUYUF";
$accountId = "a-spge-vtZQu1njZNgLAgWErfx9";



/**
 * oAuth 2.0
 */
$authUrl = "https://api.signicat.com/auth/open/connect/token";
$authBody = [
    'grant_type' => 'client_credentials',
    'scope' => 'signicat-api',
];

$requests =  Methods::requests();
$requests->basicAuth($clientId, $clientSecret);
$requests->setHeaderContentTypeFormEncoded();
$requests->setBody($authBody);
$requests->post($authUrl);

$responseHeaders = $requests->getHeaders();
$responseBody = $requests->getResponse();

prettyPrint($responseHeaders);
prettyPrint($responseBody);

$accessToken = $responseBody['access_token'];


exit;



$sessionBody = json_decode('{
    "flow": "redirect",
    "allowedProviders": [
        "mitid"
    ],
    "additionalParameters": {
        "mitid_reference_text": "c29tZXRleHQgZm9yIHRlc3Rpbmc="
    },
    "requestedAttributes": [
        "name",
        "family_name",
        "given_name",
        "firstName",
        "lastName",
        "dateOfBirth",
        "nin",
        "nin_type",
        "mitidHasCpr",
        "mitidReferenceTextBody",
        "mitidCprSource",
        "mitidNameAndAddressProtection",
        "mitidIal",
        "mitidLoa",
        "mitidAal",
        "mitidFal",
        "mitidUuid"
    ],
    "callbackUrls": {
        "success": "https://localhost/weepay/mitid-test/success",
        "abort": "https://localhost/weepay/mitid-test/abort",
        "error": "https://localhost/weepay/mitid-test/success"
    }
}', true);

//prettyPrint($sessionBody);
$sessionUrl = "https://api.signicat.com/auth/rest/sessions?signicat-accountId=$accountId";
$requests =  Methods::requests();
$requests->setBearerToken($accessToken);
$requests->setHeaderContentTypeJson();
$requests->setBody($sessionBody);
$requests->post($sessionUrl);

$responseHeaders = $requests->getHeaders();
$responseBody = $requests->getResponse();

testLog($responseBody);
$userAuthenticationUrl = $responseBody['authenticationUrl'];
$sessionId = $responseBody['id'];

prettyPrint($responseBody);



?>

<script>
    window.open(<?=json_encode($userAuthenticationUrl)?>);
</script>

<div class="mt-5" >
    <?php







    ?>

    ddd
</div>