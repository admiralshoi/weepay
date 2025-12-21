<?php

namespace classes\utility;

use classes\Methods;
use Database\Collection;
use Database\Model;
use Database\DbConnection;
use Database\QueryBuilder;
use http\QueryString;

class Crud {

    public Model $model;
    protected string $nodeName;
    public ?string $recentUid = null;
    private bool $includeForeignKeys = true;
    private ?array $fetchCleanMethod;
    private ?array $fetchAllCleanMethod;
    private ?array $paramsLogicMethod;

    function __construct(Model $model, string $nodeName, ?array $fetchCleanMethod = null, ?array $fetchAllMethod = null, ?array $paramsLogicMethod = null) {
        $this->model = $model;
        $this->nodeName = $nodeName;
        $this->fetchCleanMethod = $fetchCleanMethod;
        $this->fetchAllCleanMethod = $fetchAllMethod;
        $this->paramsLogicMethod = $paramsLogicMethod;
    }

    protected function access(int $actionType): bool {
        return Methods::hasAccess("node",$this->nodeName,$actionType, __accessLevel());
    }

    public function excludeForeignKeys(): static {
        $this->includeForeignKeys = false;
        return $this;
    }
    public function includeForeignKeys(): static {
        $this->includeForeignKeys = true;
        return $this;
    }


    protected function withForeignValues(?object $data, array &$tables = [], ?Model $model = null): ?object {
        if(is_null($model)) $model = $this->model;
        $foreignColumns = $model::foreignkeys();
        if(empty($foreignColumns)) return $data;
        if($data instanceof Collection) return null;
        if(isEmpty($data)) return $data;

        foreach ($foreignColumns as $column => $foreignColumn) {
            if(!property_exists($data, $column)) continue;
            $value = $data->$column;
            if(is_null($value)) continue;
            list($target, $static) = $foreignColumn;
            list($tableName, $targetColumn) = explode(".", $target);
            if(in_array($tableName, $tables)) continue;
            $tables[] = $tableName;

            if(!in_array($targetColumn, $static::$uniques)) $data->$column = $static->where($targetColumn, $value)->all();
            else $data->$column = $static->where($targetColumn, $value)->first();

            $tablePrefix = DbConnection::getPrefix();
            if(str_starts_with($tableName, $tablePrefix)) $tableName = str_replace($tablePrefix, "", $tableName);

            $modelName = $tableName;
            if(str_contains($tableName, "_")) {
                $words = explode("_", $tableName);
                $str = array_shift($words);
                foreach ($words as $word) $str .= ucfirst($word);
                $modelName = $str;
            }
            $modelName = ucfirst($modelName);
            $filename = ROOT . "Database/model/$modelName.php";
            $modelName = "\\Database\\model\\$modelName";
            if(!class_exists($modelName) && file_exists($filename)) require_once $filename;

            if(class_exists($modelName)) {
                $model = $modelName::newStatic();
                if($data->$column instanceof Collection) $data->$column = $this->withForeignCollection($data->$column, $tables, $model);
                else $data->$column = $this->withForeignValues($data->$column, $tables, $model);
            }
        }
        return $data;
    }

    protected function withForeignCollection(Collection $data, array &$tables = [], ?Model $model = null, bool $clearTablesEachLoop = false): Collection {
        if(is_null($model)) $model = $this->model;
        $foreignColumns = $model::foreignkeys();
        if(empty($foreignColumns)) return $data;
        $arr = [];
        foreach ($data->list() as $n => $item) {
            if($clearTablesEachLoop) $tables = [];
            $arr[$n] = $this->withForeignValues($item, $tables, $model);
        }
        return new Collection($arr);
    }

    public function getByX(object|array $params = array(), array $fields = array(), array $ifNotEmpty = []): Collection {
        if(!$this->access(READ_ACTION)) return Methods::toCollection();
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return new Collection();
        $builder = $this->model::queryBuilder()->whereList($params)->select($fields);
        foreach ($ifNotEmpty as $column => $value) {
            if(!empty($value)) $builder->where($column, $value);
        }
        $data = $builder->all();
        $tables = [];
        if($this->includeForeignKeys) $data = $this->withForeignCollection($data, $tables, $this->model, true);
        return !is_null($this->fetchAllCleanMethod) ? call_user_func($this->fetchAllCleanMethod, $data) : $data;
    }
    public function first(object|array $params = array(), array $fields = array()): ?object {
        if(!$this->access(READ_ACTION)) return Methods::toCollection();
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return null;
        $data = $this->model::whereList($params)->select($fields)->first();
        if($this->includeForeignKeys) $data = $this->withForeignValues($data);
        return !is_null($this->fetchCleanMethod) ? call_user_func($this->fetchCleanMethod, $data) : $data;
    }
    public function getFirst(object|array $params = array(), array $fields = array()): ?object {
        return $this->first($params, $fields);
    }
    public function exists(object|array $params = array()): bool {
        if(!$this->access(READ_ACTION)) return false;
        return $this->model::whereList($params)->exists();
    }
    public function create(object|array $params): bool {
        if(!array_key_exists("uid", $params)) {
            $prefix = !is_null($this->model::$uidPrefix) ? $this->model::$uidPrefix . "_" : "";
            while(true) {
                $uid = $prefix . generateUniqueId(15, "STRING_INT");
                if(!$this->exists(['uid' => $uid])) break;
            }
            $params["uid"] = $uid;
        }
        elseif(!empty($this->model::$uidPrefix) && !str_starts_with($params["uid"], $this->model::$uidPrefix))
            $params["uid"] = $this->model::$uidPrefix . "_" . $params["uid"];
        if(!$this->access(MODIFY_ACTION)) return false;
        if(array_key_exists("uid", $params)) $this->recentUid = $params["uid"];
        return $this->model::insert($params);
    }
    public function update(object|array $params, array $identifier, array $whereNot = []): bool {
        if(!$this->access(MODIFY_ACTION)) return false;
        $queryBuilder = $this->model::queryBuilder()->whereList($identifier);
        foreach ($whereNot as $column => $value) $queryBuilder->where($column, "!=", $value);
        return $queryBuilder->update($params);
    }
    public function delete(array $identifier): bool {
        if(!$this->access(MODIFY_ACTION)) return false;
        return $this->model::whereList($identifier)->delete();
    }
    public function get(null|string|int $id, array $fields = array()): ?object {
        if(!$this->access(READ_ACTION)) return null;
        $params = ['uid' => $id];
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return null;
        $data = $this->model::whereList($params)->select($fields)->first();
        if($this->includeForeignKeys) $data = $this->withForeignValues($data);
        return !is_null($this->fetchCleanMethod) ? call_user_func($this->fetchCleanMethod, $data) : $data;
    }
    public function getColumn(object|array $params, string $column = "id"): mixed {
        if(!$this->access(READ_ACTION)) return null;
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return null;
        return $this->model::whereList($params)->getColumn($column);
    }
    public function count(object|array $params = array()): int {
        if(!$this->access(READ_ACTION)) return 0;
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return 0;
        return $this->model::whereList($params)->count();
    }

    public function getByXOrderBy(string $orderColumn, string $orderDirection, object|array $params = array(), array $fields = array()): Collection {
        if(!$this->access(READ_ACTION)) return Methods::toCollection();
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return new Collection();
        $data = $this->model::whereList($params)->select($fields)->order($orderColumn, $orderDirection)->all();
        if($this->includeForeignKeys) $data = $this->withForeignCollection($data);
        return !is_null($this->fetchAllCleanMethod) ? call_user_func($this->fetchAllCleanMethod, $data) : $data;
    }
    public function getFirstOrderBy(string $orderColumn, string $orderDirection, object|array $params = array(), array $fields = array()): ?object {
        if(!$this->access(READ_ACTION)) return Methods::toCollection();
        if(is_callable($this->paramsLogicMethod) && !(($this->paramsLogicMethod)($params))) return null;
        $data = $this->model::whereList($params)->select($fields)->order($orderColumn, $orderDirection)->first();
        if($this->includeForeignKeys) $data = $this->withForeignValues($data);
        return !is_null($this->fetchCleanMethod) ? call_user_func($this->fetchCleanMethod, $data) : $data;
    }

    public function queryGetFirst(QueryBuilder $queryBuilder): ?object {
        $data = $queryBuilder->first();
        if($this->includeForeignKeys) $data = $this->withForeignValues($data);
        return !is_null($this->fetchCleanMethod) ? call_user_func($this->fetchCleanMethod, $data) : $data;
    }
    public function queryGetAll(QueryBuilder $queryBuilder): ?object {
        $data = $queryBuilder->all();
        $tables = [];
        if($this->includeForeignKeys) $data = $this->withForeignCollection($data, $tables, $this->model, true);
        return !is_null($this->fetchAllCleanMethod) ? call_user_func($this->fetchAllCleanMethod, $data) : $data;
    }
    public function queryUpdate(QueryBuilder $predicateQueryBuilder, array $updateParams): bool {
        return $predicateQueryBuilder->update($updateParams);
    }


    public function queryBuilder():QueryBuilder {
        return $this->model->queryBuilder();
    }

}