<?php

namespace classes\app;
use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\Uploads;

class UploadsHandler extends Crud {


    function __construct() {
        parent::__construct(Uploads::newStatic(), "uploads");
    }








    public function getByReferenceId(string|int|null $referenceId, array $fields = array()): Collection {
        return $this->queryGetAll(
            $this->queryBuilder()
                ->select($fields)
                ->where("reference_id", $referenceId)
                ->where("ttl", ">", time())
        );
    }

    public function getByObjectId(string|int|null $objectId, array $fields = array()): Collection {
        return $this->getByX(['object_id' => $objectId], $fields);
    }








    public function createUpload(array $data): array {
        foreach (["object_id", "path", "type"] as $key)
            if(!array_key_exists($key, $data)) return ["status" => "error", "error" => ["message" => "Missing key: $key"]];
        $type = $data["type"];
        $objectId = $data["object_id"];
        $path = $data["path"];
        $referenceId = array_key_exists("reference_id", $data) ? $data["reference_id"] : null;
        $params = $paramsCheck = [
            "object_id" => $objectId,
            "reference_id" => $referenceId,
            "type" => $type,
            "hash" => md5($path),
        ];
        $params["path"] = $path;

        if($this->exists($paramsCheck)) $status = $this->update($params, $paramsCheck);
        else $status = $this->create($params);
        if(!$status) return ["status" => "error", "error" => ["message" => "Failed to insert row."]];
        return ["status" => "success", "message" => "Item moved to bin.", "data" => $params];
    }











}