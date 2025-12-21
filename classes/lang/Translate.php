<?php

namespace classes\lang;
use classes\lang\lib\DA;

class Translate {

    private static string $lang = "DA";

    public static function setLang(string $lang): void { self::$lang = $lang; }
    public static function getLang(): string { return self::$lang; }

    private static function getLangClass(): ?string {
        $class = "\\classes\\lang\\lib\\" . self::$lang;
        return class_exists($class) ? $class : null;
    }

    public static function word(string|int|null $q): string {
        if (empty($q)) return $q;
        $class = self::getLangClass();
        if (!$class) return $q;
        $key = strtolower((string)$q);
        if (!defined("$class::WORD")) return $q;
        $words = $class::WORD;
        return $words[$key] ?? $q;
    }

    public static function context(string|int|null $q): string {
        if (empty($q)) return $q;
        $class = self::getLangClass();
        if (!$class) return $q;
        if (!defined("$class::CONTEXT")) return $q;
        $context = $class::CONTEXT;
        $key = (string)$q;
        $parts = explode(".", $key);
        $parts = array_map("strtolower", $parts);
        return nestedArray($context, $parts, $q);
    }

    public static function sentence(string|null $q): string {
        if (empty($q)) return $q;

        $class = self::getLangClass();
        if (!$class) return $q;
        if (!defined("$class::WORD")) return $q;
        $words = $class::WORD;

        // Split on whitespace but preserve delimiters
        $tokens = preg_split('/(\s+)/u', $q, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($tokens as &$token) {
            // extract word without punctuation
            if (preg_match('/^([^\p{L}]*)([\p{L}]+)([^\p{L}]*)$/u', $token, $m)) {
                $prefix = $m[1];
                $word   = $m[2];
                $suffix = $m[3];

                $lower = strtolower($word);
                if (isset($words[$lower])) {
                    $token = $prefix . $words[$lower] . $suffix;
                }
            }
        }

        return implode("", $tokens);
    }
}
