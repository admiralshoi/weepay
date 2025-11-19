<?php

namespace Database;
use features\Settings;

class DbColumnEncryptor {

    private array $details;

    public static function build(string $columnName = ""): static {
        return new static($columnName);
    }

    public function __construct(string $columnName = "") {
        $this->details = Settings::$encryptionDetails;
        if (!empty($columnName)) {
            $this->details["key"] = $columnName . "_" . $this->details["key"];
        }
    }

    public function encrypt(string|int|float|null $value): string|null {
        if ($value === "" || is_null($value)) return $value;
        $iv_length = openssl_cipher_iv_length($this->details["ciphering"]);
        return base64_encode(openssl_encrypt($value, $this->details["ciphering"], $this->details["key"], 0, $this->details["iv"]));
    }

    public function decrypt(string|int|float|null $value): string|null {
        if (empty($value)) return $value;
        $iv_length = openssl_cipher_iv_length($this->details["ciphering"]);
        return openssl_decrypt(base64_decode($value), $this->details["ciphering"], $this->details["key"], 0, $this->details["iv"]);
    }


}