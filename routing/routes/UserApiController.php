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
        }

        if(!isEmpty($args['phone_country_code'])) {
            $phoneCountryCode = strtoupper(trim($args['phone_country_code']));
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

            Response()->jsonSuccess('Profil opdateret');
        }

        Response()->jsonError('Kunne ikke opdatere profil', [], 500);
    }

    #[NoReturn] public static function updateAddress(array $args): void {
        // Update address fields
        $updateData = [
            'address_street' => $args['address_street'] ?? null,
            'address_city' => $args['address_city'] ?? null,
            'address_zip' => $args['address_zip'] ?? null,
            'address_region' => $args['address_region'] ?? null,
            'address_country' => $args['address_country'] ?? null,
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
}
