<?php

namespace classes\app;

use classes\utility\Crud;
use Database\Collection;
use Database\model\Tasks;

class TaskManager extends Crud {


    function __construct() {
        parent::__construct(Tasks::newStatic(), "tasks");
    }




    public function todo(?int $limit = 10, array $fields = []): Collection {
        $query = Tasks::queryBuilder()->select($fields)
            ->where("status", 'open')
            ->where("execute_at", '<=', time());
        if($limit !== null) $query->limit($limit)->order('execute_at', 'asc');
        return $this->queryGetAll($query);
    }



    public function newTask(
        string $type,
        string $objectId,
        int $executeAt,
        ?array $data = null,
        string $status = 'open',
    ): bool {
        $this->update(['status' => "closed"], ['object_id' => $objectId, 'type' => $type, 'status' => 'open']);
        return $this->create([
            'type' => $type,
            'object_id' => $objectId,
            'execute_at' => $executeAt,
            'data' => $data,
            'status' => $status,
        ]);
    }






    public function close(?string $objectId): bool {
        if(empty($objectId)) return false;
        return $this->update(['status' => "closed"], ['object_id' => $objectId, 'status' => 'open']);
    }

    public function revertSubscription(object $task): bool {

    }









}