<?php

namespace classes\notifications\enumerations;

use JetBrains\PhpStorm\Pure;

class EmailTypes {

    /**  CREATORS */
    const WELCOME_CREATOR = "\\classes\\emails\\templates\\WelcomeCreator";
    const ACCOUNT_SUSPENSION_CREATOR = "\\classes\\emails\\templates\\AccountSuspensionCreator";

    /**  BRANDS */
    const WELCOME_BRAND = "\\classes\\emails\\templates\\WelcomeBrand";
    const ACCOUNT_SUSPENSION_BRAND = "\\classes\\emails\\templates\\AccountSuspensionBrand";

    /** SHARED */
    const PWD_RESET = "\\classes\\emails\\templates\\PwdReset";
    const VERIFY_EMAIL = "\\classes\\emails\\templates\\VerifyEmail";




    #[Pure] public static function getTemplate(string|int $type, string $roleName): ?string {return self::getByRoleName($type, $roleName); }

    private static function getByRoleName(string|int $type, string $roleName): ?string {
        if($type === "pwd_reset") return self::PWD_RESET;
        if($type === "verify_email") return self::VERIFY_EMAIL;
        return match ($roleName) {
            "influencer" => match ($type) {
                default => null,
                "welcome" => self::WELCOME_CREATOR,
                "account_suspension" => self::ACCOUNT_SUSPENSION_CREATOR,
            },
            "brand" => match ($type) {
                default => null,
                "welcome" => self::WELCOME_BRAND,
                "account_suspension" => self::ACCOUNT_SUSPENSION_BRAND,
            },
            default => null,
        };
    }
}