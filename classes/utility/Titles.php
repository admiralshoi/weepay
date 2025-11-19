<?php
namespace classes\utility;


class Titles {
    public static function prettify($input){
        if(!in_array(gettype($input),array("string","array")))
            return $input;

        if(is_array($input)) {
            $input = array_map(function ($string) {
                return self::clean($string);
            },$input);
        }
        else
            $input = self::clean($input);

        return $input;
    }

    public static function prettifiedUppercase(string $str): string {
        if(empty($str)) return $str;
        if(!str_contains($str," ")) return ucfirst($str);

        return trim(array_reduce((explode(" ",$str)), function ($currentStr,$word) {
            return $currentStr . " " . ucfirst($word);
        }));
    }

    public static function cleanUcAll(string $str): string {
        if(empty($str)) return $str;
        $str = self::clean($str);
        return self::prettifiedUppercase($str);
    }
    public static function reverseClean(string $str): string {
        return strtolower(str_replace(" ", "_", $str));
    }


//-------------------------------------------------------------------------------------------------------------------------------------------------------

    public static function truncateStr(?string $str, int $n, bool $endOfString = true): string {
        if(empty($str)) return "";
        if (mb_strlen($str, 'UTF-8') > $n) {
            // Use mb_substr() for multi-byte safe truncation
            return $endOfString
                ? mb_substr($str, 0, ($n - 3), 'UTF-8') . "..."
                : "..." . mb_substr($str, mb_strlen($str, 'UTF-8') - ($n - 3), null, 'UTF-8');
        }
        return $str;
    }

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public static function pluralS(int|float $n, string $str): string { return abs($n) > 1 || abs($n) < 1 ? $str . "s" : $str; }

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


    public static function clean(string $input): string{
        return str_replace("_"," ",ucfirst($input));
    }

    public static function cleanArray(array|object $list): object|array {
        $converted = false;
        if(is_object($list)) {
            $list = toArray($list);
            $converted = true;
        }
        foreach ($list as $i => $value) {
            if(is_array($value) || is_object($value)) $list[$i] = self::cleanArray($value);
            elseif(!is_string($value)) $list[$i] = $value;
            else $list[$i] = self::clean($value);
        }
        return $converted ? toObject($list) : $list;
    }
}