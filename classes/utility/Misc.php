<?php

namespace classes\utility;

use classes\Methods;

class Misc {


    public static function ensureTimestamp(string|int $timestamp): int {
        if(is_numeric($timestamp)) {
            if(strlen((string)$timestamp) > strlen((string)time()))
                $timestamp = floor((int)$timestamp / 1000);
        }
        if(!is_numeric($timestamp)) $timestamp = strtotime($timestamp);
        return $timestamp;
    }



    public static function mediaType(string $url): ?string {
        $contentType = extToMediaType(__ext($url));
        if(!is_null($contentType)) return $contentType;
        if(!array_key_exists("scheme", parse_url($url))) return null;

        $args = [
            "url" => $url,
            "method" => "HEAD"
        ];
        $result = Methods::proxy()->run($args);
        if($result["status"] !== "success") return null;
        if(!array_key_exists("Content-Type", $result["headers"])) return null;
        $contentType = $result["headers"]["Content-Type"];
        if(str_starts_with($contentType, "video/")) return "video";
        if(str_starts_with($contentType, "image/")) return "image";
        return null;
    }



    public static function getNamesLibrary(): array {
        if(!file_exists(__lib(GENDER_LIB))) return array();

        $list = file_get_contents(__lib(GENDER_LIB));
        if(empty($list)) return array();

        return json_decode($list, true);
    }


    public static function getCountriesLib($lib = COUNTRY_NAME_BY_CODE): array {
        if(!file_exists(__lib($lib))) return array();
        $list = file_get_contents(__lib($lib));
        if(empty($list)) return array();
        return json_decode($list, true);
    }
    public static function countryCodeToName(?string $code): string {
        if(empty($code)) return "";
        $lib = self::getCountriesLib();
        return array_key_exists($code, $lib) ? $lib[$code] : "";
    }
    public static function callerCode(?string $countryCode, bool $callerCodeOnly = true, ?array $lib =  null): null|array|int|string {
        if(empty($countryCode)) return "";
        if(empty($lib)) $lib = self::getCountriesLib(DIALER_CODES);
        foreach ($lib as $item) {
            if($item['code'] === strtoupper($countryCode)) {
                return $callerCodeOnly ? $item['phone'] : $item;
            }
        }
        return null;
    }


    public static function setNewPageContent(array $request): bool {
        foreach (array("target", "content") as $key) if(!array_key_exists($key, $request)) return false;
        $content = $request["content"];
        $targetPage = $request["target"];

        if(!in_array($targetPage, array("privacy_policy", "terms_of_use"))) return false;
        $newFilename = $targetPage . "_" . time() . "_" . rand(5,1000) . ".html";
        $path = "includes/content/legal/";

        $filepath = $path . $newFilename;
        file_put_contents(ROOT . $filepath, $content);

        $metaName = "current_$targetPage";
        Methods::appMeta()->update($filepath, $metaName);

        return true;
    }


    public static function enforceDataType(string $type, mixed $data): mixed {
        return match ($type) {
            "array" => json_decode($data, true),
            "string" => (string)$data,
            "int" => (int)$data,
            "float" => (float)$data,
            "bool" => is_bool($data) ? $data : (in_array($data, array("true", "false")) ? (str_contains($data, "true")) : (bool)$data),
            default => null
        };
    }

    public static function isValidType(string $type, mixed $data): bool {
        return match ($type) {
            "array" => is_array($data),
            "string" => is_string($data),
            "int" => is_int($data),
            "float" => is_float($data),
            "bool" => is_bool($data),
            "object" => is_object($data),
            "null" => is_null($data),
            "callable" => is_callable($data),
            "iterable" => is_iterable($data),
            default => false
        };
    }





    public static function smallProfileIcon(string $url, bool|int $checkmark = false, bool|int $unknown = false): string {
        $html = '<div class="position-relative">';
        $html .= '<img src="' . resolveImportUrl($url, false, true) .'" class="noSelect square-50 border-radius-50 mr-2 mt-1" />';
        if($checkmark) {
            $html .= '<div style="position:absolute; top: -10px; right: -5px;">';
            $html .= '<i class="mdi mdi-check-decagram font-25 " style="color: #1c96df"></i>';
            $html .= '</div>';
        }
        elseif($unknown) {
            $html .= '<div style="position:absolute; top: -10px; right: -5px;">';
            $html .= '<i class="mdi mdi-account-question font-25 color-acoustic-yellow" data-toggle="tooltip" data-placement="top" title="This is an unknown creator."></i>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function medProfileIcon(string $url, bool|int $checkmark = false, bool|int $unknown = false): string {
        $html = '<div class="position-relative">';
        $html .= '<img src="' . resolveImportUrl($url, false, true) .'" class="noSelect square-75 border-radius-50 mr-2 mt-1" />';
        if($checkmark) {
            $html .= '<div style="position:absolute; top: -10px; right: -5px;">';
            $html .= '<i class="mdi mdi-check-decagram font-25 " style="color: #1c96df"></i>';
            $html .= '</div>';
        }
        elseif($unknown) {
            $html .= '<div style="position:absolute; top: -10px; right: -5px;">';
            $html .= '<i class="mdi mdi-account-question font-25 color-acoustic-yellow" data-toggle="tooltip" data-placement="top" title="This is an unknown creator."></i>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function microProfileIcon(string $url, bool|int $checkmark = false, bool|int $unknown = false): string {
        $html = '<div class="position-relative">';
        $html .= '<img src="' . resolveImportUrl($url, false, true) .'" class="noSelect square-30 border-radius-50" />';
        if($checkmark) {
            $html .= '<div style="position:absolute; bottom: -10px; left: -5px;">';
            $html .= '<i class="mdi mdi-check-decagram font-25 " style="color: #1c96df"></i>';
            $html .= '</div>';
        }
        elseif($unknown) {
            $html .= '<div style="position:absolute; top: -13px; left: -10px;">';
            $html .= '<i class="mdi mdi-account-question font-25 color-acoustic-yellow" data-toggle="tooltip" data-placement="top" title="This is an unknown creator."></i>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    
    
    
    public static function calculateFeatureUsagePerIntegration(string|int $includedUsage, string|int $includedIntegrations): int|float {
        $includedUsage = (int)$includedUsage;
        $includedIntegrations = (int)$includedIntegrations;
        return !(abs($includedIntegrations) > 0) ? 0 : floor($includedUsage / $includedIntegrations);
    }


}