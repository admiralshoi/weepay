<?php

namespace classes\enumerations\links\app;

class Auth {


    public string $merchantLogin = "merchant/login";
    public string $merchantSignup = "merchant/signup";
    public string $consumerLogin = "consumer/login";
    public string $consumerSignup = "consumer/signup";
    public string $adminLogin = "admin/login";
    public string $changePassword = "auth/change-password";
    public string $passwordRecovery = "password-recovery";
    public string $resetPassword = "reset-password";
    public string $invitation = "invitation"; // Base path for invitation links

    public Oicd $oicd;

    /**
     * Generate invitation link with org UID and code
     */
    public function invitationLink(string $organisationUid, string $code): string {
        return $this->invitation . "/{$organisationUid}/{$code}";
    }



    function __construct() {
        $ref = new \ReflectionClass(self::class);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue; // skip static
            }


            $type = $prop->getType();
            if (!$type) {
                continue; // skip untyped
            }

            // Skip if already initialized (PHP 8)
            if ($prop->isInitialized($this)) {
                continue;
            }

            $className = $type->getName();

            // We only auto-init class types, not scalar types
            if (class_exists($className)) {
                $prop->setAccessible(true);
                $prop->setValue($this, new $className());
            }
        }
    }

}