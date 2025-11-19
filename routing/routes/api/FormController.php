<?php

namespace routing\routes\api;

use classes\Methods;
use classes\utility\Titles;
use JetBrains\PhpStorm\NoReturn;

class FormController {

    #[NoReturn] public static function publicContactForm(array $args): void {
        $requiredKeys = ["contact_name", "contact_email", "msg_subject", "msg_content", "recaptcha_token"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty($args[$key]))
            Response()->jsonError("Missing required field: " . Titles::clean($key), [], 400);

        $name = trim($args["contact_name"]);
        $email = trim($args["contact_email"]);
        $subject = trim($args["msg_subject"]);
        $content = trim($args["msg_content"]);
        $reCaptchaToken = trim($args["recaptcha_token"]);
        $newsletterConsent = array_key_exists("consent_newsletter", $args) && $args["consent_newsletter"] === "on";

        if(empty($reCaptchaToken)) Response()->jsonError("ReCAPTCHA token is missing", [], 400);
        $captcha = Methods::reCaptcha();
        $tokenData = $captcha->getTokenData($reCaptchaToken);
        if(!$captcha->validate($tokenData)) {
            debugLog($tokenData, "recaptcha-denied-public-contact-form");
            Response()->jsonError("Could not verify human.", [], 401);
        }

        if(strlen($name) > 50) Response()->jsonError("Name too long", [], 400);
        if(strlen($email) > 50) Response()->jsonError("Email too long", [], 400);
        if(strlen($subject) > 100) Response()->jsonError("Subject too long. 100 characters max", [], 400);
        if(strlen($content) > 1000) Response()->jsonError("Message too long. 1000 characters ma.", [], 400);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) Response()->jsonError("Invalid email", [], 400);

        $handler = Methods::publicContactForm();
        if($handler->qualifySubmission($email)) Methods::publicContactForm()->insert($name, $email, $subject, $content, $newsletterConsent);
        Response()->jsonSuccess("The form submission has been received. Thank you");
    }

}