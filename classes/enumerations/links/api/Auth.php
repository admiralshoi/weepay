<?php

namespace classes\enumerations\links\api;
class Auth {

    public string $merchantLogin = 'api/merchant/login';
    public string $merchantSignup = 'api/merchant/signup';
    public string $consumerLogin = 'api/consumer/login';
    public string $consumerOidcInit = 'api/consumer/oidc/init';
    public string $consumerUpdateProfile = 'api/consumer/update-profile';
    public string $consumerSendVerificationCode = 'api/consumer/send-verification-code';
    public string $consumerVerifyCode = 'api/consumer/verify-code';
    public string $consumerCheckPhoneVerification = 'api/consumer/check-phone-verification';

}