<?php
namespace classes\emails\templates;
use classes\emails\Email;

class PwdReset extends Email {

    private const template = "views/templates/emails/pwd_reset.php";
    private const emailSubject = SITE_NAME . " password reset request";

    public function set($data): bool {
        if(is_null($data)) return false;
        $token =  $data["nodeContent"]["token"];
        $fullName =  $data["nodeContent"]["full_name"];
        $resetTokenLink = __url("password-recovery/$token");

        ob_start();
        include_once ROOT . self::template;
        $emailContent = ob_get_clean();

        $this->prepare($data["recipientId"], self::emailSubject, $emailContent);
        return true;
    }


}