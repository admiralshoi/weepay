<?php
namespace Database;

use PDO;

class SchemaManager {
    protected PDO $pdo;
    protected string $tableName;
    protected string $destinationDir;
    protected string $schemaFile = 'schema.json';
    protected string $dataFile = 'data.json';
    protected string $indexesFile = 'indexes.json';

    public function __construct(PDO $pdo, string $table, string $destinationDir) {
        $this->pdo = $pdo;
        $this->tableName = DB_ENABLE_PREFIX ? DbConnection::getPrefix() . $table : $table;

        if (!is_dir($destinationDir)) mkdir($destinationDir);
        $destinationDir = $destinationDir . "/$this->tableName";
        if(DB_ENABLE_PREFIX) $destinationDir = str_replace(DbConnection::getPrefix(), "", $destinationDir);;
        if (!is_dir($destinationDir)) mkdir($destinationDir);
        $this->destinationDir = $destinationDir;
    }

    public function fetchAndStoreSchema(): void {
        $columns = $this->fetchColumns();
        $schema = [];

        foreach ($columns as $column) {
            $columnName = $column['Field'];
            if (in_array($columnName, ["id", "created_at", "updated_at"])) continue;

            $columnType = $column['Type'];
            $nullable = $column['Null'] === 'YES';
            $default = $column['Default'];
            $comment = $this->getColumnComment($columnName);

            $schema[$columnName] = $this->parseColumnSchema($columnType, $nullable, $default, $comment);
        }

        file_put_contents("$this->destinationDir/$this->schemaFile", json_encode($schema, JSON_PRETTY_PRINT));
    }

    public function fetchAndStoreData(): void {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName}");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents("$this->destinationDir/$this->dataFile", json_encode($data, JSON_PRETTY_PRINT));
    }

    public function fetchAndStoreIndexes(): void {
        $indexes = $this->fetchIndexes();
        file_put_contents("$this->destinationDir/$this->indexesFile", json_encode($indexes, JSON_PRETTY_PRINT));
    }

    protected function fetchColumns(): array {
        $stmt = $this->pdo->prepare("DESCRIBE {$this->tableName}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getColumnComment(string $columnName): ?string {
        $stmt = $this->pdo->prepare("SELECT COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table AND COLUMN_NAME = :column");
        $stmt->execute([
            'table' => $this->tableName,
            'column' => $columnName
        ]);
        return $stmt->fetchColumn();
    }

    protected function fetchIndexes(): array {
        $stmt = $this->pdo->prepare("SHOW INDEXES FROM {$this->tableName}");
        $stmt->execute();
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedIndexes = [];
        foreach ($indexes as $index) {
            $indexName = $index['Key_name'];
            $columnName = $index['Column_name'];
            $nonUnique = $index['Non_unique'] == 1;

            if (!isset($formattedIndexes[$indexName])) {
                $formattedIndexes[$indexName] = [
                    'non_unique' => $nonUnique,
                    'columns' => []
                ];
            }

            $formattedIndexes[$indexName]['columns'][] = $columnName;
        }

        return $formattedIndexes;
    }

    protected function parseColumnSchema(string $type, bool $nullable, $default, ?string $comment): string|array {
        $columnType = $this->mapColumnType($type);
        $arr = ['type' => $columnType];
        if (!empty($comment)) $arr["comment"] = $comment;
        if ($default === null && $nullable) $arr["default"] = null;
        if ($default !== null) $arr["default"] = $default;
        if ($nullable) $arr["nullable"] = true;

        if (count(array_keys($arr)) === 1) return $columnType;
        return $arr;
    }

    protected function mapColumnType(string $type): string {
        // Determine the column type based on the type string
        $type = strtolower($type);

        if (str_contains($type, 'varchar')) {
            return 'string';
        }
        if (str_contains($type, 'int')) {
            return 'integer';
        }
        if (str_contains($type, 'tinyint')) {
            return 'tinyInteger';
        }
        if (str_contains($type, 'smallint')) {
            return 'smallInteger';
        }
        if (str_contains($type, 'mediumint')) {
            return 'mediumInteger';
        }
        if (str_contains($type, 'bigint')) {
            return 'bigInteger';
        }
        if (str_contains($type, 'decimal')) {
            return 'decimal';
        }
        if (str_contains($type, 'float')) {
            return 'float';
        }
        if (str_contains($type, 'double')) {
            return 'double';
        }
        if (str_contains($type, 'boolean')) {
            return 'boolean';
        }
        if (str_contains($type, 'date')) {
            return 'date';
        }
        if (str_contains($type, 'datetime')) {
            return 'dateTime';
        }
        if (str_contains($type, 'timestamp')) {
            return 'timestamp';
        }
        if (str_contains($type, 'time')) {
            return 'time';
        }
        if (str_contains($type, 'year')) {
            return 'year';
        }
        if (str_contains($type, 'char')) {
            return 'char';
        }
        if (str_contains($type, 'text')) {
            return 'text';
        }
        if (str_contains($type, 'mediumtext')) {
            return 'mediumText';
        }
        if (str_contains($type, 'longtext')) {
            return 'longText';
        }
        if (str_contains($type, 'blob')) {
            return 'binary';
        }
        if (str_contains($type, 'enum')) {
            return 'enum';
        }
        if (str_contains($type, 'set')) {
            return 'set';
        }

        return 'unknown'; // For types that are not mapped
    }
}
