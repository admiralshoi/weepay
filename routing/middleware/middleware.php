<?php

$files = directoryContent(__DIR__ . "/*.php");
$currentFile = currentFile(__FILE__);
foreach ($files as $file) {
    if($file === $currentFile) continue;
    require_once __DIR__ . "/$file";
}