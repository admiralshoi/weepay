<?php

namespace env\api;


class Signicat {


    public static function sandbox(): void { self::$sandbox = true; }
    public static function live(): void { self::$sandbox = false; }


    public static function clientId(): string { return self::$sandbox ? self::SANDBOX_CLIENT_ID : self::CLIENT_ID; }
    public static function clientSecret(): string { return self::$sandbox ? self::SANDBOX_CLIENT_SECRET : self::CLIENT_SECRET; }
    public static function accountId(): string { return self::$sandbox ? self::SANDBOX_ACCOUNT_ID : self::ACCOUNT_ID; }
    public static function mitIdReferenceId(): string { return self::$sandbox ? self::SANDBOX_MITID_REFERENCE_ID : self::MITID_REFERENCE_ID; }
    public static function oAuthUrl(): string { return self::$sandbox ? self::SANDBOX_OAUTH_URL : self::OAUTH_URL; }
    public static function sessionCreateUrl(): string { return (self::$sandbox ? self::SANDBOX_SESSION_CREATE_URL : self::SESSION_CREATE_URL) . self::accountId(); }
    public static function sessionReadUrl(string $sessionId): string { return (self::$sandbox ? self::SANDBOX_READ_CREATE_URL : self::SESSION_READ_URL) . $sessionId; }
    public static function sessionCreationBody(
        string $successUrl,
        string $errorUrl,
        string $abortUrl,
        string $flow = 'redirect',
    ): array {
        return [
            "flow" => $flow,
            "allowedProviders" => ['mitid'],
            "additionalParameters" => [
                "mitid_reference_text" => self::mitIdReferenceId()
            ],
            "requestedAttributes" => self::SESSION_BODY_ATTRIBUTES,
            "callbackUrls" => [
                "success" => __url($successUrl),
                "abort" => __url($abortUrl),
                "error" => __url($errorUrl),
            ]
        ];
    }




    private static bool $sandbox = true;
    private const SANDBOX_CLIENT_ID = "sandbox-biting-clock-989";
//    private const SANDBOX_CLIENT_ID = "sandbox-tricky-turtle-183";
    private const SANDBOX_CLIENT_SECRET = "NY8YZLg05SlEsRTYgY7ANOCPzDQi1789r98uI4kTOTAdJzBY";
//    private const SANDBOX_CLIENT_SECRET = "aB8b34QPuOeGd7Sym4f7UbyHUBkTP7OwGma7KbGLrH8oUYUF";
    private const SANDBOX_ACCOUNT_ID = "a-spge-j25bjJdoKxJQ9mQSF6ex";
//    private const SANDBOX_ACCOUNT_ID = "a-spge-vtZQu1njZNgLAgWErfx9";
    private const SANDBOX_MITID_REFERENCE_ID = "c29tZXRleHQgZm9yIHRlc3Rpbmc=";
    private const CLIENT_ID = "prod-annoyed-brain-701";
    private const CLIENT_SECRET = "YszbVM9mloOaOln9dWVoTp3Br3YwmuhVfrusKavmkVsFjeqQ";
    private const ACCOUNT_ID = "a-ppge-7yBfGYQ0OtoAt3bPaW7P";
    private const MITID_REFERENCE_ID = "c29tZXRleHQgZm9yIHRlc3Rpbmc=";
    private const SANDBOX_OAUTH_URL = "https://api.signicat.com/auth/open/connect/token";
    private const OAUTH_URL = "https://api.signicat.com/auth/open/connect/token";
    private const SANDBOX_SESSION_CREATE_URL = "https://api.signicat.com/auth/rest/sessions?signicat-accountId=";
    private const SESSION_CREATE_URL = "https://api.signicat.com/auth/rest/sessions?signicat-accountId=";
    private const SANDBOX_READ_CREATE_URL = "https://api.signicat.com/auth/rest/sessions/";
    private const SESSION_READ_URL = "https://api.signicat.com/auth/rest/sessions/";




    private const SESSION_BODY_ATTRIBUTES = [
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
    ];






}