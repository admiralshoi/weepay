<?php
namespace routing\routes;
use classes\Methods;
use classes\utility\Titles;
use http\Env\Response;

class LandingController {

    public static function home(array $args): mixed  {
        return Views("LANDING_HOME", $args);
    }



    public static function mitIdTestSuccess(array $args): mixed  {
        $sessionId = $args["sessionId"];

        $clientId  = "sandbox-tricky-turtle-183";
        $clientSecret  = "aB8b34QPuOeGd7Sym4f7UbyHUBkTP7OwGma7KbGLrH8oUYUF";



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

        $responseBody = $requests->getResponse();
        $accessToken = $responseBody['access_token'];






        $sessionUrl = "https://api.signicat.com/auth/rest/sessions/$sessionId";
        $requests =  Methods::requests();
        $requests->setBearerToken($accessToken);
        $requests->setHeaderContentTypeJson();
        $requests->get($sessionUrl);

        $responseHeaders = $requests->getHeaders();
        $responseBody = $requests->getResponse();



        return Views("LANDING_MITID_TEST_SUCCESS", [$responseHeaders, $responseBody]);
    }
    public static function mitIdTest(array $args): mixed  {
        return Views("LANDING_MITID_TEST", $args);
    }
    public static function vivaTestReturnUrl(array $args): mixed  {
        return Views("LANDING_VIVA_TEST_RETURN", $args);
    }
    public static function vivaTest(array $args): mixed  {
        return Views("LANDING_VIVA_TEST", $args);
    }
    public static function login(array $args): mixed  {
        return Views("LANDING_LOGIN", $args);
    }

    public static function signup(array $args): mixed  {
        return Views("LANDING_SIGNUP", $args);
    }
    public static function affiliateSignup(array $args): mixed  {
        return Views("AFFILIATE_SIGNUP", $args);
    }

    public static function passwordRecovery(array $args): mixed  {
        return Views("LANDING_PASSWORD_RECOVERY", $args);
    }







}