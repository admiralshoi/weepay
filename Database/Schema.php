<?php
namespace Database;

use PDO;

class Schema {
    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    private function setTableName(string $table): string {
        return DB_ENABLE_PREFIX ? DbConnection::getPrefix() . $table : $table;
    }

    public function tableExists(string $tableName): bool {
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $this->setTableName($tableName)]);
        return $stmt->rowCount() > 0;
    }

    public function drop(string $tableName): void {
        $stmt = $this->pdo->prepare("DROP TABLE IF EXISTS {$this->setTableName($tableName)}");
        $stmt->execute();
        migrationLog("Dropped table: $tableName");
    }

    public function truncate(string $tableName): void {
        if (!$this->tableExists($this->setTableName($tableName))) return;
        $stmt = $this->pdo->prepare("TRUNCATE TABLE {$this->setTableName($tableName)}");
        $stmt->execute();
        migrationLog("Truncated table: $tableName");
    }

    public function create(string $tableName, callable $callback): void {
        try {
            $blueprint = new Blueprint($this->setTableName($tableName), $this->pdo);
            $callback($blueprint);
            $sql = $blueprint->toCreationSql();
            if (empty($sql)) return;
            debugLog($sql);
            migrationLog("Executing CREATE TABLE SQL for $tableName...");
            migrationLog($sql);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            migrationLog("SQL executed successfully for table creation: $tableName");
        } catch (\PDOException $e) {
            migrationLog($e->getMessage(), "SQL error on table creation: $tableName");
            debugLog($e->getMessage(), "SQL table creation, $tableName");
            errorLog($e->getMessage(), "SQL table creation, $tableName");
        }
    }

    public function alter(string $tableName, callable $callback): void {
        try {
            $blueprint = new Blueprint($this->setTableName($tableName), $this->pdo);
            $callback($blueprint);
            $sql = $blueprint->toAlterSql();
            if (empty($sql)) return;
            debugLog($sql);
            migrationLog("Executing ALTER TABLE SQL for $tableName...");
            migrationLog($sql);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            migrationLog("SQL executed successfully for table alteration: $tableName");
        } catch (\PDOException $e) {
            migrationLog($e->getMessage(), "SQL error on table alter: $tableName");
            debugLog($e->getMessage(), "SQL table alter, $tableName");
            errorLog($e->getMessage(), "SQL table alter, $tableName");
        }
    }
}