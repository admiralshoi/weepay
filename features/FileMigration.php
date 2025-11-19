<?php
namespace features;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileMigration {


    public static function __migrate_to_production(): void {
        $src = $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR . 'testing/'; // Sour   ce directory (testing/)
        $dst = $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR;             // Destination directory (production/)

        migrationLog("Starting process to move files...", "__migrate_to_production");
        // Perform migration from testing to production
        self::copyDirectory($src, $dst);
        migrationLog("Finished the process of moving the files...", "__migrate_to_production");
    }

    private static function copyDirectory(string $src, string $dst): void {
        migrationLog("file-excludes: " . json_encode(MIGRATION_EXCLUDES), "copyDirectory");
        $directoryIterator = new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            // Cast the inner iterator to RecursiveDirectoryIterator to access getSubPathname()
            $innerIterator = $iterator->getInnerIterator();
            if ($innerIterator instanceof RecursiveDirectoryIterator) {
                $subPathName = $innerIterator->getSubPathname();
                $targetPath = $dst . DIRECTORY_SEPARATOR . $subPathName;

                $exclude = self::toExclude($subPathName);
                if ($exclude === true) continue;


                if ($item->isDir()) {
                    // Ensure target directory exists or create it
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0777, true);
                    }
                } elseif($exclude !== "DIR_ONLY") {
                    copy($item->getPathname(), $targetPath);
                }
            }
        }
    }

    private static function toExclude(string $subPathName): bool|string {
        if (empty(MIGRATION_EXCLUDES)) return false;
        if (in_array($subPathName, MIGRATION_EXCLUDES)) return true;
        foreach (MIGRATION_EXCLUDES as $exclude) {
            $ex = $exclude;
            if(str_contains($ex, "\\DIR_ONLY")) {
                $ex = str_replace("\\DIR_ONLY", "", $ex);
                if (str_starts_with($subPathName, $ex)) return "DIR_ONLY";
            }
            if (str_starts_with($subPathName, $ex)) return true;
        }
        return false;
    }
}

