<?php
namespace Database\model;

class Cronjob extends \Database\Model {
    public static ?string $uidPrefix = "cro";
    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "started_at" => "integer",
        "finished_at" => "integer",
        "can_run" => "tinyInteger",
        "is_running" => "tinyInteger",
        "access_level" => "tinyInteger",
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "uid"
    ];


    protected static array $requiredRows = [
        [
            "uid" => "crn_3i3uzrwyal2m5j4",
            "name" => "Hashtag tracking",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_9ezwcjusz2yf8av",
            "name" => "Media Update",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_2ywksq8hm1fogp8",
            "name" => "Tag Mentions",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_fny6zh56nw2dy3c",
            "name" => "Account Insights",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_tkw3fwyq3cuj7xw",
            "name" => "Event Mode",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_2ppdykrmb0ux842",
            "name" => "Affiliate payment period",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
    ];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
        ];
    }
}