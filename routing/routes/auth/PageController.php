<?php
namespace routing\routes\auth;

use classes\enumerations\Links;
use classes\Methods;
use classes\auth\PasswordHandler;

class PageController {

    public static function merchantDashboardLogin(array $args): mixed  {
        if(isLoggedIn()) {
            if(Methods::isMerchant()) Response()->redirect(__url(Links::$merchant->dashboard));
            // Logged in but wrong type - show login page with message
        }
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        return Views("MERCHANT_AUTH_DASHBOARD_LOGIN", compact('worldCountries'));
    }

    public static function adminDashboardLogin(array $args): mixed  {
        if(isLoggedIn()) {
            if(Methods::isAdmin()) Response()->redirect(__url(Links::$admin->dashboard));
            // Logged in but wrong type - show login page with message
        }
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        return Views("ADMIN_AUTH_DASHBOARD_LOGIN", compact('worldCountries'));
    }

    public static function merchantDashboardSignup(array $args): mixed  {
        return Views("MERCHANT_AUTH_DASHBOARD_SIGNUP", $args);
    }
    public static function consumerDashboardLogin(array $args): mixed  {
        // Store redirect URL if provided, otherwise clear any existing redirect
        if(!empty($args['redirect'])) {
            $_SESSION['redirect_after_profile_completion'] = $args['redirect'];
        } else {
            unset($_SESSION['redirect_after_profile_completion']);
        }

        if(isLoggedIn())  {
            // If logged in, check for redirect URL first
            if(!empty($_SESSION['redirect_after_profile_completion'])) {
                $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
                unset($_SESSION['redirect_after_profile_completion']);
                Response()->redirect($redirectUrl);
            }

            if(Methods::isAdmin()) $url = Links::$admin->dashboard;
            elseif(Methods::isConsumer()) $url = Links::$consumer->dashboard;
            elseif(Methods::isMerchant()) $url = Links::$merchant->dashboard;
            else return null;
            Response()->redirect(__url($url));
        }

        // Create OIDC session for consumer login
        $query = ['next' => 'consumer_login'];
        $token = crc32(json_encode($query) . "_" . __csrf());
        $oidcSessionId = Methods::oidcSession()->setSession("authenticate", $query, $token);

        // If OIDC session creation fails, still show the page but OIDC button won't work
        if(isEmpty($oidcSessionId)) {
            debugLog("Failed to create OIDC session for consumer login", "oidc-session-error");
            $oidcSessionId = null;
        }

        // Get auth error from query params if redirected back from failed OIDC
        $authError = $args['auth_error'] ?? null;

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("CONSUMER_AUTH_DASHBOARD_LOGIN", compact('oidcSessionId', 'authError', 'worldCountries'));
    }

    public static function consumerDashboardSignup(array $args): mixed  {
        // Store redirect URL if provided, otherwise clear any existing redirect
        if(!empty($args['redirect'])) {
            $_SESSION['redirect_after_profile_completion'] = $args['redirect'];
        } else {
            unset($_SESSION['redirect_after_profile_completion']);
        }

        if(isLoggedIn())  {
            if(Methods::isAdmin()) $url = Links::$admin->dashboard;
            elseif(Methods::isConsumer()) $url = Links::$consumer->dashboard;
            elseif(Methods::isMerchant()) $url = Links::$merchant->dashboard;
            else return null;
            Response()->redirect(__url($url));
        }

        // Create OIDC session for consumer signup
        $query = ['next' => 'consumer_signup'];
        $token = crc32(json_encode($query) . "_" . __csrf());
        $oidcSessionId = Methods::oidcSession()->setSession("authenticate", $query, $token);

        // If OIDC session creation fails, still show the page but OIDC button won't work
        if(isEmpty($oidcSessionId)) {
            debugLog("Failed to create OIDC session for consumer signup", "oidc-session-error");
            $oidcSessionId = null;
        }

        // Get auth error from query params if redirected back from failed OIDC
        $authError = $args['auth_error'] ?? null;

        return Views("CONSUMER_AUTH_DASHBOARD_SIGNUP", compact('oidcSessionId', 'authError'));
    }

    public static function changePassword(array $args): mixed  {
        if(!isLoggedIn()) Response()->redirect(__url(Links::$app->home));

        $user = \features\Settings::$user;
        $authRecord = Methods::localAuthentication()->excludeForeignKeys()->getFirst(['user' => __uuid()]);
        $isForced = !isEmpty($authRecord) && (int)$authRecord->force_password_change === 1;

        return Views("AUTH_CHANGE_PASSWORD", compact('user', 'isForced'));
    }

    public static function consumerCompleteProfile(array $args): mixed  {
        if(!isLoggedIn()) Response()->redirect(__url(Links::$app->auth->consumerSignup));
        if(!Methods::isConsumer()) Response()->redirect(__url(Links::$app->home));

        $user = Methods::users()->get(__uuid());
        if(isEmpty($user)) Response()->redirect(__url(Links::$app->auth->consumerSignup));

        // If user already has full_name, email and phone, redirect to intended destination or dashboard
        if(!isEmpty($user->full_name) && !isEmpty($user->email) && !isEmpty($user->phone)) {
            if(!empty($_SESSION['redirect_after_profile_completion'])) {
                $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
                unset($_SESSION['redirect_after_profile_completion']);
                Response()->redirect($redirectUrl);
            } else {
                Response()->redirect(__url(Links::$consumer->dashboard));
            }
        }

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("CONSUMER_AUTH_COMPLETE_PROFILE", compact('user', 'worldCountries'));
    }

    /**
     * Handle team invitation verification link
     * URL: /invitation/{org_uid}/{code}
     *
     * This validates the invitation code, logs the user in, marks the code as used,
     * and redirects to the password change page (since force_password_change is set)
     */
    public static function verifyInvitation(array $args): mixed {
        $organisationUid = $args['organisation_uid'] ?? null;
        $code = $args['code'] ?? null;

        // Validate required params
        if(isEmpty($organisationUid) || isEmpty($code)) {
            return Views("AUTH_INVITATION_ERROR", [
                'error' => 'Ugyldigt invitationslink.',
                'error_type' => 'invalid'
            ]);
        }

        // Verify the invitation code
        $twoFactorAuth = Methods::twoFactorAuth();
        $verification = $twoFactorAuth->verifyInvitationCode($organisationUid, $code);

        if(isEmpty($verification)) {
            return Views("AUTH_INVITATION_ERROR", [
                'error' => 'Invitationslinket er ugyldigt eller udløbet.',
                'error_type' => 'expired'
            ]);
        }

        // Get the user
        $userUid = $verification->user; // Raw UID since we used excludeForeignKeys
        $user = Methods::users()->get($userUid);

        if(isEmpty($user)) {
            return Views("AUTH_INVITATION_ERROR", [
                'error' => 'Brugerkontoen kunne ikke findes.',
                'error_type' => 'not_found'
            ]);
        }

        // Mark the invitation code as used BEFORE logging in
        $twoFactorAuth->markInvitationUsed($verification->uid);

        // Log the user in by setting session variables
        $userArray = toArray($user);
        $keys = array_keys($userArray);
        $keys[] = "logged_in";
        $keys[] = "localAuth";
        setSessions($userArray, $keys);

        // Redirect to password change page (force_password_change should be set)
        Response()->redirect(__url(Links::$app->auth->changePassword));
        return null;
    }

    /**
     * Password recovery request page
     * Allows users to request a password reset link via email or phone
     */
    public static function passwordRecovery(array $args): mixed {
        // If already logged in, redirect to dashboard
        if(isLoggedIn()) {
            if(Methods::isAdmin()) $url = Links::$admin->dashboard;
            elseif(Methods::isConsumer()) $url = Links::$consumer->dashboard;
            elseif(Methods::isMerchant()) $url = Links::$merchant->dashboard;
            else $url = Links::$app->home;
            Response()->redirect(__url($url));
        }

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("AUTH_PASSWORD_RECOVERY", compact('worldCountries'));
    }

    /**
     * Reset password page (after clicking link in email/SMS)
     * Validates token, logs user in, sets force_password_change, shows reset form
     */
    public static function resetPassword(array $args): mixed {
        $token = $args['token'] ?? null;

        // If no token provided, redirect to recovery page
        if(isEmpty($token)) {
            Response()->redirect(__url(Links::$app->auth->passwordRecovery));
            return null;
        }

        // If user is logged in, log them out and refresh the page
        if(isLoggedIn()) {
            session_destroy();
            Response()->refresh();
            return null;
        }

        $passwordHandler = new PasswordHandler();

        // Validate token
        if(!$passwordHandler->resetAvailable($token)) {
            // Token invalid or expired - show error page
            return Views("AUTH_RESET_PASSWORD", [
                'error' => 'Linket er ugyldigt eller udløbet. Anmod venligst om et nyt link.',
                'tokenValid' => false
            ]);
        }

        // Get the reset record and user
        $resetRecord = $passwordHandler->getResetByToken($token);
        if(isEmpty($resetRecord) || isEmpty($resetRecord->user)) {
            return Views("AUTH_RESET_PASSWORD", [
                'error' => 'Der opstod en fejl. Prøv venligst igen.',
                'tokenValid' => false
            ]);
        }

        $user = $resetRecord->user; // Foreign key resolves to User object

        // Get the user's AuthLocal record
        $authLocal = Methods::localAuthentication()->excludeForeignKeys()->getFirst(['user' => $user->uid]);
        if(isEmpty($authLocal)) {
            return Views("AUTH_RESET_PASSWORD", [
                'error' => 'Brugerkontoen har ikke lokal login aktiveret.',
                'tokenValid' => false
            ]);
        }

        // Set force_password_change = 1 on AuthLocal
        Methods::localAuthentication()->update(
            ['force_password_change' => 1],
            ['uid' => $authLocal->uid]
        );

        // Mark the token as used
        $passwordHandler->markTokenUsed($token);

        // Log the user in by setting session variables
        $userArray = toArray($user);
        $keys = array_keys($userArray);
        $keys[] = "logged_in";
        $keys[] = "localAuth";
        setSessions($userArray, $keys);

        // Render the reset password page (which will use the change-password API)
        return Views("AUTH_RESET_PASSWORD", [
            'user' => $user,
            'tokenValid' => true
        ]);
    }
}