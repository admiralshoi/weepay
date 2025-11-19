<?php
spl_autoload_register(function ($className) {
    // Replace backslashes in namespace with directory separators
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    // Construct the full path to the file (adjust 'vendor/' if needed)
    $file = __vendor( 'vendor/html5_parser/src/' . $fileName);

    if (file_exists($file)) {
        include_once $file;
        return true; // Important: Return true on success
    }
    return false; // Important: Return false on failure
}, true, true);