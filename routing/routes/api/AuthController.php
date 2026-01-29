<?php

namespace routing\routes\api;

use classes\auth\PasswordHandler;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class AuthController {

    /**
     * Handle password recovery request
     * Accepts email or phone number and sends reset link via notification system
     */
    #[NoReturn] public static function passwordRecovery(array $args): void {
        $identifier = $args["identifier"] ?? null;
        $phoneCountryCode = $args["phone_country_code"] ?? null;
        $recaptchaToken = $args["recaptcha_token"] ?? null;

        // Validate reCAPTCHA
        debugLog(['token_received' => !empty($recaptchaToken), 'token_length' => strlen($recaptchaToken ?? '')], "PWD-RECOVERY-CAPTCHA-START");

        if (empty($recaptchaToken)) {
            debugLog(['error' => 'no token'], "PWD-RECOVERY-CAPTCHA-FAILED");
            Response()->jsonError("reCAPTCHA verificering fejlede", [], 400);
        }

        $captcha = Methods::reCaptcha();
        $tokenData = $captcha->getTokenData($recaptchaToken);
        debugLog(['token_data' => $tokenData], "PWD-RECOVERY-CAPTCHA-TOKEN-DATA");

        if (!$captcha->validate($tokenData)) {
            debugLog(['validation_failed' => true, 'token_data' => $tokenData], "PWD-RECOVERY-CAPTCHA-DENIED");
            Response()->jsonError("Kunne ikke verificere at du er et menneske", [], 401);
        }

        debugLog(['captcha_passed' => true, 'score' => $tokenData['score'] ?? 'N/A'], "PWD-RECOVERY-CAPTCHA-PASSED");

        if (isEmpty($identifier)) {
            Response()->jsonError("Indtast venligst din email eller telefonnummer", [], 400);
        }

        $result = PasswordHandler::requestReset([
            'identifier' => trim($identifier),
            'phone_country_code' => $phoneCountryCode
        ]);

        if ($result['status'] === 'error') {
            // Check if this is a cooldown error and pass through the extra data
            $extraData = [];
            if (!empty($result['cooldown'])) {
                $extraData['cooldown'] = true;
                $extraData['wait_seconds'] = $result['wait_seconds'] ?? 60;
            }
            Response()->jsonError($result['error'] ?? "Der opstod en fejl", $extraData, 400);
        }

        Response()->jsonSuccess($result['message']);
    }

}
