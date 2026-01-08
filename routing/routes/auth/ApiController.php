<?php

namespace routing\routes\auth;
use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Numbers;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class ApiController {

    #[NoReturn] public static function loginUser(array $args): void  {
        foreach (['username', 'password'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler et påkrævet felt: $key");

        $authHandler = Methods::localAuthentication();
        if(!$authHandler->validate($args)) {
            $error = $authHandler->getError();
            Response()->jsonError($error["error"]['message'], [], $error['code']);
        }

        $user = $authHandler->getUser();
        $authRecord = $authHandler->getAuthRecord();

        // Check if 2FA is enabled for this user
        if(!isEmpty($authRecord) && $authRecord->{'2fa'} == 1) {
            // 2FA is required - send code
            $twoFactorAuth = Methods::twoFactorAuth();

            if($authRecord->{'2fa_method'} === 'SMS') {
                // Need phone number and country code
                if(isEmpty($authRecord->phone) || isEmpty($authRecord->phone_country_code)) {
                    // 2FA enabled but no phone - disable 2FA and proceed with login
                    Methods::localAuthentication()->update(['2fa' => 0, '2fa_method' => null], ['user' => $user->uid]);
                } else {
                    // Get caller info for SMS
                    $callerInfo = Methods::misc()::callerCode($authRecord->phone_country_code, false);
                    if(isEmpty($callerInfo)) {
                        Response()->jsonError("Kunne ikke sende verifikationskode. Prøv igen.", [], 500);
                    }

                    $result = $twoFactorAuth->sendSmsCode(
                        $user->uid,
                        $authRecord->phone,
                        $authRecord->phone_country_code,
                        $callerInfo,
                        'login'
                    );

                    if(isEmpty($result)) {
                        Response()->jsonError("Kunne ikke sende verifikationskode. Prøv igen.", [], 500);
                    }

                    // Store login session temporarily for 2FA verification
                    $_SESSION['pending_2fa_login'] = [
                        'user_id' => $user->uid,
                        'auth_id' => $authRecord->uid,
                        'phone' => $authRecord->phone,
                        'phone_country_code' => $authRecord->phone_country_code,
                        'expires_at' => time() + 600 // 10 minutes
                    ];

                    // Return response indicating 2FA is required
                    $callerCode = $callerInfo['phone'];
                    $maskedPhone = substr($authRecord->phone, 0, 2) . '****' . substr($authRecord->phone, -2);
                    Response()->jsonSuccess("Verifikationskode sendt", [
                        'requires_2fa' => true,
                        'phone_hint' => '+' . $callerCode . ' ' . $maskedPhone,
                        'code_id' => $result['code_id'],
                        'expires_at' => $result['expires_at']
                    ]);
                }
            }
            // EMAIL 2FA can be added later
        }

        // No 2FA or 2FA disabled - proceed with normal login
        $authHandler->login();

        // Check if password change is required
        if(!isEmpty($authRecord) && $authRecord->force_password_change == 1) {
            // Redirect to password change page
            Response()->setRedirect(__url('settings/change-password'))->jsonSuccess("Du skal ændre dit kodeord før du kan fortsætte.");
            return;
        }

        // Check for stored redirect first, otherwise use role-based redirect
        if(!empty($_SESSION['redirect_after_profile_completion'])) {
            $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
            unset($_SESSION['redirect_after_profile_completion']);
        } else {
            $role = Methods::roles()->name($user?->access_level ?? 0);
            $redirectUrl = match ($role) {
                default => "",
                "consumer"=> __url(Links::$consumer->dashboard),
                "merchant" => __url(Links::$merchant->dashboard),
            };
        }

        Response()->setRedirect($redirectUrl)->jsonSuccess("Velkommen tilbage, " . $user->full_name);
    }

    #[NoReturn] public static function verify2faLogin(array $args): void {
        // Check for pending 2FA login session
        if(empty($_SESSION['pending_2fa_login'])) {
            Response()->jsonError("Ingen afventende login. Start forfra.", [], 401);
        }

        $pending = $_SESSION['pending_2fa_login'];

        // Check if expired
        if($pending['expires_at'] < time()) {
            unset($_SESSION['pending_2fa_login']);
            Response()->jsonError("Login sessionen er udløbet. Start forfra.", [], 401);
        }

        // Validate required fields
        if(!array_key_exists('code', $args) || empty($args['code'])) {
            Response()->jsonError("Verifikationskode er påkrævet", [], 400);
        }

        // Verify the code
        $twoFactorAuth = Methods::twoFactorAuth();
        if(!$twoFactorAuth->verifyCode(
            $pending['user_id'],
            $args['code'],
            $pending['phone'],
            $pending['phone_country_code'],
            'login'
        )) {
            Response()->jsonError("Ugyldig eller udløbet kode", [], 400);
        }

        // Clear pending session
        unset($_SESSION['pending_2fa_login']);

        // Get user and complete login
        $user = Methods::users()->get($pending['user_id']);
        if(isEmpty($user)) {
            Response()->jsonError("Bruger ikke fundet", [], 404);
        }

        // Log the user in
        $userArray = toArray($user);
        $keys = array_keys($userArray);
        $keys[] = "logged_in";
        $keys[] = "localAuth";
        setSessions($userArray, $keys);

        // Check if password change is required
        $authRecord = Methods::localAuthentication()->queryBuilder()
            ->where('user', $user->uid)
            ->first();

        if(!isEmpty($authRecord) && $authRecord->force_password_change == 1) {
            Response()->setRedirect(__url('settings/change-password'))->jsonSuccess("Du skal ændre dit kodeord før du kan fortsætte.");
            return;
        }

        // Check for stored redirect first, otherwise use role-based redirect
        if(!empty($_SESSION['redirect_after_profile_completion'])) {
            $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
            unset($_SESSION['redirect_after_profile_completion']);
        } else {
            $role = Methods::roles()->name($user?->access_level ?? 0);
            $redirectUrl = match ($role) {
                default => "",
                "consumer"=> __url(Links::$consumer->dashboard),
                "merchant" => __url(Links::$merchant->dashboard),
            };
        }

        Response()->setRedirect($redirectUrl)->jsonSuccess("Velkommen tilbage, " . $user->full_name);
    }

    #[NoReturn] public static function resend2faLoginCode(array $args): void {
        // Check for pending 2FA login session
        if(empty($_SESSION['pending_2fa_login'])) {
            Response()->jsonError("Ingen afventende login. Start forfra.", [], 401);
        }

        $pending = $_SESSION['pending_2fa_login'];

        // Check if expired
        if($pending['expires_at'] < time()) {
            unset($_SESSION['pending_2fa_login']);
            Response()->jsonError("Login sessionen er udløbet. Start forfra.", [], 401);
        }

        // Get caller info for SMS
        $callerInfo = Methods::misc()::callerCode($pending['phone_country_code'], false);
        if(isEmpty($callerInfo)) {
            Response()->jsonError("Kunne ikke sende verifikationskode. Prøv igen.", [], 500);
        }

        // Send new code
        $twoFactorAuth = Methods::twoFactorAuth();
        $result = $twoFactorAuth->sendSmsCode(
            $pending['user_id'],
            $pending['phone'],
            $pending['phone_country_code'],
            $callerInfo,
            'login'
        );

        if(isEmpty($result)) {
            Response()->jsonError("Kunne ikke sende verifikationskode. Prøv igen.", [], 500);
        }

        // Update session expiry
        $_SESSION['pending_2fa_login']['expires_at'] = time() + 600;

        $callerCode = $callerInfo['phone'];
        $maskedPhone = substr($pending['phone'], 0, 2) . '****' . substr($pending['phone'], -2);
        Response()->jsonSuccess("Ny verifikationskode sendt", [
            'phone_hint' => '+' . $callerCode . ' ' . $maskedPhone,
            'code_id' => $result['code_id'],
            'expires_at' => $result['expires_at']
        ]);
    }

    #[NoReturn] public static function signupUser(array $args): void {
        // Validate required fields for merchant signup
        foreach (['email', 'password', 'full_name'] as $key) {
            if(!array_key_exists($key, $args) || empty($args[$key]))
                Response()->jsonError("Mangler obligatorisk felt: $key", [], 400);
        }

        // Check if password confirmation matches (if provided)
        if (array_key_exists('password_confirm', $args) && $args['password'] !== $args['password_confirm']) {
            Response()->jsonError("Adgangskoderne matcher ikke", [], 400);
        }

        // Prepare user data for merchant
        $userData = [
            'email' => $args['email'],
            'full_name' => $args['full_name'],
            'phone' => !empty($args['phone']) ? $args['phone'] : null,
            'access_level' => 2, // Merchant
        ];

        // Prepare auth data
        $authData = [
            'email' => $args['email'],
            'username' => !empty($args['username']) ? $args['username'] : null,
            'phone' => !empty($args['phone']) ? $args['phone'] : null,
            'password' => $args['password'], // Will be hashed in LocalSignup
        ];

        // Create signup handler
        $signupHandler = Methods::localSignup();
        $signupHandler->setUserData($userData)->setAuthData($authData);

        // Validate
        if (!$signupHandler->validate()) {
            $error = $signupHandler->getError();
            Response()->jsonError($error['error']['message'], [], $error['code']);
        }

        // Create user and auth records
        if (!$signupHandler->signup()) {
            $error = $signupHandler->getError();
            Response()->jsonError($error['error']['message'], [], $error['code']);
        }

        // Auto-login
        if (!$signupHandler->autoLogin($args['password'])) {
            // If auto-login fails, still consider signup successful but redirect to login
            $redirectUrl = __url(Links::$app->auth->merchantLogin);
            Response()->setRedirect($redirectUrl)->jsonSuccess("Konto oprettet! Log venligst ind.");
        }

        $user = $signupHandler->getUser();
        $redirectUrl = __url(Links::$merchant->organisation->add); // Redirect to create first organisation
        Response()->setRedirect($redirectUrl)->jsonSuccess("Velkommen til " . BRAND_NAME . ", " . $user->full_name);
    }

    #[NoReturn] public static function sendVerificationCode(array $args): void {
        $userId = __uuid();
        if(isEmpty($userId)) Response()->jsonError("Bruger ikke fundet", [], 404);

        // Validate phone number
        if(!array_key_exists('phone', $args) || empty($args['phone'])) {
            Response()->jsonError("Telefonnummer er påkrævet", [], 400);
        }

        $phone = trim($args['phone']);
        $phoneCountryCode = array_key_exists('phone_country_code', $args) ? trim($args['phone_country_code']) : null;

        // Validate and clean phone number
        if(empty($phoneCountryCode)) $phoneCountryCode = Settings::$app->default_country;
        $calleInfo = Methods::misc()::callerCode($phoneCountryCode, false);
        if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'phone_country_code'], 400);

        $callerCode = $calleInfo['phone'];
        $phoneLength = $calleInfo['phoneLength'];
        $cleanedPhone = Numbers::cleanPhoneNumber($phone, false, $phoneLength, $callerCode);
        if(empty($cleanedPhone)) Response()->jsonError("Ugyldigt telefonnummer angivet", ['blame_field' => 'phone'], 400);

        // Send verification code
        $twoFactorAuth = Methods::twoFactorAuth();
        $result = $twoFactorAuth->sendSmsCode($userId, $cleanedPhone, $phoneCountryCode, $calleInfo, 'phone_verification');

        if(isEmpty($result)) {
            Response()->jsonError("Kunne ikke sende verifikationskode. Prøv igen.", [], 500);
        }

        Response()->jsonSuccess("Verifikationskode sendt til +" . $callerCode . " " . $cleanedPhone, [
            'code_id' => $result['code_id'],
            'expires_at' => $result['expires_at']
        ]);
    }

    #[NoReturn] public static function checkPhoneVerification(array $args): void {
        $userId = __uuid();
        if(isEmpty($userId)) Response()->jsonError("Bruger ikke fundet", [], 404);

        // Validate required fields
        if(!array_key_exists('phone', $args) || empty($args['phone'])) {
            Response()->jsonError("Telefonnummer er påkrævet", [], 400);
        }

        $phone = trim($args['phone']);
        $phoneCountryCode = array_key_exists('phone_country_code', $args) ? trim($args['phone_country_code']) : null;

        // Validate and clean phone number
        if(empty($phoneCountryCode)) $phoneCountryCode = Settings::$app->default_country;
        $calleInfo = Methods::misc()::callerCode($phoneCountryCode, false);
        if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'phone_country_code'], 400);

        $callerCode = $calleInfo['phone'];
        $phoneLength = $calleInfo['phoneLength'];
        $cleanedPhone = Numbers::cleanPhoneNumber($phone, false, $phoneLength, $callerCode);
        if(empty($cleanedPhone)) Response()->jsonError("Ugyldigt telefonnummer angivet", ['blame_field' => 'phone'], 400);

        // Check if verified
        $twoFactorAuth = Methods::twoFactorAuth();
        $isVerified = $twoFactorAuth->isVerified($userId, $cleanedPhone, $phoneCountryCode, 'phone_verification');

        Response()->jsonSuccess("", ['is_verified' => $isVerified]);
    }

    #[NoReturn] public static function verifyCode(array $args): void {
        $userId = __uuid();
        if(isEmpty($userId)) Response()->jsonError("Bruger ikke fundet", [], 404);

        // Validate required fields
        foreach (['phone', 'code'] as $key) {
            if(!array_key_exists($key, $args) || empty($args[$key])) {
                Response()->jsonError("Mangler obligatorisk felt: $key", [], 400);
            }
        }

        $phone = trim($args['phone']);
        $phoneCountryCode = array_key_exists('phone_country_code', $args) ? trim($args['phone_country_code']) : null;
        $code = $args['code'];

        // Validate and clean phone number
        if(empty($phoneCountryCode)) $phoneCountryCode = Settings::$app->default_country;
        $calleInfo = Methods::misc()::callerCode($phoneCountryCode, false);
        if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'phone_country_code'], 400);

        $callerCode = $calleInfo['phone'];
        $phoneLength = $calleInfo['phoneLength'];
        $cleanedPhone = Numbers::cleanPhoneNumber($phone, false, $phoneLength, $callerCode);
        if(empty($cleanedPhone)) Response()->jsonError("Ugyldigt telefonnummer angivet", ['blame_field' => 'phone'], 400);

        // Verify the code
        $twoFactorAuth = Methods::twoFactorAuth();
        if(!$twoFactorAuth->verifyCode($userId, $code, $cleanedPhone, $phoneCountryCode, 'phone_verification')) {
            Response()->jsonError("Ugyldig eller udløbet kode", [], 400);
        }

        Response()->jsonSuccess("Kode verificeret");
    }

    #[NoReturn] public static function updateConsumerProfile(array $args): void {
        $userId = __uuid();
        if(isEmpty($userId)) Response()->jsonError("Bruger ikke fundet", [], 404);

        // Validate required phone field
        if(!array_key_exists('phone', $args) || empty($args['phone'])) {
            Response()->jsonError("Telefonnummer er påkrævet", [], 400);
        }

        $phone = trim($args['phone']);
        $phoneCountryCode = array_key_exists('phone_country_code', $args) ? trim($args['phone_country_code']) : null;

        // Validate and clean phone number
        if(empty($phoneCountryCode)) $phoneCountryCode = Settings::$app->default_country;
        $calleInfo = Methods::misc()::callerCode($phoneCountryCode, false);
        if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'phone_country_code'], 400);

        $callerCode = $calleInfo['phone'];
        $phoneLength = $calleInfo['phoneLength'];
        $cleanedPhone = Numbers::cleanPhoneNumber($phone, false, $phoneLength, $callerCode);
        if(empty($cleanedPhone)) Response()->jsonError("Ugyldigt telefonnummer angivet", ['blame_field' => 'phone'], 400);

        // Verify that the phone number has been verified with 2FA
        $twoFactorAuth = Methods::twoFactorAuth();
        if(!$twoFactorAuth->isVerified($userId, $cleanedPhone, $phoneCountryCode, 'phone_verification')) {
            Response()->jsonError("Telefonnummer skal verificeres først", [], 400);
        }

        $userHandler = Methods::users();
        $user = $userHandler->get($userId);
        if(isEmpty($user)) Response()->jsonError("Bruger ikke fundet", [], 404);

        // Prepare update data
        $updateData = [
            'phone' => $cleanedPhone,
            'phone_country_code' => $phoneCountryCode
        ];

        // Add full_name if provided and user doesn't have one
        if(array_key_exists('full_name', $args) && !empty($args['full_name']) && isEmpty($user->full_name)) {
            $fullName = trim($args['full_name']);
            if(strlen($fullName) > 100) {
                Response()->jsonError("Navnet er for langt. Maximum 100 tegn", ['blame_field' => 'full_name'], 400);
            }
            $updateData['full_name'] = $fullName;
        }

        // Add email if provided (optional)
        if(array_key_exists('email', $args) && !empty($args['email'])) {
            // Validate email format
            if (!filter_var($args['email'], FILTER_VALIDATE_EMAIL)) {
                Response()->jsonError("Ugyldig email-adresse", [], 400);
            }

            // Check if email is already taken by another user
            $existingUser = $userHandler->getByX(['email' => $args['email']]);
            if(!isEmpty($existingUser) && $existingUser->count() > 0) {
                $existing = $existingUser->first();
                if($existing->uid !== $userId) {
                    Response()->jsonError("Denne email er allerede i brug", [], 409);
                }
            }

            $updateData['email'] = $args['email'];
        }

        if(
            $userHandler->queryBuilder()
                ->where('phone', $phone)
                ->where("phone_country_code", $phoneCountryCode)
                ->where('uid', '!=', $userId)
            ->exists()
        ) {
            $userHandler->queryBuilder()
                ->where('phone', $phone)
                ->where("phone_country_code", $phoneCountryCode)
                ->where('uid', '!=', $userId)
                ->update(['phone' => null, 'phone_country_code' => null]);
        }
        if(
            Methods::localAuthentication()->queryBuilder()
                ->where('phone', $phone)
                ->where("phone_country_code", $phoneCountryCode)
                ->where('user', '!=', $userId)
            ->exists()
        ) {
            Methods::localAuthentication()->queryBuilder()
                ->where('phone', $phone)
                ->where("phone_country_code", $phoneCountryCode)
                ->where('user', '!=', $userId)
                ->update(['phone' => null, 'phone_country_code' => null]);
        }

        // Clear 2FA phone verification records for other users who had this phone number
        // They will need to re-verify if they want to reclaim the number
        Methods::twoFactorAuth()->clearOtherUsersPhoneVerification($cleanedPhone, $phoneCountryCode, $userId);

        // Update user profile
        if(!$userHandler->update($updateData, ['uid' => $userId])) {
            Response()->jsonError("Kunne ikke opdatere profil. Prøv igen.", [], 500);
        }

        // Update or create local auth record with phone number and enable 2FA
        $localAuthHandler = Methods::localAuthentication();
        $existingAuth = $localAuthHandler->getFirst(['user' => $userId]);
        if(!isEmpty($existingAuth)) {
            // Update existing record with phone and enable 2FA
            $localAuthHandler->update([
                'phone' => $cleanedPhone,
                'phone_country_code' => $phoneCountryCode,
                '2fa' => 1,
                '2fa_method' => 'SMS'
            ], ['user' => $userId]);
        } else {
            // Create new record with phone, enable 2FA (without password - user can set it later if needed)
            $localAuthHandler->insert([
                'user' => $userId,
                'phone' => $cleanedPhone,
                'phone_country_code' => $phoneCountryCode,
                'email' => $updateData['email'] ?? $user->email ?? null,
                '2fa' => 1,
                '2fa_method' => 'SMS'
            ]);
        }

        // Redirect to intended destination or dashboard
        if(!empty($_SESSION['redirect_after_profile_completion'])) {
            $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
            unset($_SESSION['redirect_after_profile_completion']);
        } else {
            $redirectUrl = __url(Links::$consumer->dashboard);
        }

        Response()->setRedirect($redirectUrl)->jsonSuccess("Profil opdateret! Velkommen til " . BRAND_NAME);
    }


}