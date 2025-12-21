<?php

namespace classes\notifications\enumerations;

use classes\notifications\enumerations\PushTypes as PUSHTYPE;

class NotificationItems {



    const PWD_RESET_REQUIRED = array("uid", "token", "full_name");
    const PWD_RESET_OPTIONAL = array("push_type", "title", "message", "small_icon", "large_icon", "ref", "link");
    const PWD_RESET_VALUES = array(
        "ref" => "",
        "type" => "pwd_reset",
        "timestamp" => 0,
        "push_type" => PUSHTYPE::EMAIL,
        "title" => "Password reset",
        "message" => "",
        "small_icon" => "notification_support",
        "large_icon" => array("type" => "default", "id" => "customer_support_profile"),
        "link" => ""
    );

    /**
     * VERIFY EMAIL
     */
    const VERIFY_EMAIL_REQUIRED = array("uid", "digits", "full_name");
    const VERIFY_EMAIL_OPTIONAL = array("push_type", "title", "message", "small_icon", "large_icon", "ref", "link");
    const VERIFY_EMAIL_VALUES = array(
        "ref" => "",
        "type" => "verify_email",
        "timestamp" => 0,
        "push_type" => PUSHTYPE::EMAIL,
        "title" => "Verify email",
        "message" => "",
        "small_icon" => "notification_support",
        "large_icon" => array("type" => "default", "id" => "customer_support_profile"),
        "link" => ""
    );



    /**
     * WELCOME EMAIL
     *
     */
    const WELCOME_REQUIRED = array("uid");
    const WELCOME_OPTIONAL = array("title", "message", "small_icon", "large_icon", "ref", "link");
    const WELCOME_VALUES = array(
        "ref" => "",
        "type" => "welcome",
        "timestamp" => 0,
        "push_type" => PUSHTYPE::EMAIL,
        "title" => "Welcome onboard",
        "message" => "",
        "small_icon" => "notification_support",
        "large_icon" => array("type" => "default", "id" => "customer_support_profile"),
        "link" => ""
    );



    /**
     * ACCOUNT SUSPENSION
     *
     */
    const SUSPENSION_REQUIRED = array("uid", "full_name", "email");
    const SUSPENSION_OPTIONAL = array("title", "message", "small_icon", "large_icon", "ref", "link");
    const SUSPENSION_VALUES = array(
        "ref" => "",
        "type" => "account_suspension",
        "timestamp" => 0,
        "push_type" => PUSHTYPE::EMAIL,
        "title" => "Account suspension",
        "message" => "",
        "small_icon" => "notification_support",
        "large_icon" => array("type" => "default", "id" => "customer_support_profile"),
        "link" => ""
    );


    /**
     * TEAM INVITATION
     *
     */
    const TEAM_INVITE_REQUIRED = array("uid", "organisation_name", "role");
    const TEAM_INVITE_OPTIONAL = array("title", "message", "small_icon", "large_icon", "ref", "link", "push_type");
    const TEAM_INVITE_VALUES = array(
        "ref" => "",
        "type" => "team_invitation",
        "timestamp" => 0,
        "push_type" => PUSHTYPE::PLATFORM,
        "title" => "Team invitation",
        "message" => "",
        "small_icon" => "notification_support",
        "large_icon" => array("type" => "default", "id" => "customer_support_profile"),
        "link" => ""
    );


    /**
     * USER CREATED FOR ORGANISATION
     *
     */
    const USER_CREATED_REQUIRED = array("uid", "organisation_name", "username", "password");
    const USER_CREATED_OPTIONAL = array("title", "message", "small_icon", "large_icon", "ref", "link", "push_type");
    const USER_CREATED_VALUES = array(
        "ref" => "",
        "type" => "user_created",
        "timestamp" => 0,
        "push_type" => PUSHTYPE::EMAIL,
        "title" => "Account created",
        "message" => "",
        "small_icon" => "notification_support",
        "large_icon" => array("type" => "default", "id" => "customer_support_profile"),
        "link" => ""
    );

}