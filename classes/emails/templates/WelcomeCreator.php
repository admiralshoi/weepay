<?php
namespace classes\emails\templates;
use classes\emails\Email;

class WelcomeCreator extends Email {

    private const template = "views/templates/emails/welcome_email_creator.php";
    private const emailSubject = "Welcome to " . SITE_NAME;

    public function set($data): bool {
        if(is_null($data)) return false;
        $loginLink = __url('login');

        ob_start();
        include_once ROOT . self::template;
        $emailContent = ob_get_clean();

        $this->prepare($data["recipientId"], self::emailSubject, $emailContent);
        return true;
    }


}