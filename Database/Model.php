<?php
namespace Database;

use features\Settings;
use JetBrains\PhpStorm\Pure;
use PDO;
use ReflectionClass;

class Model {
    protected static ?PDO $pdo = null;
    protected QueryBuilder $query;
    protected static array $schema = [];
    public static ?string $uidPrefix = null;

    public function __construct() {
        self::initialize();
        $this->query = self::setNewQuery(self::tableName());
    }

    public static function initialize(bool $force = false): void {
        if ($force || !isset(self::$pdo)) {
            self::$pdo = (new Connection())->getPdo();
        }
    }


    public static function newStatic(): static { return new static(); }

    private static function setNewQuery(string $tableName): QueryBuilder {
        $encodedColumns = $encryptedColumns = $schema = [];
        if ($tableName === "model") {
            // Mapping between table names and model classes (you may need to update this based on your actual class naming convention)
            $modelDir = __DIR__ . "/model/";
            $files = directoryContent($modelDir, false, true);

            foreach ($files as $file) {
                // Assuming that class names match the file names (e.g., Users.php contains class Users)
                $className = pathinfo($file, PATHINFO_FILENAME);
                $expectedTableName = strtolower($className); // Adjust if your table naming convention differs

                if ($tableName === $expectedTableName) {
                    // Require the class file if it's not already loaded
                    if (!class_exists($className)) {
                        require_once $file;
                    }

                    // Use reflection to access the static property
                    if (class_exists($className)) {
                        $reflector = new ReflectionClass($className);
                        if ($reflector->hasProperty('encodeColumns')) {
                            $property = $reflector->getProperty('encodeColumns');
                            if ($property->isStatic()) {
                                $encodedColumns = $property->getValue();
                            }
                        }
                        if ($reflector->hasProperty('encryptedColumns')) {
                            $property = $reflector->getProperty('encryptedColumns');
                            if ($property->isStatic()) {
                                $encryptedColumns = $property->getValue();
                            }
                        }
                        if ($reflector->hasProperty('schema')) {
                            $property = $reflector->getProperty('schema');
                            if ($property->isStatic()) {
                                $schema = $property->getValue();
                            }
                        }
                    }

                    break; // Exit the loop once the class is found and processed
                }
            }
        } else {
            $encodedColumns = static::$encodeColumns;
            $encryptedColumns = static::$encryptedColumns;
            $schema = static::$schema;
        }

        return new QueryBuilder(self::$pdo, $tableName, $encodedColumns, $encryptedColumns, $schema);
    }

    public static function setConnection(PDO $pdo): void {
        self::$pdo = $pdo;
    }

    public static function tableColumn(string $column): ?string {
        $schema = static::$schema;
        if (!array_key_exists($column, $schema)) return null;
        $table = self::tableName();
        return (DB_ENABLE_PREFIX ? DbConnection::getPrefix() . $table : $table) . "." . $column;
    }

    public static function queryBuilder(): QueryBuilder {
        $instance = new static();
        return $instance->query;
    }

    public static function __callStatic(string $method, array $parameters) {
        $instance = new static();
        if (!method_exists($instance->query, $method)) {
            throw new \BadMethodCallException("Method {$method} does not exist on QueryBuilder.");
        }
        return $instance->query->$method(...$parameters);
    }

    public function __call(string $method, array $parameters) {
        if (!method_exists($this->query, $method)) {
            throw new \BadMethodCallException("Method {$method} does not exist on QueryBuilder.");
        }
        return $this->query->$method(...$parameters);
    }

    public static function table(string $table): static {
        $instance = new static();
        $instance->query = self::setNewQuery($table);
        return $instance;
    }

    public static function drop(string $tableName = ""): void {
        if (empty($tableName)) $tableName = self::tableName();
        if ($tableName === "model") return;
        self::initialize(); // Ensure this method sets the static $pdo property
        $schema = new Schema(self::$pdo);
        $schema->drop($tableName);
    }

    public static function truncate(string $tableName = ""): void {
        if (empty($tableName)) $tableName = self::tableName();
        if ($tableName === "model") return;
        self::initialize(); // Ensure this method sets the static $pdo property
        $schema = new Schema(self::$pdo);
        $schema->truncate($tableName);
    }

    public static function backupTable(string $destinationDir, string $tableName = ""): void {
        self::initialize(true);
        if (empty($tableName)) $tableName = self::tableName();
        if ($tableName === "model") return;
        migrationLog("Model::backupTable() START on table '$tableName'");
        $schema = new Schema(self::$pdo);
        if (!$schema->tableExists($tableName)) {
            migrationLog("Table did not exist.", "backupTable");
            return;
        }
        self::initialize(); // Ensure this method sets the static $pdo property
        $schemaManager = new SchemaManager(self::$pdo, $tableName, $destinationDir);
        $schemaManager->fetchAndStoreSchema();
        $schemaManager->fetchAndStoreData();
        $schemaManager->fetchAndStoreIndexes();
        migrationLog("Model::backupTable() END on table '$tableName'");
    }

    public static function migrate(): void {
        self::initialize(true);
        $schema = new Schema(self::$pdo);
        $tableName = static::tableName();
        if ($tableName === "model") return;
        migrationLog("Model::migrate() START on table '$tableName'");

        if ($schema->tableExists($tableName)) {
            migrationLog("Table exists, altering...");
            // Use alter method from Schema class to apply changes
            $schema->alter($tableName, function (Blueprint $blueprint) use ($tableName) {
                migrationLog("Altering table with schema, indexes, uniques, and foreign keys...");
                debugLog("Altering table '$tableName' with schema, indexes, uniques, and foreign keys...");
                $blueprint->setData(static::$schema, static::$indexes, static::$uniques, static::foreignkeys());
            });
        } else {
            migrationLog("Table does not exist, creating...");
            // Create new table
            $schema->create($tableName, function (Blueprint $blueprint) use ($tableName) {
                migrationLog("Creating table with schema, indexes, uniques, and foreign keys...");
                debugLog("Creating table '$tableName' with schema, indexes, uniques, and foreign keys...");
                $blueprint->setData(static::$schema, static::$indexes, static::$uniques, static::foreignkeys(), true);
            });
        }

        migrationLog("Processing required rows...");

        $rows = static::$requiredRows;
        if (TESTING && !Settings::$migrating) $rows = array_merge($rows, static::$requiredRowsTesting);
        migrationLog($rows, "required-rows");
        foreach ($rows as $row) {
            $query = null;
            foreach ($row as $column => $value) {
                if (!isset($query)) $query = self::where($column, $value);
                else $query->where($column, $value);
            }
            if (empty($query->first())) {
                $query->insert($row);
            }
        }

        migrationLog("Model::migrate() END on table '$tableName'");
    }

    public static function tableName(): string {
        return strtolower((new \ReflectionClass(static::class))->getShortName());
    }

    public static function foreignkeys(): array {
        return [];
    }
}