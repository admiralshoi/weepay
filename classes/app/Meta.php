<?php
namespace classes\app;
use Database\Collection;
use Database\model\AppMeta;
use classes\Methods;

class Meta {
    private int $requestingUsersAccessLevel = 0;

    function __construct() {
        if(isset($_SESSION["access_level"])) $this->requestingUsersAccessLevel = $_SESSION["access_level"];
    }

    private function access(int $actionType): bool {
        return Methods::hasAccess("node","meta",$actionType, $this->requestingUsersAccessLevel);
    }


    public function get(string $metaName): mixed {
        $item = $this->getRow($metaName);
        if(empty($item)) return null;

        $data = $item->value;
        $dataType = $item->type;
        return Methods::misc()::enforceDataType($dataType, $data);
    }


    public function exists(string $name): bool {
        if(!$this->access(READ_ACTION)) return false;
        return AppMeta::where("name", $name)->exists();
    }


    public function getRow(string $metaName, $fields = array()): ?object {
        if(!$this->access(READ_ACTION)) return null;
        return AppMeta::where('name', $metaName)->select($fields)->first();
    }


    public function getByX(array $params = array(), array $fields = array()): ?Collection {
        if(!$this->access(READ_ACTION)) return null;
        return AppMeta::whereList($params)->select($fields)->all();
    }


    public function update(object|array|string|int|float $value, string $metaName, string $sql = ""): bool {
        if(!$this->access(MODIFY_ACTION)) return false;
        if(empty($metaName)) return false;
        if(is_array($value) || is_object($value)) $value = json_encode($value);
        return AppMeta::where("name", $metaName)->update(["value" => $value]);
    }

    public function create(array $params): bool {
        if(!$this->access(MODIFY_ACTION)) return false;
        if(empty($params)) return false;
        return AppMeta::insert($params);
    }



    public function getAllAsKeyPairs(): Collection {
        $items = $this->getByX();
        if($items->empty()) return $items;
        return $items->reduce(function ($initial, $item) {
            if(!isset($initial)) $initial = [];
            $key = $item["name"];
            $value = enforceDataType($item["value"], $item["type"]);
            return array_merge($initial, [$key => $value]);
        });
    }

    public function mergeSettingsWithRole(Collection $settings): object {
        if($settings->empty()) return $settings->list();
        $roleSettings = Methods::roles()->userRoleSettings($settings->list());
//        if(!is_null($settings->list()->user_role_settings)) unset($settings["user_role_settings"]);
        if(empty($roleSettings)) return $settings->list();

        foreach ($roleSettings as $key => $value) $settings->add($value, $key);
        return $settings->list();
    }


}

