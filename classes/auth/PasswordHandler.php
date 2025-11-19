<?php
namespace classes\auth;

use JetBrains\PhpStorm\ArrayShape;
use Database\model\Users;
use Database\model\PasswordResets;
use classes\Methods;

class PasswordHandler {

    public static function resetPassword(array $args): array  {
        if(isset($_SESSION["pwd_reset"])) unset($_SESSION["pwd_reset"]);
        if(!array_key_exists("email", $args) || empty($args["email"]) || !filter_var($args["email"], FILTER_VALIDATE_EMAIL))
            return ["status" => "error", "error" => "Invalid email"];
        $email = $args["email"];

        $user = Users::where("email", $email)->first();
        if(empty($user)) return ["status" => "success", "message" => "Email sent"];

        $uuid = $user->uuid;
        while (true) {
            $token = md5($uuid . $email . "__" . time() . "__" . rand(10,100000));
            if(!PasswordResets::where("token", $token)->exists()) break;
        }

        $creation = PasswordResets::insert([
            "uuid" => $uuid,
            "email" => $email,
            "token" => $token,
        ]);


        Methods::notificationHandler()->pwdReset(
            array(
                "uuid" => $uuid,
                "token" => $token,
                "full_name" => $user->full_name,
            )
        );


        return $creation ? ["status" => "success", "message" => "Email sent"] :
            ["status" => "error", "message" => "Failed the attempt to reset password. Try again later"];
    }


    public function resetAvailable(string $token): bool {
        $row = PasswordResets::where("token", $token)->first();
        if(empty($row)) return false;

        $expiresAt = (int)$row->expires_at;
        if(time() > $expiresAt) return false;

        if((int)$row->is_used === 0) {
            $_SESSION["pwd_reset"] = true;
            return true;
        }
        return false;
    }


    #[ArrayShape(["status" => "string", "message" => "string"])]
    public function createNewPassword(array $args): array {
        if(!array_key_exists("data", $args)) return array("status" => "error", "message" => "Missing fields");
        $data = $args["data"];

        foreach (array("password", "password_repeat", "token") as $key) if(!array_key_exists($key, $data))
            return array("status" => "error", "message" => "Missing field $key");
        $password = $data["password"];
        $passwordRepeat = $data["password_repeat"];
        $token = $data["token"];

        if(!isset($_SESSION["pwd_reset"]) || $_SESSION["pwd_reset"] !== true) return array("status" => "error", "message" => "You do not have permission to perform this action");

        $row = PasswordResets::where("token", $token)->first();
        if(!$this->resetAvailable($token)) return array("status" => "error", "message" => "#2 You do not have permission to perform this action");
        if($password !== $passwordRepeat)  return array("status" => "error", "message" => "The passwords do not match. Try again");
        if(strlen($password) < 6)  return array("status" => "error", "message" => "The password must be at least 6 characters long");


        $uuid = $row->uuid;
        $newPassword = passwordHashing($password);

        $user = Users::where("uuid", $uuid)->first();
        if($user->password === $newPassword || Users::where("uuid", $uuid)->update(["password" => $newPassword])) {
            PasswordResets::where("token", $token)->update(["is_used" => 1]);
            unset($_SESSION["pwd_reset"]);
            return array("status" => "success", "message" => "Your password has been updated");
        }

        return array("status" => "error", "message" => "Failed to update password. Try again later");
    }




}