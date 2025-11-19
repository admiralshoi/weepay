<?php

namespace classes\app;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\EventLog;


class EventTracker extends Crud {

    private const eventTypes = array( "click", "change", "page_view", "video_view", "passive");
    private static array $eventValues = array(
        "page_view" => [],
        "click" => ["story_publish"],
        "change" => [],
        "passive" => []
    );

    private int $requestingUsersAccessLevel = 0;
    private int $requestingUsersId = 0;


    function __construct() {
        parent::__construct(EventLog::newStatic(), "event_log");
        $this->buildActionValueCollection();
    }


    private function buildActionValueCollection(): void {
//        self::$eventValues["page_view"] = array_keys((new PageSettings())->pageSettings());
    }






    public function rowCountToday(): int {
        $todayStart = strtotime("today");
        return $this->queryBuilder()->whereTimeAfter("created_at", $todayStart, ">=")->count();
    }





    public function event_log(array $args): void {
        if(empty($args)) return;

        if(!array_key_exists("event_type", $args) || !array_key_exists("event_value", $args)) return;
        $eventType = $args["event_type"];
        $eventValue = $args["event_value"];
        $_object = array_key_exists("_object", $args) ? $args["_object"] : null;
        $_value = array_key_exists("_value", $args) ? $args["_value"] : null;
        $_page = array_key_exists("_page", $args) ? $args["_page"] : null;

        if(!in_array($eventType,self::eventTypes)) return;

        /*  need a prepared list of all pages / paths  */
//        if(!in_array($eventValue,self::$eventValues[$eventType])) return;


        $eventSessionId = crc32("$eventValue-$_page-$_object-$_value");
        if($eventType === "page_view") {
            $lastViewTimestamp = nestedArray($_SESSION, ["events", "page_view", $eventSessionId], 0);
            if($lastViewTimestamp >= strtotime("2 hours ago")) return;
        }

        if(!array_key_exists("events", $_SESSION)) $_SESSION["events"] = [];
        if(!array_key_exists($eventType, $_SESSION["events"])) $_SESSION["events"][$eventType] = [];
        $_SESSION["events"][$eventType][$eventSessionId] = time();


        $this->create([
            "event_type" => $eventType,
            "event_value" => $eventValue,
            "uid" => array_key_exists("uid", $args) ? $args["uid"] : __uuid(),
            "_object" => $_object,
            "_value" => $_value,
            "_page" => $_page,
        ]);
    }


}






























