<?php
namespace classes\emails\templates;

use classes\emails\Email;

class AccountSuspensionBrand extends Email {

    private const template = "views/templates/emails/suspension_brand.php";
    private const emailSubject = "Your account has been suspended";

    public function set($data): bool {
        if(is_null($data)) return false;
        $email = $data["nodeContent"]["email"];
        $fullname = $data["nodeContent"]["full_name"];

        ob_start();
        include_once ROOT . self::template;
        $emailContent = ob_get_clean();


        $this->prepare($email, self::emailSubject, $emailContent);
        return true;
    }


}