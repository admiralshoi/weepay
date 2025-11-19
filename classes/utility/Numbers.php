<?php
namespace classes\utility;


class Numbers {
    static public function shortenNumber($number,$shortM = true, $shortK = true, $includeCharSeparate = false): array|int|string {
        if(is_null($number)) return "";
        $number = (float)$number;
        $mil = 1000000; $kilo = 1000; $m="M"; $k="K"; $char = "";
        if(($number >= $mil || $number <= -$mil)  && $shortM) {
            $x = round($number / $mil,2);
            $char = $m;
        } else if(($number >= $kilo || $number <= -$kilo) && $shortK) {
            $x = round($number / $kilo,2);
            $char = $k;
        } else $x = $number;

        if($includeCharSeparate) return ["number" => $x, "char" => $char];
        return $x . $char;
    }

    public static function datesBetween(int $timeFrom, int $timeUntil): array {
        $uniqueDaysPaused = [];
        while($timeFrom <= $timeUntil) {
            $date = date('Y-m-d', $timeFrom);
            $uniqueDaysPaused[] = $date;
            $timeFrom = strtotime(date('Y-m-d', strtotime("$date +1 day")));
        }
        return array_values(array_unique($uniqueDaysPaused));
    }
    public static function daysBetweenTimestamps(int $start, int $end): int {
        $days = floor(($end - $start) / 86400);
        if ($days >= 0) $days += 1;
        else $days -= 1;
        if ($days === 0) $days = ($end > $start) ? 1 : -1;
        return (int)$days;
    }

    public static function cleanPhoneNumber(string $input, bool $includeCountryCode = false, array|int $length = 8, string|int $code = '45'): ?string {
        $digits = preg_replace('/\D+/', '', $input);
        if (!$digits) return null;
        $maxLength = $length;
        $minLength = $length;
        if(is_array($length)) {
            $maxLength = max($length);
            $minLength = min($length);
        }
        $local = substr($digits, -$maxLength);
        if (strlen($local) > $maxLength || strlen($local) < $minLength) return null;
        if ($includeCountryCode) $local = (string)$code . $local;

        return $local;
    }



    static public function timeAgo(string|int $timestamp, bool $countdown = false, bool $pluralS = true, array $prefixes = [], array $intervalCaps = []): string {
        if($countdown) {
            $timeNow = (int)$timestamp;
            $timestamp = time();
        }
        else {
            $timestamp = (int)$timestamp;
            $timeNow = time();
        }

        $standardPrefix = array(
            "year" => "year",
            "month" => "month",
            "day" => "day",
            "hour" => "hour",
            "minute" => "minute",
            "ago" => $countdown ? "" : "ago",
        );

        $cap = array(
            "year" => (24 * 365),
            "month" => (24 * 30 * 3),
            "day" => 24,
            "hour" => 0,
        );

        foreach ($intervalCaps as $key => $value) {
            if(!array_key_exists($key, $cap)) continue;
            if(!is_numeric($value)) continue;
            $cap[$key] = (float)$value;
        }

        foreach ($standardPrefix as $key => $name) {
            if(array_key_exists($key, $prefixes)) continue;
            $prefixes[$key] = $name;
        }

        $difference = round(($timeNow-$timestamp));
        $hoursFloor = floor($difference / 3600);

        if($cap["year"] < $hoursFloor) { //Year in hours
            $count = floor($hoursFloor / (24 * 365));
            $response = $count . ($pluralS ? " " . Titles::pluralS($count,$prefixes["year"]) : $prefixes["year"]) . " " . $prefixes["ago"];
        }
        else if($cap["month"] < $hoursFloor) { // 3 months in hours (we display days if not greater than 3 months)
            $count = floor($hoursFloor / (24 * 30)); // Display in unit of 1 month (not 3 months)
            $response = $count . ($pluralS ? " " . Titles::pluralS($count,$prefixes["month"]) : $prefixes["month"]) . " " . $prefixes["ago"];
        }
        else if($cap["day"] < $hoursFloor) { // day in hours
            $count = floor($hoursFloor / 24);
            $response = $count . ($pluralS ? " " . Titles::pluralS($count,$prefixes["day"]) : $prefixes["day"]) . " " . $prefixes["ago"];
        }
        else  {
            if(!($hoursFloor > $cap["hour"])) {
                $count = round($difference / 60);
                $response = $count . ($pluralS ? " " . Titles::pluralS($count,$prefixes["minute"]) : $prefixes["minute"]) . " " . $prefixes["ago"];
            }
            else $response = $hoursFloor . ($pluralS ? " " . Titles::pluralS($hoursFloor,$prefixes["hour"]) : $prefixes["hour"]) . " " . $prefixes["ago"]; // Hours
        }

        return trim($response);
    }


    static public function timeDifferenceInUnits(int $currentTime, int $compareTime, string $unit = "daily"): int|float|null {

        $timeDifference = $currentTime - $compareTime;
        $dayTimeDefined = (3600 * 24);
        switch ($unit) {
            default: return null;
            case "daily": return floor($timeDifference / $dayTimeDefined);
            case "weekly": return floor($timeDifference / ($dayTimeDefined * 7));
            case "monthly": return floor($timeDifference / ($dayTimeDefined * 365.25 / 12));
            case "yearly": return floor($timeDifference / ($dayTimeDefined * 365.25));
        }
    }

    public static function timeDifferenceLabel(int $timestamp1, int$timestamp2): string {
        $diff = abs($timestamp2 - $timestamp1);
        $minutes = floor($diff / 60);
        if ($minutes < 60) return $minutes . ' ' . Titles::pluralS($minutes, 'minute');
        $hours = floor($diff / 3600);
        if ($hours < 48) return $hours . ' ' . Titles::pluralS($hours, 'hour');
        $days = floor($diff / 86400);
        if ($days < 61) return $days . ' ' . Titles::pluralS($days, 'day');
        $months = floor($days / 30);
        return $months . ' ' . Titles::pluralS($months, 'hour');
    }


    static public function countdownInUnits(string|int $targetTime): \DateInterval {
        $targetTime = (int)$targetTime;
        $targetDate = date('m/d/Y H:i:s', $targetTime);


        $dt = new \DateTime($targetDate);
        return $dt->diff(new \DateTime());
    }


    public static function translateSeconds($time): string {
        $store = array("h" => 0, "m" => 0, "s" => 0); $remaining = $time;
        if($remaining >= 3600) {
            $store["h"] = floor($remaining/3600);
            $remaining = $remaining - $store["h"] * 3600;
        }
        if($remaining >= 60) {
            $store["m"] = floor($remaining/60);
            $remaining = $remaining - $store["m"] * 60;
        }
        $store["s"] = $remaining;

        return $store["h"]." Hours - ".$store["m"]." Minutes - ".$store["s"]." seconds";
    }




}