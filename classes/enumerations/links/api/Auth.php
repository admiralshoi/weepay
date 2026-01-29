<?php

namespace classes\enumerations\links\api;
class Auth {

    public string $merchantLogin = 'api/merchant/login';
    public string $merchantSignup = 'api/merchant/signup';
    public string $consumerLogin = 'api/consumer/login';
    public string $adminLogin = 'api/admin/login';
    public string $consumerOidcInit = 'api/consumer/oidc/init';
    public string $consumerUpdateProfile = 'api/consumer/update-profile';
    public string $consumerSendVerificationCode = 'api/consumer/send-verification-code';
    public string $consumerVerifyCode = 'api/consumer/verify-code';
    public string $consumerCheckPhoneVerification = 'api/consumer/check-phone-verification';
    public string $verify2faLogin = 'api/auth/verify-2fa-login';
    public string $resend2faLoginCode = 'api/auth/resend-2fa-code';
    public string $changePassword = 'api/auth/change-password';
    public string $passwordRecovery = 'api/password-recovery';

}