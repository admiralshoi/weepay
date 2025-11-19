<?php
namespace features;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BackupUtility {
    public static function __backup_files(): void {
        if (MAX_BACKUPS === 0) return;
        $root = $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR;
        $versionDir = $root . "versions/";
        $backupDirName = date("Y-m-d-H-i-s");
        $backupDir = $versionDir . $backupDirName;
        $dirsToRemove = self::dirsToRemove($versionDir);
        $filesDir = $backupDir . "/content";
        $dbDir = $backupDir . "/database/";

        // Remove old backup directories if necessary
        migrationLog("Versions to remove: " . json_encode($dirsToRemove), "__backup_files");
        foreach ($dirsToRemove as $dir) removeDirectory($versionDir . $dir, true);


        mkdir($backupDir);
        migrationLog("Creating new version: $backupDirName", "__backup_files");
        self::copyDirectory($root, $filesDir);
        migrationLog("Finished creating the new version $backupDirName", "__backup_files");
        migrationLog("Beginning db-backup", "__backup_files");
        DbMigration::backup_db($dbDir);
        migrationLog("Finished db-backup", "__backup_files");
    }

    private static function dirsToRemove(string $versionDir): array {
        $dirsToRemove = [];
        $currentBackupDirs = directoryContent($versionDir, true);
        if (count($currentBackupDirs) >= MAX_BACKUPS) {
            $oldestDir = null;
            foreach ($currentBackupDirs as $dirname) {
                if (is_null($oldestDir)) $oldestDir = $dirname;
                elseif (strtotime($dirname) < strtotime($oldestDir)) $oldestDir = $dirname;
            }
            $dirsToRemove[] = $oldestDir;
        }
        return $dirsToRemove;
    }


    private static function copyDirectory(string $src, string $dst): bool {
        migrationLog("Backup-excludes: " . json_encode(BACKUP_EXCLUDES), "copyDirectory");
        $directoryIterator = new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $innerIterator = $iterator->getInnerIterator();
            if ($innerIterator instanceof RecursiveDirectoryIterator) {
                $subPathName = $innerIterator->getSubPathname();
                $targetPath = self::sanitizePath($dst . DIRECTORY_SEPARATOR . $subPathName);
                $exclude = self::toExclude($subPathName);
                if ($exclude === true) continue;

                if ($item->isDir()) {
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0755, true);
                    }
                } else {
                    if (!is_dir(dirname($targetPath))) {
                        mkdir(dirname($targetPath), 0777, true);
                    }
                    elseif($exclude !== "DIR_ONLY") {
                        if (!copy($item->getPathname(), $targetPath)) return false;
                    }
                }
            }
        }
        return true;
    }

    private static function toExclude(string $subPathName): bool|string {
        if (empty(BACKUP_EXCLUDES)) return false;
        if (in_array($subPathName, BACKUP_EXCLUDES)) return true;
        foreach (BACKUP_EXCLUDES as $exclude) {
            $ex = $exclude;
            if(str_contains($ex, "\\DIR_ONLY")) {
                $ex = str_replace("\\DIR_ONLY", "", $ex);
                if (str_starts_with($subPathName, $ex)) return "DIR_ONLY";
            }
            if (str_starts_with($subPathName, $ex)) return true;
        }
        return false;
    }



    private static function sanitizePath(string $path): string {
        // Replace any backslashes with forward slashes for consistency
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }
}
