<?php

namespace classes\notifications;
use classes\notifications\enumerations\NotificationItems as ITEMS;

class NotificationHandler extends NotificationsManager {

    //---------------------------- CORE METHODS START -----------------------------------------//

    private function setValuesTimestamp(array &$values):void { $values["timestamp"] = time(); }
    private function validateParams(array &$values, array $params, array $requiredItems, array $optionalItems, array $additionAllowedKeys): bool {
        foreach ($optionalItems as $optionalItem) if(array_key_exists($optionalItem, $params)) $values[$optionalItem] = $params[$optionalItem];
        foreach ($requiredItems as $requiredItem) {
            if (!array_key_exists($requiredItem, $params)) return false;
            $values[$requiredItem] = $params[$requiredItem];
        }

        $allowedKeys = array_merge($requiredItems, $optionalItems, $additionAllowedKeys);
        foreach (array_keys($values) as $valueKey) if(!in_array($valueKey, $allowedKeys)) return false;

        $this->setValuesTimestamp($values);
        return true;
    }


    private function exec(array $values): bool {
        $this->prepare($values);
        return parent::execute();
    }
    private function prepare(array $values): void {
        $this->setValues($values);
    }

    private function setValues(array $values): void {
        parent::initNewNotification(array(
            "type" => $values["type"],
            "recipient_id" => $values["uid"],
            "push_type" => $values["push_type"],
            "ref" => $values["ref"],
        ));
        parent::setContent($values);
        parent::setReflectionClassName(get_parent_class());
    }


    //---------------------------- CORE METHODS END -----------------------------------------//



    //---------------------------- NOTIFICATION METHODS START -----------------------------------------//


    /**  VERIFY EMAIL */
    public function verifyEmail(array $params): void {
        $values = ITEMS::VERIFY_EMAIL_VALUES;
        if(!$this->validateParams($values, $params, ITEMS::VERIFY_EMAIL_REQUIRED, ITEMS::VERIFY_EMAIL_OPTIONAL, array_keys($values))) return;
        $this->exec($values);
    }

    /**  PASSWORD RESET */
    public function pwdReset(array $params): void {
        $values = ITEMS::PWD_RESET_VALUES;
        if(!$this->validateParams($values, $params, ITEMS::PWD_RESET_REQUIRED, ITEMS::PWD_RESET_OPTIONAL, array_keys($values))) return;
        $this->exec($values);
    }


    /**  WELCOME EMAIL */
    public function welcomeEmail(array $params): void {
        $values = ITEMS::WELCOME_VALUES;
        if(!$this->validateParams($values, $params, ITEMS::WELCOME_REQUIRED, ITEMS::WELCOME_OPTIONAL, array_keys($values))) return;
        $this->exec($values);
    }


    /**  ACCOUNT SUSPENSIONS */
    public function accountSuspension(array $params): void {
        $values = ITEMS::SUSPENSION_VALUES;
        if(!$this->validateParams($values, $params, ITEMS::SUSPENSION_REQUIRED, ITEMS::SUSPENSION_OPTIONAL, array_keys($values))) return;
        $this->exec($values);
    }


    //---------------------------- NOTIFICATION METHODS END -----------------------------------------//



}