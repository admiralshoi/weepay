<?php

namespace features;
use Database\Model;


class DbMigration {

    public static function _migrate_db(): void {
        migrationLog("Starting Db migration", "_migrate_db");
        self::includeModels();
        $models = self::getModels();

        foreach ($models as $model) {
            migrationLog($model, "_migrate_db");
            $model::migrate();
        }
    }


    public static function backup_db(string $destinationDir): void {
        self::includeModels();
        $models = self::getModels();

        foreach ($models as $model) {
            migrationLog($model, "backup_db");
            $model::backupTable($destinationDir);
        }
    }



    private static function getModels(): array {
        $childClasses = get_declared_classes();
        $modelClasses = [];

        foreach ($childClasses as $class) {
            if (is_subclass_of($class, Model::class)) {
                $modelClasses[] = $class;
            }
        }

        return $modelClasses;
    }


    private static function includeModels(): array {
        $dir = ROOT . "Database/model/";
        $models = directoryContent("$dir*.php");
        foreach ($models as $fn) require_once $dir . $fn;
        return $models;
    }





    public static function massDropDb(): void {
        echo "please uncomment this return in DbMigration to mass-drop";
        return;
        self::includeModels();
        $models = self::getModels();
        foreach ($models as $model) {
            $model::drop();
        }
    }


    public static function massTruncateDb(): void {
        echo "please uncomment this return in DbMigration to mass-truncate";
        return;
        self::includeModels();
        $models = self::getModels();
        foreach ($models as $model) {
            $model::truncate();
        }
    }


}