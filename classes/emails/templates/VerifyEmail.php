<?php
namespace classes\emails\templates;
use classes\emails\Email;

class VerifyEmail extends Email {

    private const template = "views/templates/emails/verify_email.php";
    private const emailSubject = SITE_NAME . " - Verify your email";

    public function set($data): bool {
        if(is_null($data)) return false;
        $digits =  $data["nodeContent"]["digits"];
        $fullName =  $data["nodeContent"]["full_name"];

        ob_start();
        include_once ROOT . self::template;
        $emailContent = ob_get_clean();

        $this->prepare($data["recipientId"], self::emailSubject, $emailContent);
        return true;
    }


}