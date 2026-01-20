<?php
namespace routing\routes;

use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

/**
 * Common API controller for user settings (both Consumer and Merchant)
 */
class UserApiController {

    #[NoReturn] public static function updateProfile(array $args): void {
        // Validation
        foreach (['full_name', 'email'] as $key) {
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) {
                Response()->jsonError("Fulde navn og email er påkrævet", [], 400);
            }
        }

        $fullName = trim($args['full_name']);
        $email = trim($args['email']);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response()->jsonError("Ugyldig email format", [], 400);
        }

        // Check if email is already taken by another user
        $existingUser = Methods::users()->getFirst(['email' => $email]);
        if($existingUser && $existingUser->uid !== __uuid()) {
            Response()->jsonError("Denne email er allerede i brug", [], 400);
        }

        // Phone validation
        $phone = null;
        $phoneCountryCode = null;
        $phoneRemoved = false;
        $phoneChanged = false;

        // Get current user to check if phone is being changed/removed
        $currentUser = Methods::users()->get(__uuid());

        if(!isEmpty($args['phone'])) {
            // Clean phone number
            $phone = preg_replace('/[^0-9+]/', '', $args['phone']);

            if(strlen($phone) < 8) {
                Response()->jsonError("Telefonnummer skal være mindst 8 cifre", [], 400);
            }

            // Check if phone is already taken by another user
            $existingUser = Methods::users()->getFirst(['phone' => $phone]);
            if($existingUser && $existingUser->uid !== __uuid()) {
                Response()->jsonError("Dette telefonnummer er allerede i brug", [], 400);
            }

            // Only set country code if phone is provided
            if(!isEmpty($args['phone_country_code'])) {
                $phoneCountryCode = strtoupper(trim($args['phone_country_code']));
            }

            // Check if phone has changed (either number or country code)
            $currentPhoneClean = preg_replace('/[^0-9+]/', '', $currentUser->phone ?? '');
            $currentCountryCode = $currentUser->phone_country_code ?? '';
            if($phone !== $currentPhoneClean || $phoneCountryCode !== $currentCountryCode) {
                $phoneChanged = true;

                // Verify that the new phone has been verified via 2FA
                if(!Methods::twoFactorAuth()->isVerified(__uuid(), $phone, $phoneCountryCode, 'phone_verification')) {
                    Response()->jsonError("Du skal bekræfte dit nye telefonnummer før du kan gemme", [], 400);
                }
            }
        } else {
            // Phone is being removed - check if user had a phone before
            if(!isEmpty($currentUser->phone)) {
                $phoneRemoved = true;
            }
            // Ensure country code is also cleared when phone is removed
            $phoneCountryCode = null;
        }

        // Update user
        $updateData = [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'phone_country_code' => $phoneCountryCode,
        ];

        $updated = Methods::users()->update($updateData, ['uid' => __uuid()]);

        if($updated) {
            // Also update AuthLocal if exists
            $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);
            if($authLocal) {
                Methods::localAuthentication()->update([
                    'email' => $email,
                    'phone' => $phone,
                    'phone_country_code' => $phoneCountryCode,
                ], ['uid' => $authLocal->uid]);
            }

            // If phone was removed, also delete 2FA verification records for this user's phone
            if($phoneRemoved) {
                Methods::twoFactorAuth()->clearUserPhoneVerification(__uuid());
            }

            // If phone was changed, clear the used verification record
            if($phoneChanged) {
                // Clear old verification records since phone is now confirmed
                Methods::twoFactorAuth()->clearUserPhoneVerification(__uuid());
            }

            Response()->jsonSuccess('Profil opdateret');
        }

        Response()->jsonError('Kunne ikke opdatere profil', [], 500);
    }

    #[NoReturn] public static function updateAddress(array $args): void {
        $addressCountry = $args['address_country'] ?? null;

        // Validate country code if provided
        if(!isEmpty($addressCountry)) {
            $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
            $validCountry = false;
            foreach($worldCountries as $country) {
                $code = is_object($country) ? $country->countryCode : $country['countryCode'];
                if(strtoupper($code) === strtoupper($addressCountry)) {
                    $validCountry = true;
                    break;
                }
            }
            if(!$validCountry) {
                Response()->jsonError("Ugyldigt land valgt", [], 400);
            }
        }

        // Update address fields
        $updateData = [
            'address_street' => $args['address_street'] ?? null,
            'address_city' => $args['address_city'] ?? null,
            'address_zip' => $args['address_zip'] ?? null,
            'address_region' => $args['address_region'] ?? null,
            'address_country' => $addressCountry,
        ];

        $updated = Methods::users()->update($updateData, ['uid' => __uuid()]);

        if($updated) {
            Response()->jsonSuccess('Adresse opdateret');
        }

        Response()->jsonError('Kunne ikke opdatere adresse', [], 500);
    }

    #[NoReturn] public static function updatePassword(array $args): void {
        foreach (['password', 'password_confirm'] as $key) {
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) {
                Response()->jsonError("Udfyld begge felter", [], 400);
            }
        }

        $password = trim($args['password']);
        $passwordConfirm = trim($args['password_confirm']);

        if(strlen($password) < 8) {
            Response()->jsonError("Adgangskode skal være mindst 8 tegn", [], 400);
        }

        if($password !== $passwordConfirm) {
            Response()->jsonError("Adgangskoder matcher ikke", [], 400);
        }

        // Check if user already has a password
        $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);
        $newPwd = passwordHashing($password);

        // Get user info
        $user = Methods::users()->get(__uuid());

        if($authLocal) {
            // Update existing password
            $updated = Methods::localAuthentication()->update([
                'password' => $newPwd,
                'enabled' => 1
            ], ['uid' => $authLocal->uid]);
        } else {
            // Create new AuthLocal entry
            $created = Methods::localAuthentication()->create([
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $newPwd,
                'user' => __uuid(),
                'enabled' => 1,
                'phone_country_code' => $user->phone_country_code ?? null,
            ]);
            $updated = !empty($created);
        }

        if($updated) {
            Response()->jsonSuccess($authLocal ? 'Adgangskode opdateret' : 'Adgangskode oprettet');
        }

        Response()->jsonError($authLocal ? 'Kunne ikke opdatere adgangskoden' : 'Kunne ikke oprette adgangskoden', [], 500);
    }

    #[NoReturn] public static function verifyPhone(array $args): void {
        // TODO: Implement phone verification logic
        // This would typically involve:
        // 1. Sending SMS verification code
        // 2. Storing verification code temporarily
        // 3. Verifying the code when user submits it
        // 4. Marking phone as verified in database

        Response()->jsonError('Telefon verifikation er ikke implementeret endnu', [], 501);
    }

    #[NoReturn] public static function updateUsername(array $args): void {
        $username = isset($args['username']) ? trim($args['username']) : null;

        // Username can be null/empty (remove username)
        if(!isEmpty($username)) {
            // Validate username format: alphanumeric, underscore, 3-30 chars
            if(!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                Response()->jsonError("Brugernavn skal være 3-30 tegn og kun indeholde bogstaver, tal og understreg", [], 400);
            }

            // Check if username is already taken by another user
            $existingAuth = Methods::localAuthentication()->excludeForeignKeys()->getFirst(['username' => $username]);
            if($existingAuth && $existingAuth->user !== __uuid()) {
                Response()->jsonError("Dette brugernavn er allerede i brug", [], 400);
            }
        } else {
            $username = null;
        }

        // Get user's auth local record
        $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);

        if(!$authLocal) {
            Response()->jsonError("Du skal først oprette en adgangskode før du kan sætte et brugernavn", [], 400);
        }

        $updated = Methods::localAuthentication()->update([
            'username' => $username
        ], ['uid' => $authLocal->uid]);

        if($updated) {
            Response()->jsonSuccess($username ? 'Brugernavn opdateret' : 'Brugernavn fjernet');
        }

        Response()->jsonError('Kunne ikke opdatere brugernavn', [], 500);
    }

    #[NoReturn] public static function updateTwoFactor(array $args): void {
        $twoFactorEnabled = (int)($args['two_factor_enabled'] ?? 0);
        $twoFactorMethod = $args['two_factor_method'] ?? 'SMS';

        // Validate 2FA method
        $allowedMethods = ['SMS', 'EMAIL'];
        if(!in_array($twoFactorMethod, $allowedMethods)) {
            Response()->jsonError("Ugyldig 2FA metode", [], 400);
        }

        // Get user to check access level
        $user = Methods::users()->get(__uuid());
        if(!$user) {
            Response()->jsonError("Bruger ikke fundet", [], 404);
        }

        // Consumers (access_level = 1) cannot disable 2FA
        if($user->access_level == 1 && $twoFactorEnabled == 0) {
            Response()->jsonError("Forbrugere kan ikke deaktivere to-faktor godkendelse", [], 400);
        }

        // Get user's auth local record
        $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);

        if(!$authLocal) {
            Response()->jsonError("Du skal først oprette en adgangskode før du kan ændre 2FA indstillinger", [], 400);
        }

        // If enabling 2FA with SMS, verify user has a phone number
        if($twoFactorEnabled == 1 && $twoFactorMethod === 'SMS') {
            if(isEmpty($user->phone)) {
                Response()->jsonError("Du skal have et telefonnummer for at bruge SMS 2FA", [], 400);
            }
        }

        $updated = Methods::localAuthentication()->update([
            '2fa' => $twoFactorEnabled,
            '2fa_method' => $twoFactorEnabled ? $twoFactorMethod : null
        ], ['uid' => $authLocal->uid]);

        if($updated) {
            Response()->jsonSuccess($twoFactorEnabled ? 'To-faktor godkendelse aktiveret' : 'To-faktor godkendelse deaktiveret');
        }

        Response()->jsonError('Kunne ikke opdatere 2FA indstillinger', [], 500);
    }

    // =====================================================
    // BELL NOTIFICATIONS
    // =====================================================

    /**
     * Get paginated notifications for current user
     * Supports infinite scroll with cursor-based pagination
     */
    #[NoReturn] public static function notificationsList(array $args): void {
        $cursor = $args['cursor'] ?? null;
        $perPage = isset($args['per_page']) ? (int)$args['per_page'] : 15;
        $perPage = max(1, min(50, $perPage)); // Limit between 1 and 50

        $result = Methods::userNotifications()->getPaginated(__uuid(), $perPage, $cursor);
        $items = $result['items'];
        $meta = $result['meta'];

        // Transform items for API response
        $notifications = $items->map(function($notification) {
            return [
                'uid' => $notification['uid'],
                'title' => $notification['title'],
                'content' => $notification['content'],
                'type' => $notification['type'],
                'icon' => $notification['icon'],
                'link' => $notification['link'],
                'is_read' => (int)$notification['is_read'],
                'created_at' => $notification['created_at'],
                'time_ago' => \classes\utility\Numbers::timeAgo(strtotime($notification['created_at'])),
            ];
        });

        Response()->jsonSuccess('', $notifications->apiPaginationResponse($meta));
    }

    /**
     * Mark a single notification as read
     */
    #[NoReturn] public static function notificationsMarkRead(array $args): void {
        $uid = $args['uid'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError('Notification ID mangler', [], 400);
        }

        // Verify notification belongs to current user
        $notification = Methods::userNotifications()->get($uid);
        if (isEmpty($notification)) {
            Response()->jsonError('Notifikation ikke fundet', [], 404);
        }

        // Handle foreign key - user might be object or string
        $notificationUserUid = is_object($notification->user) ? $notification->user->uid : $notification->user;
        if ($notificationUserUid !== __uuid()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        Methods::userNotifications()->markAsRead($uid);
        Response()->jsonSuccess('Notifikation markeret som læst');
    }

    /**
     * Mark all notifications as read for current user
     */
    #[NoReturn] public static function notificationsMarkAllRead(array $args): void {
        Methods::userNotifications()->markAllAsRead(__uuid());
        Response()->jsonSuccess('Alle notifikationer markeret som læst');
    }

    /**
     * Get unread notification count for current user
     */
    #[NoReturn] public static function notificationsUnreadCount(array $args): void {
        $count = Methods::userNotifications()->countUnread(__uuid());
        Response()->jsonSuccess('', ['unread_count' => $count]);
    }
}
