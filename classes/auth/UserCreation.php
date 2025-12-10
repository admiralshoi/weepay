<?php
namespace classes\auth;

use classes\Methods;
use Database\model\Users;
use features\Settings;

class UserCreation {

    static array $EXPECTED_FIELDS = array("full_name","email","password", "password_repeat", "access_level");
    private const PWDDEF = "123456";
    protected static array $givenFields = array();
    public static ?array $error = null;
    private static bool $thirdPartyCreation = false;

    private static int $requestingUsersAccessLevel = 0;
    private static int $requestingUsersUuid = 0;



    public static function run(array $params, bool $thirdParty = false): bool {
        if(isset($_SESSION["access_level"])) self::$requestingUsersAccessLevel = $_SESSION["access_level"];
        if(isset($_SESSION["uid"])) self::$requestingUsersUuid = $_SESSION["uid"];
        self::$thirdPartyCreation = $thirdParty;
        self::setFields($params);
        return self::execute();
    }


    private static function setFields($params): void {
        if(empty($params)) return;
        if(self::$thirdPartyCreation) self::$EXPECTED_FIELDS[] = "access_level";

        foreach (self::$EXPECTED_FIELDS as $field) {
            if(!array_key_exists($field,$params)) {
                if(self::$thirdPartyCreation && str_contains($field, "password")) continue;
                self::$error = array("error" => "No $field was given");
                return;
            }

            self::$givenFields[$field] = $params[$field];
        }

        if(self::$thirdPartyCreation) self::$givenFields["password"] = self::$givenFields["password_repeat"] = self::PWDDEF;
        if(in_array("email", self::$EXPECTED_FIELDS) && !array_key_exists("username", self::$givenFields))
            self::$givenFields["username"] = array_key_exists("username", $params) ? $params["username"] : self::$givenFields["email"];
    }


    private static function execute(): bool {
        if(!empty(self::$error)) return false;
        self::validate();
        if(!empty(self::$error)) return false;


        if( //Assumes hat access_level is always present in thirdparty creation
            !self::$thirdPartyCreation &&
            !(array_key_exists("access_level", self::$givenFields) && in_array(self::$givenFields["access_level"], [1,2,6]))
        ) self::$givenFields["access_level"] = 1;
        self::$givenFields["registration_complete"] = (int)((int)self::$givenFields["access_level"] !== 1);
        self::$givenFields["created_by"] = self::$requestingUsersUuid;
        self::$givenFields["lang"] = "english";
//        return json_encode(array("error" => "Due to missing SocialMedia integrations, we're currently unable to create your account. Please try again later"));

        //Affiliate
        if(self::$givenFields["access_level"] === Methods::roles()->accessLevel("affiliate")) {
            if(!Settings::$app->allow_affiliate_signup) {
                self::$error = ["error" => "Affiliate registration is currently closed. Come back another time."];
                return false;
            }
        }


        if(Users::where("email", self::$givenFields["email"])->exists()) {
            self::$error = array("error" => "This email is already in use");
            return false;
        }
        if(Users::where("username", self::$givenFields["username"])->exists()) {
            self::$error = array("error" => "This username is already in use");
            return false;
        }

        while(true){
            $uuid = generateUniqueId(13, "INT");
            if(!Users::where("uuid", $uuid)->exists()) break;
        }

        $params = array(
            "username" => trim(self::$givenFields["username"]),
            "password" =>  passwordHashing(self::$givenFields["password"]),
            "full_name" => trim(self::$givenFields["full_name"]),
            "created_by" => self::$givenFields["created_by"],
            "access_level" => self::$givenFields["access_level"],
            "email" => trim(self::$givenFields["email"]),
            "uuid" => $uuid,
            "lang" => self::$givenFields["lang"],
            "registration_complete" => self::$givenFields["registration_complete"]
        );

        $insert = Users::insert($params);
        if($insert === false) {
            self::$error = array("error" => "Failed to create user");
            return false;
        }


//        $this->crud->notificationHandler()->welcomeEmail(
//            $this->crud,
//            array(
//                "uuid" => $uuid,
//            )
//        );

        if(!self::$thirdPartyCreation)
            LocalAuthentication::run(["email" => self::$givenFields["email"], "password" => self::$givenFields["password"]]);


        return true;
    }

    private static function validate(): void {
        foreach (self::$givenFields as $fieldName => $fieldValue) {
            if($fieldName === "email" && !filter_var($fieldValue,FILTER_VALIDATE_EMAIL)) self::$error = array("error" => "$fieldValue is not a legitimate email");
            elseif(in_array($fieldName, ["full_name", "username"]) && (strlen($fieldValue) <= 1 || strlen($fieldValue) >= 50)) self::$error = array("error" => "$fieldValue must be between 2 and 49 characters");
            elseif($fieldName === "password" && $fieldValue !== self::$givenFields["password_repeat"]) self::$error = array("error" => "The passwords do not match");
            elseif($fieldName === "access_level" && ((int)$fieldValue > 6 && self::$requestingUsersAccessLevel < 8)) self::$error = array("error" => "Cannot create user with access level $fieldValue");
        }
    }

}