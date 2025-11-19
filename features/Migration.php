<?php

namespace features;



class Migration {

    public static function _migrate_do_backup(): void {
        BackupUtility::__backup_files();
    }

    public static function _migrate_do_move_files(): void {
        FileMigration::__migrate_to_production();
    }

    public static function _migrate_do_db(): void {
        DbMigration::_migrate_db();
        unset($_SESSION["migrating"]);
    }


}