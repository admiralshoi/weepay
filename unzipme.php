<?php
function extraZip(string $sourceFile, string $destination): bool {
    if(!file_exists($sourceFile)) return false;
    if(!file_exists($destination) || !is_dir($destination)) return false;


    $zip = new ZipArchive;
    $res = $zip->open($sourceFile);

    if ($res) {
        $zip->extractTo($destination);

        return $zip->close();
    }

    return false;
}


extraZip(__DIR__ . "/Archive.zip", __DIR__ . "/");

//echo __DIR__ . "<br>";
//echo $_SERVER['DOCUMENT_ROOT'] . "<br>";

