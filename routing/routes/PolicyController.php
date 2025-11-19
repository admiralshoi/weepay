<?php
namespace routing\routes;

class PolicyController {



    public static function privacy(array $args): mixed  {
        return Views("PRIVACY_POLICY", $args);
    }


    public static function termsOfUse(array $args): mixed  {
        return Views("TERMS_OF_USE", $args);
    }

    public static function affiliateTermsAndUsage(array $args): mixed  {
        return Views("AFFILIATE_TERMS_AND_USAGE", $args);
    }




}