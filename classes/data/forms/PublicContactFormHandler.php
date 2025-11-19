<?php

namespace classes\data\forms;

use classes\utility\Crud;
use Database\model\ContactFormSubmissions;

class PublicContactFormHandler extends Crud {

    function __construct() {
        parent::__construct(ContactFormSubmissions::newStatic(), "form_submissions");
    }


    public function qualifySubmission($email): bool {
        return !$this->queryBuilder()
            ->startGroup("OR")
                ->startGroup("AND")
                    ->where("email", $email)
                    ->whereTimeAfter("created_at", strtotime("-3 months"))
                ->endGroup()
                ->startGroup("AND")
                    ->whereColumnIsNotNull("_csrf")
                    ->where("_csrf", __csrf())
                ->endGroup()
            ->endGroup()
            ->exists();
    }

    public function insert(
        string $name,
        string $email,
        string $subject,
        string $content,
        bool $newsletterConsent = false,
    ): bool {
        return $this->create([
            "name" => $name,
            "email" => $email,
            "subject" => $subject,
            "content" => $content,
            "newsletter_consent" => (int)$newsletterConsent,
            "uuid" => isLoggedIn() ? __uuid() : null,
            "_csrf" => __csrf()
        ]);
    }




}