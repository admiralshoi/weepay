<?php

namespace classes\notifications;
use classes\notifications\enumerations\PushTypes as PUSHTYPE;
use classes\notifications\enumerations\EmailTypes as EMAILTYPE;
use classes\notifications\enumerations\NotificationTypes;
use Database\Collection;
use ReflectionClass;
use Database\model\Users;
use Database\model\UserRoles;
use Database\model\Notifications;
use classes\Methods;

if (session_status() == PHP_SESSION_NONE && !headers_sent()) session_start();


class NotificationsManager {


    private static string $reflectionClassName;
    private static string $type;
    private static string $ref;
    private static string $pushType;
    private static string|int $recipientId;
    private static array $nodeContent;
    private static string $emailTemplate;
    private static ?string $nId;

    protected static function update(array $params, array $identifier): bool {
        return Notifications::whereList($identifier)->update($params);
    }
    public static function get(string|int $nId): object {return Notifications::where("nid", $nId)->first(); }
    public static function getByX(array $params = array(), array $fields = array()): Collection {return Notifications::whereList($params)->select($fields)->all();}
    protected static function exists(string $nId): bool { return Notifications::where("nid", $nId)->exists(); }
    protected static function setContent(array $content): void {self::$nodeContent = $content;}
    protected static function setReflectionClassName(string $className): void {self::$reflectionClassName = $className;}

    protected static function mayReceiveNotification(int $pushType): bool {
        $row = self::getByX(array("recipient_id" => self::$recipientId, "type" => self::$type, "ref" => self::$ref));
        if(empty($row->list())) return true;

        $row->sortByKey("created_at");
        $lastCreationTimestamp = $row->nestedArray([0, "created_at"], 0);
        return NotificationTypes::delayIsOk($pushType, $lastCreationTimestamp, time());
    }

    protected static function setEmailTemplate(): void {
        $accessLevel = Users::where("uid", self::$recipientId)->getColumn("access_level");
        self::$emailTemplate = EMAILTYPE::getTemplate(self::$type, UserRoles::where("access_level", $accessLevel)->getColumn("name"));
    }


    protected static function initNewNotification(array $args): bool {
        foreach (array(
                     "type",
                     "recipient_id",
                     "ref",
                 ) as $key) if(!array_key_exists($key, $args)) return false;

        NotificationTypes::setType($args["type"]);
        if(!NotificationTypes::typeIsValid()) return false;
        if(!Users::where("uid",$args["recipient_id"])->exists()) return false;

        self::$ref = $args["ref"];
        self::$type = $args["type"];
        self::$recipientId = $args["recipient_id"];
        self::$pushType = array_key_exists("push_type", $args) ? $args["push_type"] : PUSHTYPE::BOTH;

        if(!self::mayReceiveNotification(self::$pushType)) return false;
        self::setEmailTemplate();


        return true;
    }



    protected static function execute(): bool {
        if(!isset(self::$nodeContent, self::$pushType, self::$recipientId, self::$type)) return false;
        if(in_array(self::$pushType, array(PUSHTYPE::EMAIL, PUSHTYPE::BOTH)) && empty(self::$emailTemplate)) return false;

        if(!is_null(self::$emailTemplate) && !class_exists(self::$emailTemplate)) return false;
        if(!self::mayReceiveNotification(0)) return false;

        if(!self::mayReceiveNotification(1)) self::$pushType = PUSHTYPE::PLATFORM; //If cant receive email, then force platform

        self::$nId = self::create(array(
            "recipient_id" => self::$recipientId,
            "ref" => self::$ref,
            "type" => self::$type,
            "push_type" => self::$pushType,
            "is_read" => (self::$pushType == PUSHTYPE::EMAIL ? 1 : 0),
        ));


        if(is_null(self::$nId)) return false;
        if(self::$pushType === PUSHTYPE::PLATFORM) return true;

        $emailHandler = new self::$emailTemplate();
        if($emailHandler->set(self::getClassProperties(self::$reflectionClassName))){
            $emailHandler->execute();
            return self::update(array("email_sent" => 1), array("nid" => self::$nId));
        }
        return false;
    }


    private static function create(array $params): ?string {
        foreach (array("recipient_id", "type", "push_type") as $key) if(!array_key_exists($key, $params)) return null;

        $params["email_sent"] = 0;

        while(true) {
            $params["nid"] = md5(crc32(rand(25,390032) . "_" . time()));
            if(!self::exists($params["nid"])) break;
        }

        return !Notifications::insert($params) ? null : $params["nid"];
    }

    protected static function getClassProperties(string $className): ?array {
        $reflectionClass = new ReflectionClass($className);
        return $reflectionClass->getStaticProperties();
    }


    public static function setIsRead(string|array $nIds): void {
        if(!is_array($nIds)) $nIds = [$nIds];
        foreach ($nIds as $nId) self::update(array("is_read" => 1), array("nid" => $nId));
    }






}