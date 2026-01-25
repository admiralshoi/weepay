<?php
if (session_status() == PHP_SESSION_NONE && !headers_sent()) session_start();
const IN_VIEW = true;

//phpinfo();
//exit;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once "env/other/config.php";
require_once ROOT . "routing/autoload.php";
require_once __vendor("vendor/autoload.php");

// Register global error handlers for notifications
\classes\errors\ErrorNotifier::register();

require_once ROOT . "routing/web.php";







