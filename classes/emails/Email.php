<?php

namespace classes\emails;
use Database\model\Users;

if (session_status() == PHP_SESSION_NONE && !headers_sent()) session_start();

Abstract class Email {


    protected bool $dataIsSet = false;
    protected string $defaultFromEmail = "no-reply@" . SITE_NAME;
    protected string $defaultFromName = SITE_NAME;
    private const MIME_VERSION = "MIME-Version: 1.0\r\n";
    private string $boundary;
    private string $headers;
    private string $senderFromEmail;
    private string $senderFromName;
    private string $message;
    private string $subject;
    private array $recipients;

    abstract public function set($data): bool;
    public function setReadyFlag(): void {$this->dataIsSet = true;}
    public function execute(): void { if($this->dataIsSet) foreach ($this->recipients as $recipient) mail($recipient, $this->subject, $this->message, $this->headers); }
    protected function setSubject(string $subject): void {$this->subject = $subject;}
    protected function setSenderFromName(string $name): void {$this->senderFromName = $name;}
    protected function setSenderFromEmail(string $email): void {$this->senderFromEmail = $email;}
    protected function setRecipients(string|array $recipients): void {
        if(!is_array($recipients)) $recipients = array($recipients);
        $this->recipients = array_map(function ($recipient) {
            if(filter_var($recipient, FILTER_VALIDATE_EMAIL)) return $recipient;
            return Users::where("uid", $recipient)->getColumn("email");
        }, $recipients);
    }


    protected function prepare(string|int $recipientIdOrEmail, string $subject, string $emailContent): void {
        if(!LIVE) return;
        $this->setSenderFromEmail($this->defaultFromEmail);
        $this->setSenderFromName($this->defaultFromName);
        $this->setRecipients($recipientIdOrEmail);
        $this->setSubject($subject);
        $this->setHeaders();
        $this->setMessageBody($emailContent);
        $this->setReadyFlag();
    }


    protected function setHeaders(): void {
        $this->boundary = md5( uniqid('',TRUE));
        $this->headers = "From: " . $this->senderFromName . " <" . $this->senderFromEmail . ">\r\n";
        // Specify MIME version 1.0
        $this->headers .= self::MIME_VERSION;
        // Tell e-mail client this e-mail contains alternate versions
        $this->headers .= "Content-Type: multipart/alternative; boundary=\"$this->boundary\"\r\n\r\n";
    }


    protected function setMessageBody(string $message): void {
        $this->message = "--$this->boundary\r\n" . // Plain text version of message
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n";
        $this->message .= chunk_split( base64_encode( strip_tags($message) ) );

        $this->message .= "--$this->boundary\r\n" . // HTML version of message
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n\r\n";
        $this->message .= chunk_split( base64_encode( $message ) );
        $this->message .= "--$this->boundary--";
    }






}