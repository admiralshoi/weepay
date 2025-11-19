<?php

namespace Database;
use features\Settings;

class DbConnection {

    private static string $current = "";

    public static function getNewConfig(): ?array {
        if(LIVE) $file = ROOT . DB_LIVE_FILE;
        else $file = ROOT . DB_LOCAL_FILE;
        if(TESTING && forceLiveDb()) $default = "production";
        elseif(!TESTING) $default = "production";
        elseif(Settings::$migrating) $default = "production";
        else $default = "testing";

        $var = $file . "::" .$default;
        if($var === self::$current) return null;

        DbConnection::$current = $var;
        $conf = require $file;
        return $conf['connections'][$default];
    }

    public static function getConfig(): ?array {
        if(LIVE) $file = ROOT . DB_LIVE_FILE;
        else $file = ROOT . DB_LOCAL_FILE;

        if(TESTING && forceLiveDb()) $default = "production";
        elseif(!TESTING) $default = "production";
        elseif(Settings::$migrating) $default = "production";
        else $default = "testing";

        $conf = require $file;
        return $conf['connections'][$default];
    }

    public static function getPrefix(): string {
        if(LIVE) $file = ROOT . DB_LIVE_FILE;
        else $file = ROOT . DB_LOCAL_FILE;

        if(TESTING && forceLiveDb()) $default = "production";
        elseif(!TESTING) $default = "production";
        elseif(Settings::$migrating) $default = "production";
        else $default = "testing";

        $conf = require $file;
        return $conf['connections'][$default]["prefix"];
    }

}