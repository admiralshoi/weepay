<?php

namespace Database;

use features\Settings;
use JetBrains\PhpStorm\Pure;
use PDO;

class QueryBuilder {
    protected PDO $pdo;
    public string $table;
    protected string $sql = "";
    protected string $countSql;
    protected int $limit;
    protected int $offset;
    protected string $groupBy;
    protected string $order;
    protected string $direction;
    protected array $bindings = [];
    protected array $conditions = [];
    protected array $groupStack = [];
    protected array $delimiterList = [];
    protected bool $inGroup = false;
    protected array $encodedColumns = [];
    protected array $encryptedColumns = [];
    protected string $rawSql;
    protected bool $deadDraw = false;
    protected bool $retainDeadDraw = false;
    protected string $selection;
    protected string $paginationBuilderOrder = "ORDERLY";
    protected string $predicateOffset = "";
    protected array $schema = [];

    public function __construct($pdo, $table, array $encodedColumns = [], array $encryptedColumns = [], array $schema = []) {
        $this->pdo = $pdo;
        $this->table = DB_ENABLE_PREFIX ? DbConnection::getPrefix() . $table : $table;
        $this->table = $this->escapeIdentifier($this->table);
        $this->schema = $schema;
        $this->encodedColumns = $encodedColumns;
        $this->encryptedColumns = $encryptedColumns;
        $this->selection = "SELECT * FROM {$this->table}";
        $this->countSql = "SELECT COUNT(*) AS count FROM {$this->table}";
    }

    public function select(array $columns): static {
        if (empty($columns)) return $this;
        $columnsList = implode(', ', array_map([$this, 'escapeIdentifier'], $columns));
        $this->selection = "SELECT {$columnsList} FROM {$this->table}";
        return $this;
    }

    public function rawSelect(string $sql): static {
        if(!str_starts_with($sql, "SELECT ")) $sql = "SELECT $sql";
        $this->selection = "{$sql} FROM {$this->table}";
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static {
        $columnUnescaped = $column;
        $column = $this->escapeIdentifier($column);
        if ($value === null) {
            $values = $operator;
            $operator = is_array($values) ? "IN" : "=";
        } else {
            $values = $value;
        }

        if(!is_array($values) && in_array($columnUnescaped, $this->encryptedColumns)) $values = DbColumnEncryptor::build($columnUnescaped)->encrypt($values);

        if (is_array($values)) {
            if(empty($values)) {
                $this->deadDraw = true;
                return $this;
            }
            $placeholders = rtrim(str_repeat('?,', count($values)), ',');
            $this->addCondition("{$column} {$operator} ({$placeholders})", $values);
        } elseif (str_starts_with($values, '{{') && str_ends_with($values, '}}')) {
            $referencedColumn = $this->escapeIdentifier(substr($values, 2, -2));
            $this->addCondition("{$column} {$operator} {$referencedColumn}");
        } else {
            $this->addCondition("{$column} {$operator} ?", [$values]);
        }

        return $this;
    }
    public function whereLike(string $column, null|int|string|float $value): static {
        if(in_array($column, $this->encryptedColumns)) $value = DbColumnEncryptor::build($column)->encrypt($value);
        $column = $this->escapeIdentifier($column);
        $value = '%' . $value . '%';
        $this->addCondition("{$column} LIKE ?", [$value]);
        return $this;
    }
    public function whereNotLike(string $column, null|int|string|float $value): static {
        if(in_array($column, $this->encryptedColumns)) $value = DbColumnEncryptor::build($column)->encrypt($value);
        $column = $this->escapeIdentifier($column);
        $value = '%' . $value . '%';
        $this->addCondition("{$column} NOT LIKE ?", [$value]);
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static {
        $columnUnescaped = $column;
        $column = $this->escapeIdentifier($column);
        if ($value === null) {
            $values = $operator;
            $operator = is_array($values) ? "IN" : "=";
        } else {
            $values = $value;
        }
        if(!is_array($values) && in_array($columnUnescaped, $this->encryptedColumns)) $values = DbColumnEncryptor::build($columnUnescaped)->encrypt($values);

        if (is_array($values)) {
            if(empty($values)) {
                $this->deadDraw = true;
                return $this;
            }
            $placeholders = rtrim(str_repeat('?,', count($values)), ',');
            $this->addOrCondition("{$column} {$operator} ({$placeholders})", $values);
        } elseif (str_starts_with($values, '{{') && str_ends_with($values, '}}')) {
            $referencedColumn = substr($values, 2, -2);
            $this->addOrCondition("{$column} {$operator} {$referencedColumn}");
        } else {
            $this->addOrCondition("{$column} {$operator} ?", [$values]);
        }

        return $this;
    }

    public function whereList(array $list): static {
        if (empty($list)) return $this;

        if (isAssoc($list)) {
            foreach ($list as $key => $value) {
                $this->where($key, $value);
            }
        } else {
            foreach ($list as $item) {
                if (!is_array($item)) continue;
                if (count($item) === 2) {
                    $this->where($item[0], $item[1]);
                } elseif (count($item) === 3) {
                    $this->where($item[0], $item[1], $item[2]);
                }
            }
        }
        return $this;
    }


    public function whereRaw(string $condition): static {
        $this->addCondition($condition);
        return $this;
    }

    public function raw(string $sql): static {
        $this->rawSql = $sql;
        return $this;
    }

    public function whereColumnIsNotNull(string $column): static {
        $column = $this->escapeIdentifier($column);
        $this->addCondition("{$column} IS NOT NULL");
        return $this;
    }
    public function whereColumnIsNull(string $column): static {
        $column = $this->escapeIdentifier($column);
        $this->addCondition("{$column} IS NULL");
        return $this;
    }
    public function whereNotNull(string $column): static { return $this->whereColumnIsNotNull($column); } //For stupid AIS that dont understand whereColumnIsNull is its own method...
    public function whereNull(string $column): static { return $this->whereColumnIsNull($column); } //For stupid AIS that dont understand whereColumnIsNull is its own method...
    public function whereColumn(string $column1, string $operator, string $column2): static {
        $column1 = $this->escapeIdentifier($column1);
        $column2 = $this->escapeIdentifier($column2);
        $this->addCondition("{$column1} {$operator} {$column2}");
        return $this;
    }

    public function whereMath(string $column, string $math, string $operator, string|int|float $value): static {
        $column = $this->escapeIdentifier($column);
        if(in_array($column, $this->encryptedColumns)) $value = DbColumnEncryptor::build($column)->encrypt($value);
        if(is_string($value)) $value = $this->escapeString($value);
        $this->addCondition("{$column} {$math} {$operator} {$value}");
        return $this;
    }
    public function whereTimeAfter(string $column, int $unixTimestamp, string $operator = ">"): static {
        $formattedDate = date('Y-m-d H:i:s', $unixTimestamp);
        return $this->where($column, $operator, $formattedDate);
    }

    public function whereTimeBefore(string $column, int $unixTimestamp, string $operator = "<"): static {
        $formattedDate = date('Y-m-d H:i:s', $unixTimestamp);
        return $this->where($column, $operator, $formattedDate);
    }

    public function whereTimeBetween(string $column, int $startTimestamp, int $endTimestamp): static {
        $startDate = date('Y-m-d H:i:s', $startTimestamp);
        $endDate = date('Y-m-d H:i:s', $endTimestamp);
        return $this->where($column, 'BETWEEN', [$startDate, $endDate]);
    }

    public function startGroup(string $type = 'AND'): static {
        $this->groupStack[] = [
            'type' => $type,
            'conditions' => [],
            'delimiter_added' => false
        ];
        $this->inGroup = true;
        return $this;
    }

    public function endGroup(): static {
        if ($this->inGroup) {
            $group = array_pop($this->groupStack);
            $conditions = implode(' ' . $group['type'] . ' ', $group['conditions']);
            if (!empty($this->groupStack)) {
                $this->groupStack[count($this->groupStack) - 1]['conditions'][] = "($conditions)";
            } else {
                $this->conditions[] = "($conditions)";
            }
            $this->inGroup = !empty($this->groupStack);
        }
        return $this;
    }

    protected function addCondition(string $condition, array $values = [], string $type = "AND"): void {
        $delimiterAdded = count($this->conditions) === 0;
        if ($this->inGroup) {
            $this->groupStack[count($this->groupStack) - 1]['conditions'][] = $condition;
            $delimiterAdded = $this->groupStack[count($this->groupStack) - 1]['delimiter_added'];
        } else {
            $this->conditions[] = $condition;
        }
        $this->bindings = array_merge($this->bindings, $values);

        if(!$delimiterAdded) {
            $this->delimiterList[] = $type;
            if ($this->inGroup) $this->groupStack[count($this->groupStack) - 1]['delimiter_added'] = true;
        }
//        if(count($this->conditions) > 1) $this->delimiterList[] = $type;
    }

    protected function addOrCondition(string $condition, array $values = []): void {
        $this->addCondition($condition, $values, "OR");
    }


    public function predicateSql(): string {
        $sqlConditions = "";
        if (!empty($this->conditions)) {
            foreach ($this->conditions as $i => $condition) {
                $sqlConditions .= " $condition";
                if(count($this->conditions) > ($i +1) && array_key_exists($i, $this->delimiterList)) $sqlConditions .= " " . $this->delimiterList[$i];
            }
            $sqlConditions = " WHERE" . $sqlConditions;
        }
        return $sqlConditions;
    }
    public function selectionSql(bool $count = false): string {
        return $count ? $this->countSql : $this->selection;
    }

    public function paginationSql(): string {
        $sql = "";
        if (isset($this->order)) $sql .= " ORDER BY {$this->escapeIdentifier($this->order)} $this->direction";
        if (isset($this->limit)) $sql .= " LIMIT $this->limit";
        if (empty($this->predicateOffset)) {
            if (isset($this->offset)) $sql .= " OFFSET $this->offset";
        }
        return $sql;
    }

    public function predicateOffsetSql(): string {
        return !empty($this->predicateOffset) && isset($this->offset) && isset($this->order)
            ? " {$this->escapeIdentifier($this->order)} $this->predicateOffset $this->offset" : "";
    }
    public function groupSql(): string {
        return isset($this->groupBy) ? " GROUP BY {$this->escapeIdentifier($this->groupBy)}" : "";
    }


    public function toSql(bool $count = false): string {
        if (isset($this->rawSql) && $this->rawSql !== "") {
            $sql = $this->rawSql;
            $this->rawSql  = "";
            return $sql;
        }

        $sql = $this->selectionSql($count);
        $predicateSql = $this->predicateSql();
        if(!$count && !empty($this->predicateOffset)) {
            if(!empty($predicateSql)) $sql .= $predicateSql . " AND " . $this->predicateOffsetSql();
            else $sql .= " " . $this->predicateOffsetSql();
        }
        else $sql .= $predicateSql;
        if(!$count) $sql .= $this->groupSql();
        if(!$count) $sql .= $this->paginationSql();

        return $sql;
    }

    protected function escapeIdentifier(string $identifier): string {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }
    protected function escapeString(string $identifier): string {
        return "'" . str_replace("'", "", $identifier) . "'";
    }

    public function insert(array $data): bool {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql insert, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return false;
        }
        try {
            if (!array_key_exists(0, $data)) {
                $data = [$data];
            }
            $data = $this->removeForeignKeys($data);

            $columns = implode(', ', array_map(function ($name) { return $this->escapeIdentifier($name); }, array_keys($data[0])));
            $placeholders = rtrim(str_repeat('(' . rtrim(str_repeat('?,', count($data[0])), ',') . '),', count($data)), ',');

            $values = [];
            foreach ($this->keyJsonEncoding($data) as $row) {
                $values = array_merge($values, array_values($this->columnEncryption($row)));
            }

            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES {$placeholders}";
            $stmt = $this->pdo->prepare($sql);
            debugLog($sql, 'querybuilder-insert-sql');
            debugLog($values, 'querybuilder-insert-values');
            migrationLog($sql);
            $res = $stmt->execute($values);
            migrationLog("Required rows inserted successfully");
            return $res;
        } catch (\PDOException $e) {
            migrationLog($e->getMessage(), "required rows-insert");
            debugLog($e->getMessage(), "Sql insert, $this->table");
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql insert, $this->table");
            return false;
        }
    }

    public function update(array $dataToUpdate): bool {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql update, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return false;
        }
        try {
            $dataToUpdate = $this->removeForeignKeys($dataToUpdate);
            $dataToUpdate = $this->keyJsonEncoding((object)$dataToUpdate);
            $dataToUpdate = $this->columnEncryption((object)$dataToUpdate);
            $updateColumns = implode('=?, ', array_map(function ($name) { return $this->escapeIdentifier($name); }, array_keys($dataToUpdate))) . '=?';
            $updateSql = "UPDATE {$this->table} SET {$updateColumns}";

            $whereSql = '';
            foreach ($this->conditions as $condition) {
                $whereSql .= "{$condition} AND ";
            }
            $whereSql = rtrim($whereSql, ' AND ');

            $sql = "{$updateSql} WHERE {$whereSql}";
            $bindings = array_merge(array_values($dataToUpdate), $this->bindings);
            debugLog($sql);
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($bindings);
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql update, $this->table");
            return false;
        }
    }

    public function delete(): bool {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql delete, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return false;
        }
        try {
            $deleteSql = "DELETE FROM {$this->table}";

            $whereSql = '';
            foreach ($this->conditions as $condition) {
                $whereSql .= "{$condition} AND ";
            }
            $whereSql = rtrim($whereSql, ' AND ');

            $sql = "{$deleteSql} WHERE {$whereSql}";
            $bindings = $this->bindings;

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($bindings);
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql delete, $this->table");
            return false;
        }
    }

    public function runmeforbackups() {
        $backupDir = ROOT . "bup/db";
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        prettyPrint($tables);

//        foreach ($tables as $table) {
//            if (!str_starts_with($table, "Ts_dIkQ")) continue;
//
//            try {
//                $this->pdo->exec("DROP TABLE `$table`");
//                echo "Dropped table: $table\n";
//            } catch (PDOException $e) {
//                echo "Failed to drop $table: " . $e->getMessage() . "\n";
//            }
//        }
    }

    public function retainDeadDraw(): static {
        $this->retainDeadDraw = true;
        return $this;
    }
    public function discardDeadDraw(): static {
        $this->retainDeadDraw = false;
        return $this;
    }


    public function all(): Collection {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql all, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return $this->emptyList();
        }
        try {
            $sql = $this->toSql();
            debugLog($sql);
            debugLog($this->bindings);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            if (!isEmpty($result)) {
                $result = $this->keyJsonEncoding($result, false);
            }
            if (!isEmpty($result) && !empty($this->encryptedColumns)) {
                $result = $this->columnEncryption(toArray($result), false);
            }

            return new Collection($result);
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql all, $this->table");
            return $this->emptyList();
        }
    }

    public function paginate(
        ?int $limit = null,
        ?int $offset = null,
        ?string $column = null,
        ?string $sortingOrder = null,
        ?string $predicateOffset = null,
        ?string $builderOrder = null,
    ): Collection {
        if(!is_null($limit)) $this->limit($limit);
        if(!is_null($offset)) $this->offset($offset);
        if(!is_null($column)) $this->order($column, $sortingOrder);
        if(!is_null($predicateOffset)) $this->predicateOffset($predicateOffset);
        if(!is_null($builderOrder)) $this->builderOrder($builderOrder);
        return $this->all()->setPagination($this);
    }

    public function first(): ?object {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql first, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        try {
            $this->limit(1);
            $sql = $this->toSql();
            debugLog($sql);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            debugLog($this->bindings, 'queryBuilder-first-bindings');
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if (empty($result)) return null;

            if (!isEmpty($result)) {
                $result = $this->keyJsonEncoding($result, false);
            }
            if (!isEmpty($result) && !empty($this->encryptedColumns)) {
                $result = $this->columnEncryption(toArray($result), false);
            }

            return $result;
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql first, $this->table");
            return null;
        }
    }

    public function count(): int {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql count, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return 0;
        }
        try {
            $sql = $this->toSql(true);
            debugLog($sql, 'count-sql');
            debugLog($this->bindings);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            return $stmt->fetch(PDO::FETCH_OBJ)->count;
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql count, $this->table");
            return 0;
        }
    }

    public function sum(string $column): float|int|null {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql sum, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        try {
            $escapedColumn = $this->escapeIdentifier($column);
            $sql = "SELECT SUM({$escapedColumn}) AS total FROM {$this->table}" . $this->predicateSql();
            debugLog($sql, 'sum-sql');
            debugLog($this->bindings, 'sum-bindings');
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result->total !== null ? (float)$result->total : null;
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql sum, $this->table");
            return null;
        }
    }

    public function avg(string $column): float|int|null {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql avg, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        try {
            $escapedColumn = $this->escapeIdentifier($column);
            $sql = "SELECT AVG({$escapedColumn}) AS average FROM {$this->table}" . $this->predicateSql();
            debugLog($sql, 'avg-sql');
            debugLog($this->bindings, 'avg-bindings');
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result->average !== null ? (float)$result->average : null;
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql avg, $this->table");
            return null;
        }
    }

    public function min(string $column): float|int|string|null {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql min, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        try {
            $escapedColumn = $this->escapeIdentifier($column);
            $sql = "SELECT MIN({$escapedColumn}) AS minimum FROM {$this->table}" . $this->predicateSql();
            debugLog($sql, 'min-sql');
            debugLog($this->bindings, 'min-bindings');
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result->minimum;
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql min, $this->table");
            return null;
        }
    }

    public function max(string $column): float|int|string|null {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql max, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        try {
            $escapedColumn = $this->escapeIdentifier($column);
            $sql = "SELECT MAX({$escapedColumn}) AS maximum FROM {$this->table}" . $this->predicateSql();
            debugLog($sql, 'max-sql');
            debugLog($this->bindings, 'max-bindings');
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result->maximum;
        } catch (\PDOException $e) {
            errorLog($e->getMessage() . "  -   " . $e->getTraceAsString(), "Sql max, $this->table");
            return null;
        }
    }

    public function getColumn(string $column): mixed {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql column, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        if (empty($column)) return null;
        $row = $this->first();
        return isEmpty($row) ? $row : $row->$column;
    }


    public function pluck(string $column): ?array {
        if($this->deadDraw) {
            debugLog("Dead draw", "Sql column, $this->table");
            $this->deadDraw = $this->retainDeadDraw ? $this->deadDraw : false;
            return null;
        }
        if (empty($column)) return null;
        $this->select([$column]);
        $rows = $this->all();
        return $rows->empty() ? [] : $rows->map(function ($row) use  ($column) { return $row[$column]; })->toArray();
    }

    public function limit(int $limit): static {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static {
        $this->offset = $offset;
        return $this;
    }

    public function order(string $order, ?string $direction = "ASC"): static {
        $this->order = $order;
        if(!is_null($direction)) $this->direction = $direction;
        return $this;
    }

    public function predicateOffset(string $operator): static {
        $this->predicateOffset = $operator;
        return $this;
    }

    public function builderOrder(string $buildOrder): static {
        $this->paginationBuilderOrder = $buildOrder;
        return $this;
    }
    public function builderOrderOrderly(): static {
        $this->paginationBuilderOrder = "ORDERLY";
        return $this;
    }
    public function builderOrderDynamic(): static {
        $this->paginationBuilderOrder = "DYNAMIC";
        return $this;
    }

    public function groupBy(string $column): static {
        $this->groupBy = $column;
        return $this;
    }

    public function exists(): bool {
        return $this->count() > 0;
    }

    public function asSql(): string { return $this->toSql(); }


    private function removeForeignKeys(array $dataToInsertOrUpdate): array {
        $result = [];
        if(!isAssoc($dataToInsertOrUpdate)) {
            foreach ($dataToInsertOrUpdate as $i => $item) $result[$i] = $this->removeForeignKeys($item);
        }
        else {
            foreach ($dataToInsertOrUpdate as $key => $value) {
                if(array_key_exists($key, $this->schema)) $result[$key] = $value;
            }
        }
        return $result;
    }


    //Not for arrays
    private function columnEncryption(array|object $data, bool $encode = true): object|array {
        if (is_object($data) || (is_array($data) && isAssoc($data))) return $this->encryptColumn($data, $encode);

        $collector = [];
        foreach ($data as $item) {
            if (is_object($item) || is_array($item)) {
                $collector[] = $this->encryptColumn($item, $encode);
            } else {
                $collector[] = $item;
            }
        }
        return $encode ? $collector : toObject($collector);
    }
    private function encryptColumn(object|array $data, bool $encode = true): object|array {
        if (is_array($data)) $data = toObject($data);
        if (empty($data)) return $data;
        foreach ($this->encryptedColumns as $key) {
            $method = DbColumnEncryptor::build($key);
            if(!property_exists($data, $key)) continue;
            if((is_array($data->$key) || is_object($data->$key))) continue;
            if($encode) $data->$key = $method->encrypt($data->$key);
            else $data->$key = $method->decrypt($data->$key);
        }

        return $encode ? toArray($data) : $data;
    }

    private function keyJsonEncoding(array|object $data, bool $encode = true): object|array {
        if (is_object($data) || (is_array($data) && isAssoc($data))) return $this->keyEncoding($data, $encode);

        $collector = [];
        foreach ($data as $item) {
            if (is_object($item) || is_array($item)) {
                $collector[] = $this->keyEncoding($item, $encode);
            } else {
                $collector[] = $item;
            }
        }
        return $encode ? $collector : toObject($collector);
    }

    private function keyEncoding(object|array $data, bool $encode = true): object|array {
        if (is_array($data)) $data = toObject($data);
        if (empty($data)) return $data;
        foreach ($this->encodedColumns as $key) {
            if (
                property_exists($data, $key) &&
                $encode &&
                (is_array($data->$key) || is_object($data->$key))
            ) $data->$key = base64_encode(json_encode($data->$key));
            if (
                property_exists($data, $key) &&
                !$encode &&
                (!is_array($data->$key) && !is_object($data->$key)) &&
                !empty($data->$key)
            ) $data->$key = json_decode(base64_decode($data->$key));
        }
        return $encode ? toArray($data) : $data;
    }

    #[Pure] public function emptyList(): Collection { return new Collection(); }



    /*
        Below is pagination stuff
    */

    public function setPaginationCursor(string $cursor): ?static {
        $data = json_decode(base64_decode(encrypt($cursor, true)), true);
        debugLog($data, "decoded-cursor");
        if(!is_array($data)) return new static($this->pdo, $this->table, $this->encodedColumns, $this->encryptedColumns, $this->schema);
        foreach ([
            "build_method",
            "limit",
            "offset", "column",
            "direction",
            "predicate_offset",
            "predicate_conditions",
            "predicate_delimiters",
            "predicate_bindings",
            "selection",
         ] as $key) if(!array_key_exists($key, $data)) return new static($this->pdo, $this->table, $this->encodedColumns, $this->encryptedColumns, $this->schema);

        $this->setPaginationData(
            $data["build_method"],
            $data["limit"],
            $data["offset"],
            $data["column"],
            $data["direction"],
            $data["predicate_offset"],
            $data["predicate_conditions"],
            $data["predicate_delimiters"],
            $data["predicate_bindings"],
        );
        $this->selection = $data["selection"];
        return $this;
    }

    public function setPaginationData(
        ?string $buildMethod,
        ?int $limit = null,
        ?int $offset = null,
        ?string $orderColumn = null,
        ?string $orderDirection = null,
        ?string $predicateOffset = null,
        ?array $predicateConditions = null,
        ?array $predicateDelimiterList = null,
        ?array $predicateBindings = null,
    ): static {
        if(!is_null($buildMethod)) $this->paginationBuilderOrder = $buildMethod;
        if(!is_null($limit)) $this->limit($limit);
        if(!is_null($offset)) $this->offset($offset);
        if(!is_null($orderColumn)) $this->order($orderColumn, $orderDirection);
        if(!is_null($predicateOffset)) $this->predicateOffset($predicateOffset);
        if(!is_null($predicateConditions)) $this->conditions = $predicateConditions;
        if(!is_null($predicateDelimiterList)) $this->delimiterList = $predicateDelimiterList;
        if(!is_null($predicateBindings)) $this->bindings = $predicateBindings;
        return $this;
    }

    public function getLimit(): ?int { return isset($this->limit) ? $this->limit : null; }
    public function getOffset(): ?int { return isset($this->offset) ? $this->offset : null; }
    public function getOrderColumn(): ?string { return isset($this->order) ? $this->order : null; }
    public function getDirection(): ?string { return isset($this->direction) ? $this->direction : null; }
    public function getBuildOrder(): ?string { return isset($this->paginationBuilderOrder) ? $this->paginationBuilderOrder : null; }
    public function getPredicateOffset(): string { return $this->predicateOffset; }
    public function getPredicateConditions(): array { return $this->conditions; }
    public function getPredicateDelimiters(): array { return $this->delimiterList; }
    public function getPredicateBindings(): array { return $this->bindings; }

    public function assemble(
        int $limit,
        int $offset,
        string $order,
        string $column,
        string $selection,
        string $builderOrder,
        string $predicateOffset,
        array $predicateConditions,
        array $predicateDelimiters,
        array $predicateBindings,
    ): static {
        $static = new static($this->pdo, $this->table, $this->encodedColumns, $this->encryptedColumns, $this->schema);
        $static->limit = $limit;
        $static->offset = $offset;
        $static->order($column, $order);
        $static->selection = $selection;
        $static->paginationBuilderOrder = $builderOrder;
        $static->predicateOffset = $predicateOffset;
        $static->conditions = $predicateConditions;
        $static->delimiterList = $predicateDelimiters;
        $static->bindings = $predicateBindings;
        return $static;
    }
}
