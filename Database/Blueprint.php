<?php
namespace Database;

use PDO;

class Blueprint {
    protected string $table;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $uniques = [];
    protected array $foreignKeys = [];
    protected array $existingColumns = [];
    protected array $schema = [];
    protected array $existingIndexes = [];
    protected array $existingUniques = [];
    protected array $existingForeignKeys = [];
    protected PDO $pdo;
    protected bool $useForeignKeys = true; // Toggle for foreign keys

    public function __construct(string $table, PDO $pdo) {
        $this->table = $table;
        $this->pdo = $pdo;
        $this->addStaticColumns();
    }

    public function setUseForeignKeys(bool $useForeignKeys): self {
        $this->useForeignKeys = $useForeignKeys;
        migrationLog("Foreign keys " . ($useForeignKeys ? "enabled" : "disabled") . " for table: {$this->table}");
        return $this;
    }

    protected function addStaticColumns(): void {
        $this->columns['id'] = [
            "schema" => ["type" => "primary"],
            "definition" => "{$this->primary()}"
        ];
        $this->columns['created_at'] = [
            "schema" => ["type" => "timestamp"],
            "definition" => "{$this->timestamp()} DEFAULT CURRENT_TIMESTAMP"
        ];
        $this->columns['updated_at'] = [
            "schema" => ["type" => "timestamp"],
            "definition" => "{$this->timestamp()} DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
    }


    protected function fetchExistingColumns(): void {
        $stmt = $this->pdo->prepare("DESCRIBE {$this->escapeIdentifier($this->table)}");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $column) {
            $existingColumn = array_merge(
                $column,
                [
                    "Comment" => $this->getCommentForColumn($column["Field"]),
                    "Nullable" => $column["Null"] === "YES"
                ]
            );

            if (stripos($column['Type'], 'enum') === 0) {
                $enumValues = $this->getEnumValues($column['Field']);
                $existingColumn['EnumValues'] = $enumValues;
            }

            $this->existingColumns[$column['Field']] = $existingColumn;
        }
    }

    protected function getCommentForColumn(string $column): ?string {
        return $this->pdo->query("SELECT COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = {$this->pdo->quote($this->table)} AND COLUMN_NAME = {$this->pdo->quote($column)}")->fetchColumn();
    }

    protected function getEnumValues(string $column): array {
        $stmt = $this->pdo->prepare("
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = :table AND COLUMN_NAME = :column
    ");
        $stmt->execute(['table' => $this->table, 'column' => $column]);
        $columnType = $stmt->fetchColumn();

        if (preg_match("/^enum\((.+)\)$/i", $columnType, $matches)) {
            $values = array_map(
                fn($value) => trim($value, "'"),
                explode(',', str_replace("'", "", $matches[1]))
            );
            return $values;
        }
        return [];
    }

//    protected function fetchExistingColumns(): void {
//        $stmt = $this->pdo->prepare("DESCRIBE {$this->table}");
//        $stmt->execute();
//        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
//
//        foreach ($columns as $column) {
//            $existingColumn = array_merge(
//                $column,
//                [
//                    "Comment" => $this->getCommentForColumn($column["Field"]),
//                    "Nullable" => $column["Null"] === "YES"
//                ]
//            );
//
//            // Fetch ENUM values if the column is ENUM
//            if (stripos($column['Type'], 'enum') === 0) {
//                $enumValues = $this->getEnumValues($column['Field']);
//                $existingColumn['EnumValues'] = $enumValues;
//            }
//
//            $this->existingColumns[$column['Field']] = $existingColumn;
//        }
//    }
//
//
//    protected function getCommentForColumn(string $column): ?string {
//        return $this->pdo->query("SELECT COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$this->table}' AND COLUMN_NAME = '{$column}'")->fetchColumn();
//    }
//
//    protected function getEnumValues(string $column): array {
//        $stmt = $this->pdo->prepare("
//            SELECT COLUMN_TYPE
//            FROM INFORMATION_SCHEMA.COLUMNS
//            WHERE TABLE_NAME = :table AND COLUMN_NAME = :column
//        ");
//        $stmt->execute(['table' => $this->table, 'column' => $column]);
//        $columnType = $stmt->fetchColumn();
//
//        // Extract ENUM values (e.g., enum('PENDING','COMPLETED') -> ['PENDING', 'COMPLETED'])
//        if (preg_match("/^enum\((.+)\)$/i", $columnType, $matches)) {
//            $values = array_map(
//                fn($value) => trim($value, "'"),
//                explode(',', str_replace("'", "", $matches[1]))
//            );
//            return $values;
//        }
//        return [];
//    }

    public function setData(array $schema, array $indexes, array $uniques, array $foreignKeys = [], bool $isCreate = false): void {
        $this->schema = $schema;
        foreach ($this->schema as $column => $type) {
            $this->addColumn($column, $type);
        }

        foreach ($indexes as $indexColumn) {
            $this->index('idx_' . $indexColumn, [$indexColumn]);
        }

        foreach ($uniques as $uniqueColumn) {
            $this->unique('uq_' . $uniqueColumn, [$uniqueColumn]);
        }

        if ($this->useForeignKeys) {
            foreach ($foreignKeys as $column => $reference) {
                $this->foreign("fk_{$column}_{$this->table}", $column, $reference);
            }
        } else {
            migrationLog("Skipping foreign key definitions for table: {$this->table} due to useForeignKeys=false");
        }

        if (!$isCreate) {
            migrationLog("Fetching existing columns, indexes, uniques, and foreign keys for table: {$this->table}");
            $this->fetchExistingColumns();
            $this->fetchExistingIndexesAndUniques();
        } else {
            migrationLog("Skipping fetch of existing columns, indexes, uniques, and foreign keys for table creation: {$this->table}");
        }
    }

    protected function fetchExistingIndexesAndUniques(): void {
        $stmt = $this->pdo->prepare("SHOW INDEXES FROM {$this->escapeIdentifier($this->table)}");
        $stmt->execute();
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($indexes as $index) {
            $indexName = $index['Key_name'];
            $columnName = $index['Column_name'];
            if ($index['Non_unique'] == 0 && $indexName !== 'PRIMARY') {
                $this->existingUniques[$indexName][] = $columnName;
            } else {
                $this->existingIndexes[$indexName][] = $columnName;
            }
        }

        $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn();
        $stmt = $this->pdo->prepare("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :dbName AND TABLE_NAME = :table AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
        $stmt->execute(['dbName' => $dbName, 'table' => $this->table]);
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($foreignKeys as $fk) {
            $this->existingForeignKeys[$fk['CONSTRAINT_NAME']] = [
                'column' => $fk['COLUMN_NAME'],
                'referenced_table' => $fk['REFERENCED_TABLE_NAME'],
                'referenced_column' => $fk['REFERENCED_COLUMN_NAME']
            ];
        }
    }

//    protected function fetchExistingIndexesAndUniques(): void {
//        // Fetch indexes and uniques
//        $stmt = $this->pdo->prepare("SHOW INDEXES FROM {$this->table}");
//        $stmt->execute();
//        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
//
//        foreach ($indexes as $index) {
//            $indexName = $index['Key_name'];
//            $columnName = $index['Column_name'];
//            if ($index['Non_unique'] == 0 && $indexName !== 'PRIMARY') {
//                $this->existingUniques[$indexName][] = $columnName;
//            } else {
//                $this->existingIndexes[$indexName][] = $columnName;
//            }
//        }
//
//        // Fetch foreign keys
//        $stmt = $this->pdo->prepare("
//            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
//            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
//            WHERE TABLE_NAME = :table AND CONSTRAINT_NAME != 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL
//        ");
//        $stmt->execute(['table' => $this->table]);
//        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
//
//        foreach ($foreignKeys as $fk) {
//            $this->existingForeignKeys[$fk['CONSTRAINT_NAME']] = [
//                'column' => $fk['COLUMN_NAME'],
//                'referenced_table' => $fk['REFERENCED_TABLE_NAME'],
//                'referenced_column' => $fk['REFERENCED_COLUMN_NAME']
//            ];
//        }
//    }


    public function columnsEqual(array|string $column, array $existingColumn): bool {
        $method = is_array($column) ? $column["type"] : $column;
        $params = [];

        if (is_array($column)) {
            // Check ENUM values first, as type may be the same but values differ
            if ($method === 'enum' && isset($column['values']) && isset($existingColumn['EnumValues'])) {
                $schemaValues = $column['values'];
                $existingValues = $existingColumn['EnumValues'];
                if ($schemaValues !== $existingValues) {
                    debugLog("ENUM values differ for column {$existingColumn['Field']}: schema=[" . implode(',', $schemaValues) . "], existing=[" . implode(',', $existingValues) . "]");
                    migrationLog("ENUM values differ for column {$existingColumn['Field']}: schema=[" . implode(',', $schemaValues) . "], existing=[" . implode(',', $existingValues) . "]");
                    return false;
                }
            }

            if ($method === 'enum' || $method === 'set') {
                $params = [$column['values'] ?? []];
            } elseif ($method === 'string' || $method === 'char') {
                $params = [$column['length'] ?? 255];
            } elseif ($method === 'decimal' || $method === 'float' || $method === 'double') {
                $params = [$column['precision'] ?? 10, $column['scale'] ?? 2];
            }
        }

        $columnType = call_user_func([$this, $method], ...$params);
        $existingColumnType = strtolower($existingColumn["Type"]);

        if (str_contains($existingColumnType, "(") && !str_contains($columnType, "(")) {
            $existingColumnType = substr($existingColumnType, 0, strpos($existingColumnType, "("));
        }

        if (strtolower($columnType) !== $existingColumnType) {
            migrationLog("Column type differs for {$existingColumn['Field']}: schema=$columnType, existing=$existingColumnType");
            return false;
        }

        if (is_array($column)) {
            $nullable = array_key_exists("nullable", $column) && $column["nullable"];
            if ($existingColumn["Nullable"] !== $nullable) {
                migrationLog("Nullable differs for {$existingColumn['Field']}: schema=" . ($nullable ? 'NULL' : 'NOT NULL') . ", existing=" . ($existingColumn["Nullable"] ? 'NULL' : 'NOT NULL'));
                return false;
            }
            if (array_key_exists("default", $column) && (string)$column["default"] !== (string)$existingColumn["Default"]) {
                migrationLog("Default differs for {$existingColumn['Field']}: schema=" . ($column["default"] ?? 'NULL') . ", existing=" . ($existingColumn["Default"] ?? 'NULL'));
                return false;
            }
            if (array_key_exists("comment", $column) && $column["comment"] !== $existingColumn["Comment"]) {
                migrationLog("Comment differs for {$existingColumn['Field']}: schema=" . ($column["comment"] ?? '') . ", existing=" . ($existingColumn["Comment"] ?? ''));
                return false;
            }
        }

        migrationLog("Columns equal for {$existingColumn['Field']} with type $columnType");
        return true;
    }

    public function addColumn(string $name, $definition): void {
        $type = is_array($definition) ? $definition['type'] : $definition;
        $default = '';
        $nullable = ' NOT NULL';
        $comment = '';
        $values = [];
        $length = 255;
        $precision = 10;
        $scale = 2;

        if (is_string($definition) && $type === 'enum') {
            throw new \Exception("Enum column $name must be defined as an array with a 'values' key");
        }

        if (is_array($definition)) {
            if (isset($definition['default'])) {
                if (str_contains(strtolower($type), "integer") && is_numeric($definition["default"])) {
                    $default = " DEFAULT {$definition['default']}";
                } elseif ($definition['default'] === null) {
                    $default = " DEFAULT NULL";
                } else {
                    $default = " DEFAULT '{$definition['default']}'";
                }
            }
            if (isset($definition['nullable']) && $definition['nullable']) {
                $nullable = " NULL";
            }
            if (isset($definition['comment'])) {
                $comment = " COMMENT '{$definition['comment']}'";
            }
            if (($type === 'enum' || $type === 'set') && isset($definition['values'])) {
                $values = $definition['values'];
            }
            if (($type === 'string' || $type === 'char') && isset($definition['length'])) {
                $length = $definition['length'];
            }
            if (($type === 'decimal' || $type === 'float' || $type === 'double') && isset($definition['precision'])) {
                $precision = $definition['precision'];
            }
            if (($type === 'decimal' || $type === 'float' || $type === 'double') && isset($definition['scale'])) {
                $scale = $definition['scale'];
            }
        }

        $method = $type;
        if (method_exists($this, $method)) {
            if ($type === 'enum' || $type === 'set') {
                if (empty($values) || !is_array($values) || !array_reduce($values, fn($carry, $item) => $carry && is_string($item), true)) {
                    throw new \Exception("ENUM or SET column $name requires a non-empty array of string values");
                }
                $methodType = call_user_func([$this, $method], $values);
            } elseif ($type === 'string' || $type === 'char') {
                $methodType = call_user_func([$this, $method], $length);
            } elseif ($type === 'decimal' || $type === 'float' || $type === 'double') {
                $methodType = call_user_func([$this, $method], $precision, $scale);
            } else {
                $methodType = call_user_func([$this, $method]);
            }
            $this->columns[$name] = [
                "schema" => $definition,
                "definition" => "{$methodType}{$default}{$nullable}{$comment}"
            ];
            migrationLog("Added column: $name with type $type" . ($type === 'enum' ? " and values [" . implode(',', $values) . "]" : ""));
        } else {
            throw new \Exception("Method $method does not exist.");
        }
    }

    public function index(string $name, array $columns): void {
        $escapedColumns = array_map([$this, 'escapeIdentifier'], $columns);
        $this->indexes[$name] = "INDEX {$this->escapeIdentifier($name)} (" . implode(', ', $escapedColumns) . ")";
    }

    public function unique(string $name, array $columns): void {
        $escapedColumns = array_map([$this, 'escapeIdentifier'], $columns);
        $this->uniques[$name] = "UNIQUE {$this->escapeIdentifier($name)} (" . implode(', ', $escapedColumns) . ")";
    }

    public function foreign(string $name, string $column, array $reference): void {
        list($target, $static) = $reference;
        [$referencedTable, $referencedColumn] = explode('.', $target);
        $this->foreignKeys[$name] = [
            'column' => $column,
            'referenced_table' => $referencedTable,
            'referenced_column' => $referencedColumn,
            'definition' => "FOREIGN KEY ({$this->escapeIdentifier($column)}) REFERENCES {$this->escapeIdentifier($referencedTable)}({$this->escapeIdentifier($referencedColumn)}) ON DELETE RESTRICT ON UPDATE RESTRICT"
        ];
        migrationLog("Defined foreign key: $name on $column referencing $referencedTable.$referencedColumn");
    }

//    public function index(string $name, array $columns): void {
//        $this->indexes[$name] = "INDEX (" . implode(', ', $columns) . ")";
//    }
//
//    public function unique(string $name, array $columns): void {
//        $this->uniques[$name] = "UNIQUE (" . implode(', ', $columns) . ")";
//    }

//    public function foreign(string $name, string $column, array $reference): void {
//        // Parse reference (e.g., "customers.uid")
//        list($target, $static) = $reference;
//        [$referencedTable, $referencedColumn] = explode('.', $target);
//        $this->foreignKeys[$name] = [
//            'column' => $column,
//            'referenced_table' => $referencedTable,
//            'referenced_column' => $referencedColumn,
//            'definition' => "FOREIGN KEY ($column) REFERENCES $referencedTable($referencedColumn) ON DELETE RESTRICT ON UPDATE RESTRICT"
//        ];
//        migrationLog("Defined foreign key: $name on $column referencing $referencedTable.$referencedColumn");
//    }

    protected function escapeIdentifier(string $identifier): string {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }

    public function toCreationSql(): string {
        $columnsSql = array_map(
            fn($definition, $name) => "{$this->escapeIdentifier($name)} {$definition}",
            array_column($this->columns, "definition"),
            array_keys($this->columns)
        );
        $columnsSql = implode(', ', $columnsSql);
        $indexesSql = !empty($this->indexes) ? ', ' . implode(', ', $this->indexes) : '';
        $uniquesSql = !empty($this->uniques) ? ', ' . implode(', ', $this->uniques) : '';
        $foreignKeysSql = $this->useForeignKeys && !empty($this->foreignKeys) ? ', ' . implode(', ', array_column($this->foreignKeys, 'definition')) : '';
        $sql = "CREATE TABLE {$this->escapeIdentifier($this->table)} ({$columnsSql}{$indexesSql}{$uniquesSql}{$foreignKeysSql})";
        migrationLog("Generated CREATE TABLE SQL: $sql");
        return $sql;
    }

//    public function toCreationSql(): string {
//        $columnsSql = array_map(fn($definition, $name) => "{$name} {$definition}", array_column($this->columns, "definition"), array_keys($this->columns));
//        $columnsSql = implode(', ', $columnsSql);
//        $indexesSql = !empty($this->indexes) ? ', ' . implode(', ', $this->indexes) : '';
//        $uniquesSql = !empty($this->uniques) ? ', ' . implode(', ', $this->uniques) : '';
//        $foreignKeysSql = $this->useForeignKeys && !empty($this->foreignKeys) ? ', ' . implode(', ', array_column($this->foreignKeys, 'definition')) : '';
//        $sql = "CREATE TABLE {$this->table} ({$columnsSql}{$indexesSql}{$uniquesSql}{$foreignKeysSql})";
//        migrationLog("Generated CREATE TABLE SQL: $sql");
//        return $sql;
//    }

    public function toAlterSql(): ?string {
        $alterations = [];
        if (empty($this->columns)) return null;

        foreach ($this->columns as $column => $columnItem) {
            if (in_array($column, ["id", "updated_at", "created_at"])) continue;
            if (!isset($this->existingColumns[$column])) {
                $alterations[] = "ADD COLUMN {$this->escapeIdentifier($column)} {$columnItem['definition']}";
                migrationLog("Adding new column: $column");
            } elseif (!$this->columnsEqual($columnItem['schema'], $this->existingColumns[$column])) {
                if (is_array($columnItem['schema']) && $columnItem['schema']['type'] === 'enum' && isset($columnItem['schema']['values']) && isset($this->existingColumns[$column]['EnumValues'])) {
                    $newValues = $columnItem['schema']['values'];
                    $existingValues = $this->existingColumns[$column]['EnumValues'];
                    $removedValues = array_values(array_diff($existingValues, $newValues));
                    $addedValues = array_values(array_diff($newValues, $existingValues));
                    if (!empty($removedValues) || !empty($addedValues)) {
                        debugLog("ENUM changes for column $column: added=[" . implode(',', $addedValues) . "], removed=[" . implode(',', $removedValues) . "]");
                        migrationLog("ENUM changes for column $column: added=[" . implode(',', $addedValues) . "], removed=[" . implode(',', $removedValues) . "]");
                        if (!empty($removedValues)) {
                            $count = Model::queryBuilder()->where($column, $removedValues)->count();
                            if ($count > 0) {
                                migrationLog("Cannot modify ENUM column $column: $count rows contain removed values [" . implode(',', $removedValues) . "]", "error");
                                throw new \Exception("Cannot modify ENUM column $column: contains removed values");
                            }
                        }
                    }
                }
                $alterations[] = "MODIFY COLUMN {$this->escapeIdentifier($column)} {$columnItem['definition']}";
                migrationLog("Modifying column: $column due to differences in definition or ENUM values");
            }
        }

        foreach ($this->existingColumns as $column => $definition) {
            if (in_array($column, ["id", "updated_at", "created_at"])) continue;
            if (!isset($this->columns[$column])) {
                $alterations[] = "DROP COLUMN {$this->escapeIdentifier($column)}";
                migrationLog("Dropping column: $column");
            }
        }

        foreach ($this->indexes as $name => $definition) {
            $comparingIdxName = str_replace("idx_", "", $name);
            if ($name !== 'PRIMARY') {
                if (!isset($this->existingIndexes[$comparingIdxName])) {
                    $alterations[] = "ADD {$definition}";
                    migrationLog("Adding index: $name");
                }
            }
        }

        foreach ($this->existingIndexes as $name => $columns) {
            if (!isset($this->indexes["idx_$name"]) && $name !== 'PRIMARY') {
                $alterations[] = "DROP INDEX {$this->escapeIdentifier($name)}";
                migrationLog("Dropping index: $name");
            }
        }

        foreach ($this->uniques as $name => $definition) {
            $comparingUnqName = str_replace("uq_", "", $name);
            if (!isset($this->existingUniques[$comparingUnqName])) {
                $alterations[] = "ADD {$definition}";
                migrationLog("Adding unique constraint: $name");
            }
        }

        foreach ($this->existingUniques as $name => $columns) {
            if (!isset($this->uniques["uq_$name"])) {
                $alterations[] = "DROP INDEX {$this->escapeIdentifier($name)}";
                migrationLog("Dropping unique constraint: $name");
            }
        }

        if ($this->useForeignKeys) {
            foreach ($this->foreignKeys as $name => $fk) {
                if (!isset($this->existingForeignKeys[$name])) {
                    $alterations[] = "ADD {$fk['definition']}";
                    migrationLog("Adding foreign key: $name");
                } elseif (
                    $this->existingForeignKeys[$name]['column'] !== $fk['column'] ||
                    $this->existingForeignKeys[$name]['referenced_table'] !== $fk['referenced_table'] ||
                    $this->existingForeignKeys[$name]['referenced_column'] !== $fk['referenced_column']
                ) {
                    $alterations[] = "DROP FOREIGN KEY {$this->escapeIdentifier($name)}";
                    $alterations[] = "ADD {$fk['definition']}";
                    migrationLog("Modifying foreign key: $name");
                }
            }

            foreach ($this->existingForeignKeys as $name => $fk) {
                if (!isset($this->foreignKeys[$name])) {
                    $alterations[] = "DROP FOREIGN KEY {$this->escapeIdentifier($name)}";
                    migrationLog("Dropping foreign key: $name");
                }
            }
        }

        if (!empty($alterations)) {
            $sql = "ALTER TABLE {$this->escapeIdentifier($this->table)} " . implode(', ', $alterations);
            migrationLog("Generated ALTER TABLE SQL: $sql");
            return $sql;
        }

        migrationLog("No alterations needed for table: {$this->table}");
        return null;
    }

//    public function toAlterSql(): ?string {
//        $alterations = [];
//        if (empty($this->columns)) return null;
//
//        foreach ($this->columns as $column => $columnItem) {
//            if (in_array($column, ["id", "updated_at", "created_at"])) continue;
//            if (!isset($this->existingColumns[$column])) {
//                $alterations[] = "ADD COLUMN {$column} {$columnItem['definition']}";
//                migrationLog("Adding new column: $column");
//            } elseif (!$this->columnsEqual($columnItem['schema'], $this->existingColumns[$column])) {
//                // Check for ENUM data integrity before modifying
//                if (is_array($columnItem['schema']) && $columnItem['schema']['type'] === 'enum' && isset($columnItem['schema']['values']) && isset($this->existingColumns[$column]['EnumValues'])) {
//                    $newValues = $columnItem['schema']['values'];
//                    $existingValues = $this->existingColumns[$column]['EnumValues'];
//                    $removedValues = array_values(array_diff($existingValues, $newValues));
//                    $addedValues = array_values(array_diff($newValues, $existingValues));
//                    if (!empty($removedValues) || !empty($addedValues)) {
//                        debugLog("ENUM changes for column $column: added=[" . implode(',', $addedValues) . "], removed=[" . implode(',', $removedValues) . "]");
//                        migrationLog("ENUM changes for column $column: added=[" . implode(',', $addedValues) . "], removed=[" . implode(',', $removedValues) . "]");
//                        if (!empty($removedValues)) {
//                            $count = Model::queryBuilder()->where($column, $removedValues)->count();
//                            if ($count > 0) {
//                                migrationLog("Cannot modify ENUM column $column: $count rows contain removed values [" . implode(',', $removedValues) . "]", "error");
//                                throw new \Exception("Cannot modify ENUM column $column: contains removed values");
//                            }
//                        }
//                    }
//                }
//                $alterations[] = "MODIFY COLUMN {$column} {$columnItem['definition']}";
//                migrationLog("Modifying column: $column due to differences in definition or ENUM values");
//            }
//        }
//
//        // Check for columns in database that are not in schema (to drop them)
//        foreach ($this->existingColumns as $column => $definition) {
//            if (in_array($column, ["id", "updated_at", "created_at"])) continue;
//            if (!isset($this->columns[$column])) {
//                $alterations[] = "DROP COLUMN {$column}";
//                migrationLog("Dropping column: $column");
//            }
//        }
//
//        foreach ($this->indexes as $name => $definition) {
//            $comparingIdxName = str_replace("idx_", "", $name);
//            if ($name !== 'PRIMARY') {
//                if (!isset($this->existingIndexes[$comparingIdxName])) {
//                    $alterations[] = "ADD {$definition}";
//                    migrationLog("Adding index: $name");
//                }
//            }
//        }
//
//        foreach ($this->existingIndexes as $name => $columns) {
//            if (!isset($this->indexes["idx_$name"]) && $name !== 'PRIMARY') {
//                $alterations[] = "DROP INDEX {$name}";
//                migrationLog("Dropping index: $name");
//            }
//        }
//
//        // Track unique constraint changes
//        foreach ($this->uniques as $name => $definition) {
//            $comparingUnqName = str_replace("uq_", "", $name);
//            if (!isset($this->existingUniques[$comparingUnqName])) {
//                $alterations[] = "ADD {$definition}";
//                migrationLog("Adding unique constraint: $name");
//            }
//        }
//
//        foreach ($this->existingUniques as $name => $columns) {
//            if (!isset($this->uniques["uq_$name"])) {
//                $alterations[] = "DROP INDEX {$name}";
//                migrationLog("Dropping unique constraint: $name");
//            }
//        }
//
//        // Track foreign key changes (only if useForeignKeys is true)
//        if ($this->useForeignKeys) {
//            foreach ($this->foreignKeys as $name => $fk) {
//                if (!isset($this->existingForeignKeys[$name])) {
//                    $alterations[] = "ADD {$fk['definition']}";
//                    migrationLog("Adding foreign key: $name");
//                } elseif (
//                    $this->existingForeignKeys[$name]['column'] !== $fk['column'] ||
//                    $this->existingForeignKeys[$name]['referenced_table'] !== $fk['referenced_table'] ||
//                    $this->existingForeignKeys[$name]['referenced_column'] !== $fk['referenced_column']
//                ) {
//                    $alterations[] = "DROP FOREIGN KEY {$name}";
//                    $alterations[] = "ADD {$fk['definition']}";
//                    migrationLog("Modifying foreign key: $name");
//                }
//            }
//
//            foreach ($this->existingForeignKeys as $name => $fk) {
//                if (!isset($this->foreignKeys[$name])) {
//                    $alterations[] = "DROP FOREIGN KEY {$name}";
//                    migrationLog("Dropping foreign key: $name");
//                }
//            }
//        }
////        else {
////            // If foreign keys are disabled, drop all existing foreign keys
////            foreach ($this->existingForeignKeys as $name => $fk) {
////                $alterations[] = "DROP FOREIGN KEY {$name}";
////                migrationLog("Dropping foreign key: $name due to useForeignKeys=false");
////            }
////        }
//
//        if (!empty($alterations)) {
//            $sql = "ALTER TABLE {$this->table} " . implode(', ', $alterations);
//            migrationLog("Generated ALTER TABLE SQL: $sql");
//            return $sql;
//        }
//
//        migrationLog("No alterations needed for table: {$this->table}");
//        return null; // No alterations needed
//    }

    // TYPE METHODS BELOW

    public function primary(): string {
        return "{$this->getIntegerType()} AUTO_INCREMENT PRIMARY KEY";
    }

    public function string(int $length = 255): string {
        return $this->getStringType($length);
    }

    public function integer(): string {
        return $this->getIntegerType();
    }

    public function tinyInteger(): string {
        return $this->getTinyIntegerType();
    }

    public function smallInteger(): string {
        return $this->getSmallIntegerType();
    }

    public function mediumInteger(): string {
        return $this->getMediumIntegerType();
    }

    public function bigInteger(): string {
        return $this->getBigIntegerType();
    }

    public function decimal(int $precision = 10, int $scale = 2): string {
        return $this->getDecimalType($precision, $scale);
    }

    public function float(int $precision = 10, int $scale = 2): string {
        return $this->getFloatType($precision, $scale);
    }

    public function double(int $precision = 10, int $scale = 2): string {
        return $this->getDoubleType($precision, $scale);
    }

    public function boolean(): string {
        return $this->getBooleanType();
    }

    public function date(): string {
        return $this->getDateType();
    }

    public function dateTime(): string {
        return $this->getDateTimeType();
    }

    public function timestamp(): string {
        return $this->getTimestampType();
    }

    public function time(): string {
        return $this->getTimeType();
    }

    public function year(): string {
        return $this->getYearType();
    }

    public function char(int $length = 255): string {
        return $this->getCharType($length);
    }

    public function text(): string {
        return $this->getTextType();
    }

    public function mediumText(): string {
        return $this->getMediumTextType();
    }

    public function longText(): string {
        return $this->getLongTextType();
    }

    public function binary(): string {
        return $this->getBinaryType();
    }

    public function enum(array $values): string {
        $valuesList = implode("', '", array_map('addslashes', $values));
        return "ENUM('$valuesList')";
    }

    public function set(array $values): string {
        $valuesList = implode("', '", array_map('addslashes', $values));
        return "SET('$valuesList')";
    }

    public function getStringType(int $length = 255): string {
        return "VARCHAR({$length})";
    }

    public function getIntegerType(): string {
        return "INT";
    }

    public function getTinyIntegerType(): string {
        return 'TINYINT';
    }

    public function getSmallIntegerType(): string {
        return 'SMALLINT';
    }

    public function getMediumIntegerType(): string {
        return 'MEDIUMINT';
    }

    public function getBigIntegerType(): string {
        return "BIGINT";
    }

    public function getDecimalType(int $precision = 10, int $scale = 2): string {
        return "DECIMAL({$precision}, {$scale})";
    }

    public function getFloatType(int $precision = 10, int $scale = 2): string {
        return "FLOAT({$precision}, {$scale})";
    }

    public function getDoubleType(int $precision = 10, int $scale = 2): string {
        return "DOUBLE({$precision}, {$scale})";
    }

    public function getBooleanType(): string {
        return 'BOOLEAN';
    }

    public function getDateType(): string {
        return 'DATE';
    }

    public function getDateTimeType(): string {
        return 'DATETIME';
    }

    public function getTimestampType(): string {
        return 'TIMESTAMP';
    }

    public function getTimeType(): string {
        return 'TIME';
    }

    public function getYearType(): string {
        return 'YEAR';
    }

    public function getCharType(int $length = 255): string {
        return "CHAR({$length})";
    }

    public function getTextType(): string {
        return 'TEXT';
    }

    public function getMediumTextType(): string {
        return 'MEDIUMTEXT';
    }

    public function getLongTextType(): string {
        return 'LONGTEXT';
    }

    public function getBinaryType(): string {
        return 'BLOB';
    }

    public function getEnumType(array $values): string {
        $valuesList = implode("', '", array_map('addslashes', $values));
        return "ENUM('$valuesList')";
    }

    public function getSetType(array $values): string {
        $valuesList = implode("', '", array_map('addslashes', $values));
        return "SET('$valuesList')";
    }
}