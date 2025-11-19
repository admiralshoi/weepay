<?php
namespace classes\auth;

use classes\Methods;

class UserLogin  {
    private static array $session_keys = array("full_name","uid","access_level","email", "lang", "cookies");
    const EXPECTED_FIELDS = array("username","password");
    private static ?string $redirectUri = null;
    protected static array $givenFields = array();
    public static ?string $error = null;
    public static bool $guest = false;


    public static function run(array $params): bool {
        self::setFields($params);
        return self::execute();
    }




    private static function setFields($params): void {
        if(empty($params)) return;

        foreach (self::EXPECTED_FIELDS as $field) {
            if(!array_key_exists($field,$params)) {
                self::$error = "No $field was given";
                return;
            }

            self::$givenFields[$field] = $params[$field];
        }
        if(array_key_exists("redirect_uri", $params)) self::$redirectUri = $params["redirect_uri"];
    }


    private static function getUserByDifferentColumnsAndPassword(string|int $identifier, string $password, array $columns = ['email', "username"]): ?object {
        if(empty($identifier) || empty($password) || empty($columns)) return null;
        $password = passwordHashing($password);
        for($i = 0; $i < 2; $i++) {
            $query = Methods::users()->queryBuilder();

            $query->startGroup("OR");
                foreach ($columns as $column) {
                    $query->where($column, $identifier);
                }
            $query->endGroup();

            $query->where("password", $password);
            if($i === 1) $query->where("deactivated", 1);
            $user = $query->first();
            if(!empty($user)) return $user;
        }
        return null;
    }



    private static function execute(): bool{
        if(!empty(self::$error)) return false;
        foreach (self::$givenFields as $param=>$value) if(empty($value)) self::$error = "'".ucfirst($param)."' cannot be empty. ";
        if(!empty(self::$error)) return false;


        $user = self::getUserByDifferentColumnsAndPassword(self::$givenFields["username"], self::$givenFields["password"]);
        if(empty($user)) {
            self::$error = "No user with these credentials";
            return false;
        }
        if((int)$user->deactivated === 1) {
            self::$error = "Your account has been suspended. Please check your email for details";
            return false;
        }


        self::signIn($user);
        if(!empty(self::$redirectUri)) header("Location: " . self::$redirectUri);
        return true;
    }


    public static function signIn(object $user): void {
        self::$session_keys[] = "logged_in";
        setSessions($user, self::$session_keys);
    }

}