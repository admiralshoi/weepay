<?php
namespace routing\paths;


use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;

class Paths {

    private static bool $constantsIncluded = false;

    public static function view(string $name, bool $nesting = false): ?array {
        $viewList = [];
        $const = self::getConstant($name);

        if(empty($const)) {
            $path = __path("views." . $name);
            if(!file_exists($path)) return null;
            $viewList[] = ["view" => $name, "assets" => [], "custom_scripts" => [], "head" => null];
        }
        else {
            if(empty($const["view"]) || !file_exists(__path("views." . $const["view"]))) {
                throw new \Exception("Invalid View " . $const["view"] . " on constant $name. ");
            }
            $viewList[] = [
                "view" => $const["view"],
                "assets" => $const["assets"],
                "custom_scripts" => $const["custom_scripts"],
                "head" => $const["head"],
                "title" => $const["title"] ?? null,
            ];


            if(!is_null($const["template"])) {
                $result = self::view($const["template"], true);
                if(!is_null($result)) $viewList = array_merge($viewList, $result);
            }
        }

        if(empty($viewList)) return null;
        if($nesting) return $viewList;
        return self::viewAssetsTransform($viewList);
    }


    #[Pure] #[ArrayShape(["views" => "array", "css" => "array", "js" => "array"])]
    private static function viewAssetsTransform(array $viewList): array {
        $viewList = array_reverse($viewList);
        $transformed = ["views" => [], "css" => [], "js" => [],  "custom_scripts" => [], "head" => null, "title" => ""];

        $assets = [ //Ensure order.
            "base" => [],
            "vendor" => [],
            "custom" => [],
            "main" => [],
        ];

        foreach ($viewList as $item) {
            $transformed["views"][] = $item["view"];
            if(isset($item["title"]) && !empty($item["title"])) $transformed["title"] = $item["title"];

            if(!empty($item["custom_scripts"])) {
                if(!is_array($item["custom_scripts"])) $item["custom_scripts"] = [$item["custom_scripts"]];
                foreach ($item["custom_scripts"] as $script) {
                    if(file_exists(__view($script))) $transformed["custom_scripts"][] = $script;
                }
            }

            if(!empty($item["head"]) && file_exists(__view($item["head"]))) $transformed["head"] = $item["head"];
            if(empty($item["assets"])) continue;

            foreach ($item["assets"] as $key => $asset) if($asset !== null) $assets[$key] = array_merge($assets[$key], $asset);
        }

        if(empty($assets["base"])) $assets["base"] = self::getConstant("BASE");
        if(empty($assets["main"])) $assets["main"] = self::getConstant("MAIN");

        foreach ($assets as $key => $list) {
            if(empty($list)) continue;
            foreach ($list as $asset) {
                if(is_array($asset) && count($asset) === 2) {
                    $ext = $asset[1];
                    $asset = $asset[0];
                }
                else $ext = __ext($asset);
                if(!in_array($asset, $transformed["css"]) && $ext === "css") $transformed["css"][] = $asset;
                elseif(!in_array($asset, $transformed["js"]) && $ext === "js") $transformed["js"][] = $asset;
            }
        }

        return  $transformed;
    }

    private static function isDefinedConstant(string $name): bool {
        $classname = __CLASS__;
        return defined("$classname::$name");
    }



    private static function getAllConstants(): array     {
        self::includeConstants();
        $allConstants = [];
        $childClasses = get_declared_classes();

        foreach ($childClasses as $class) {
            if (is_subclass_of($class, self::class)) {
                $reflection = new ReflectionClass($class);
                $constants = $reflection->getConstants();
                $allConstants = array_merge($allConstants, $constants);
            }
        }

        return $allConstants;
    }

    private static function getConstant(string $name): mixed {
        self::includeConstants();
        $childClasses = get_declared_classes();

        foreach ($childClasses as $class) {
            if (is_subclass_of($class, self::class)) {
                $reflection = new ReflectionClass($class);
                if ($reflection->hasConstant($name)) {
                    return $reflection->getConstant($name);
                }
            }
        }

        return null;
    }


    private static function includeConstants(): void {
        if(self::$constantsIncluded) return;
        $dir = __DIR__ . "/constants/";
        foreach (directoryContent("$dir*.php") as $fn) require_once $dir . $fn;
        self::$constantsIncluded = true;
    }









}