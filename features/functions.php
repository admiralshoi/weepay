<?php

use classes\enumerations\Links;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

require_once ROOT . "features/permissions.php";

function isLoggedIn(): bool {
    return isset($_SESSION["logged_in"]) && $_SESSION["logged_in"];
}
function isOidcAuthenticated(): bool {
    return isset($_SESSION["oidcAuth"]) && $_SESSION["oidcAuth"];
}
function isOidcVerified(): bool {
    return isOidcAuthenticated() ||
        isLoggedIn() && \classes\Methods::oidcAuthentication()->exists(['user' => __uuid(), 'enabled' => 1]);
}
function isLocalAuthenticated(): bool {
    return isset($_SESSION["localAuth"]) && $_SESSION["localAuth"];
}
function currentUserId(): null|string|int {
    return isset($_SESSION["uuid"]) ? $_SESSION["uuid"] : null;
}

function request_handler($request) {
    if(isset($request["refresh"])) {
        $_SESSION = array();
        session_destroy();
        header("location: " . HOST . "login");
    }
}




function isAssoc(array $arr): bool {
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}


function prettyPrint(mixed $content): void {
    $style = 'white-space: normal';
    if(is_array($content) || is_object($content)) {
        $content = json_encode($content, JSON_PRETTY_PRINT);
        $style = '';
    }
    echo "<pre style='$style'>$content</pre>";
}
function printView(array $viewList): void {
    if(empty($viewList["views"])) return;

    $args = toObject($viewList["args"]);
    $styleList = $viewList["css"];
    $scriptList = $viewList["js"];
    $head = $viewList["head"];
    $customScripts = $viewList["custom_scripts"];
    $view = $viewList["views"][0];
    $pageHeaderTitle = $viewList["title"];
    unset($viewList["views"][0]);
    $viewList["views"] = array_values($viewList["views"]);
    include_once __view($view);
}

#[NoReturn] function printHtml(?string $content, $responseCode = 200): void {
    if(is_null($content)) {
        http_response_code(500);
        echo "Something went wrong";
        exit;
    }
    http_response_code($responseCode);
    header("Content-Type: text/html; charset=utf-8");
    echo $content;
    exit;
}

#[NoReturn] function printJson(mixed $content, $responseCode = 200): void {
    if(is_null($content)) {
        http_response_code(500);
        echo "Something went wrong";
        exit;
    }
    if(is_array($content)) $content = json_encode($content);
    http_response_code($responseCode);
    header("Content-Type: application/json");
    echo $content;
    exit;
}
#[NoReturn] function printMimeType(mixed $content, string $mimeType, $responseCode = 200): void {
    if(is_null($content)) {
        http_response_code(500);
        echo "Something went wrong";
        exit;
    }
    if(is_array($content)) $content = json_encode($content);
    http_response_code($responseCode);
    header("Content-Type: $mimeType");
    echo $content;
    exit;
}
function microTimeFloat(): float {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


function backslashToSlash(string &$str): void { $str = str_replace("\\", "/", $str); }
function resolveImportUrl(string $url, bool $includeVersion = true, bool $respectLiveDbForce = false): string {
    $path = $url;
    if(array_key_exists("scheme", parse_url($url))) backslashToSlash($path);
    else {
        $path = __url($url);
        if(TESTING && $respectLiveDbForce && forceLiveDb() && str_contains($path, "testing/"))
            $path = str_replace("testing/", "", $path);
    }
    return $includeVersion ? "$path?version=" . PLATFORM_VERSION : $path;
}

function __vendor($filename): string {
    return $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR . $filename;
}
function __ext (string $filename): string {
    return pathinfo($filename, PATHINFO_EXTENSION);
}
function __path (string $path, string $ext = "", bool $asset = false): string {
    if(str_ends_with($path, ".html")) {
        $ext = "html";
        $path = substr($path, 0, -5);
    }
    elseif(str_ends_with($path, ".php")) {
        $ext = "php";
        $path = substr($path, 0, -4);
    }
    elseif(str_ends_with($path, ".min.js")) {
        $ext = "min.js";
        $path = substr($path, 0, -7);
    }
    elseif(str_ends_with($path, ".js")) {
        $ext = "js";
        $path = substr($path, 0, -3);
    }
    elseif(str_ends_with($path, ".min.css")) {
        $ext = "min.css";
        $path = substr($path, 0, -8);
    }
    elseif(str_ends_with($path, ".css")) {
        $ext = "css";
        $path = substr($path, 0, -4);
    }
    if(empty($ext)) $ext = "php";

    if($asset) return str_replace(".", "/", $path) . "." . $ext;
    return ROOT . str_replace(".", DIRECTORY_SEPARATOR, $path) . "." . $ext;
}


function __view (string $path, string $ext = ""): string {
    return __path("views.$path", $ext);
}
function __include (string $path, string $ext = "", bool $includeOnce = true): void {
    $basedir = "views";
    $path = $basedir . "." . $path;
    $path = __path($path, $ext);
    if(file_exists($path)) {
        if($includeOnce) include_once $path;
        else include $path;
    }
}


function __image(string $path): string {
    return __asset("media/images/$path");
}
function __video(string $path): string {
    return HOST . "public/media/videos/$path";
}
function __asset(string $path): string {
    return HOST . "public/$path";
}
function assets (string $path, string $ext = "js"): string {
    $external = array_key_exists("scheme", parse_url($path));
    if(!$external) {
        $basedir = "public";
        $path = $basedir . "." . $path;
        $path = __path($path, $ext, true);
    }

    if(file_exists(ROOT . $path) || $external) {
        if($ext === "css") return "<link rel='stylesheet' href='" . resolveImportUrl($path) . "'>";
        elseif($ext === "js") {
            return "<script src='" . resolveImportUrl($path, !$external) . "'></script>";
        }
    }
    return "";
}

function Views(string $name, array $args = []): ?array {
    $viewList = routing\paths\Paths::view($name);

    if(empty($viewList)) return $viewList;
    return array_merge(["return_as" => "view", "args" => $args], $viewList);
}
#[Pure] function Response(): \routing\RouteResponse {
    return new \routing\RouteResponse();
}

function trimPath(string $path): string {
    if(str_starts_with($path, "/")) $path = substr($path, 1);
    if(str_ends_with($path, "/")) $path = substr($path, 0, strlen($path) -1);
    return strtolower($path);
}


function realUrlPath(): string {
    $path = trimPath(getUrlPath());
    $rootDir = trimPath(ROOT_DIR);
    if (TESTING) $rootDir = trimPath(ROOT_DIR . "testing/");

    if (!empty($path) && !empty($rootDir) && str_contains($path, $rootDir))
        $path = str_replace($rootDir, "", $path);

    return trimPath($path);
}


function __adjustUrl(string $url = "", array $addToQuery = [], array $removeFromQuery = []): string {
    if(empty($url)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $protocol . $host . $uri;
    }

    $path_query = explode("?", $url);
    $url = $path_query[0];
    $query = [];
    if(array_key_exists(1, $path_query)) parse_str($path_query[1], $query);
    if(forceLiveDb() && !array_key_exists("live_db", $query) && !array_key_exists("live_db", $removeFromQuery)) $addToQuery["live_db"] = LIVE_DB_FORCE_TOKEN;
    foreach ($addToQuery as $parameter => $value) $query[$parameter] = $value;
    foreach ($removeFromQuery as $parameter) if(array_key_exists($parameter, $query)) unset($query[$parameter]);
    return  $url . (empty($query) ? "" : "?" . http_build_query($query));
}


function directoryContent($path, $directoryOnly = false, $fullPath = false): bool|array {
    $content = $directoryOnly ? glob($path."*",GLOB_ONLYDIR) : glob($path."*");
    if(!empty($content)) {
        foreach ($content as $i => $item) {
            if(!$fullPath) $item = str_contains($item,$path) ? str_replace($path,"",$item) : basename($item);
            if($item === ".") unset($content[$i]);
            $content[$i] = $item;
        }
    }
    return $content;
}
function currentFile($file): string {
    return basename($file);
}

function removeDirectory($directory, $removeStartDirectory = false): bool {
    if(!is_dir($directory)) return false;

    $it = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

    if(!empty($files)) {
        foreach ($files as $file) {

            if($file->isDir()) rmdir($file->getRealPath());
            else unlink($file->getRealPath());
        }
    }
    if($removeStartDirectory) rmdir($directory);

    return !$removeStartDirectory || !is_dir($directory);
}

#[Pure] function testingLiveDb(): bool { return TESTING && forceLiveDb(); }
function forceLiveDb(): bool { return isset($_GET["live_db"]) && $_GET["live_db"] === LIVE_DB_FORCE_TOKEN; }
function __url(string $path = ""): string { return __adjustUrl(str_starts_with($path, HOST) ? $path : HOST . $path); }
function __lib(string $path): string { return ROOT . "lib/$path"; }
function __env(string $path): string { return ROOT . "env/$path"; }

function snakeToCamel(string $snake): string {
    $prefix = '';
    if (str_starts_with($snake, '_')) {
        $prefix = '_';
        $snake = substr($snake, 1);
    }
    $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snake))));
    return $prefix . $camel;
}
function camelToSnake(string $camel, bool $toLower = true): string {
    $replaced = preg_replace('/(?<!^)[A-Z]/', '_$0', $camel);
    return $toLower ? strtolower($replaced) : $replaced;
}

function objectReverse(object|array $item): array|object {
    $reversed = array_reverse(toArray($item));
    return is_object($item) ? toObject($reversed) : $reversed;
}
function isEmpty(mixed $item): bool {
    if(is_object($item)) return empty((array)$item);
    return empty($item);
}
function toArray(object|array|null $item): array {
    if(is_null($item)) return [];
    $collectionClass = "\\Database\\Collection";
    $collExists = class_exists($collectionClass);
    $mediaClass = "\\Database\\Collection";
    $MediaExists = class_exists($collectionClass);
    $array = [];
    foreach ($item as $key => $value) {
        if (is_array($value)) {
            $array[$key] = $value;
        } elseif ($collExists && $value instanceof $collectionClass) {
            $array[$key] = toArray($value->list());
        }  elseif ($MediaExists && $value instanceof $mediaClass) {
            $array[$key] = toArray($value->data());
        }  elseif (is_object($value)) {
            $array[$key] = toArray($value);
        } else {
            $array[$key] = $value;
        }
    }
    return $array;
}
function toObject(array|object|null $array, bool $force = false): object {
    $object = new stdClass();
    if(is_null($array)) return $object;
    if(is_object($array) && !$force) return $array;
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $object->$key = toObject($value);
        } elseif (is_object($value)) {
            $object->$key = $value;
        } else {
            $object->$key = $value;
        }
    }
    return $object;
}


function __unsetKey(array|object|null $obj, string|int|array $keys): array|object|null {
    $isObj = false;
    if(isEmpty($obj)) return $obj;
    if(is_object($obj)) {
        $isObj = true;
        $obj = toArray($obj);
    }
    if(!is_array($keys)) $keys = [$keys];
    foreach ($keys as $key) {
        if(array_key_exists($key, $obj)) unset($obj[$key]);
    }
    if($isObj) $obj = toObject($obj);
    return $obj;
}


function nestedArray(null|array|object $targetObject, array $keys, mixed $defaultReturnKey = null): mixed {
    $revert = false;
    if(is_object($targetObject)) {
        $targetObject = toArray($targetObject);
        $revert = true;
    }
    if(empty($keys) || empty($targetObject)) return $defaultReturnKey;

    $loop = $targetObject;
    foreach ($keys as $key) {
        if(!is_array($loop) || !array_key_exists($key, $loop)) return $defaultReturnKey;
        $loop = $loop[$key];
    }

    return is_array($loop) && $revert ? toObject($loop) : $loop;
}


$encode_keys = array(
    "iv" => 1111011011011101 ,// Non-NULL Initialization Vector for encryption
    "key" => 'DECHI_IMNDI_AOKDdjd_JOI',// Store the encryption key,
    "ciphering" => 'AES-128-CTR'// Store the cipher method
);

function _env(string $path): mixed {
    if(!file_exists(ROOT . "env/$path")) return null;
    return include ROOT . "env/$path";
}

function passwordHashing(string $password): string {
    if(empty($password)) return "";
    return hash("sha256", encrypt($password));
}
function encrypt(string $str, bool $decrypt = false): string {
    $details = \features\Settings::$encryptionDetails;
    $iv_length = openssl_cipher_iv_length($details["ciphering"]); // Use OpenSSl Encryption method
    return $decrypt ? openssl_decrypt($str, $details["ciphering"], $details["key"], 0,$details["iv"])  //Decrypt
        : openssl_encrypt($str, $details["ciphering"], $details["key"], 0, $details["iv"]); //Encrypt
}
function encodeCursor(array $cursor): string {
    return base64_encode(
        encrypt(
            json_encode($cursor)
        )
    );
}
function decodeCursor(string $cursor): array {
    $data = json_decode(
        encrypt(
            base64_decode($cursor),
            true
        )
        ,true);
    return is_array($data) ? $data : [];
}

function setSessions(array|object $object, array $keys): void {
    $object = (array)$object;
    foreach ($keys as $session_key) {
        if (array_key_exists($session_key, $object)) $_SESSION[$session_key] = $object[$session_key];
        else $_SESSION[$session_key] = true;
    }
}
function removeSessions(): void {
    $exceptions = ["_csrf", "events"];
    foreach (array_keys($_SESSION) as $key) if(!in_array($key, $exceptions)) unset($_SESSION[$key]);
}



function enforceDataType(mixed $value, string $type, bool $abs = false): mixed {
    return match ($type) {
        default => $value,
        "int" => $abs ? abs((int)$value) : (int)$value,
        "float" => $abs ? abs((float)str_replace(",", ".", $value)) : (float)str_replace(",", ".", $value),
        "string" => (string)$value,
        "bool" => (string)$value === "true" || (int)$value === 1,
        "array" => !is_array($value) ? json_decode($value, true) : $value,
        "string|int" => !is_numeric($value) ? (string)$value : ($abs ? abs((int)$value) : (int)$value)
    };
}

function confirmDataType(mixed $value, string $type): bool {
    return match ($type) {
        default => false,
        "int" => is_int($value),
        "float" => is_float($value),
        "string" => is_string($value),
        "bool" => is_bool($value),
        "array" => is_array($value),
        "numeric" => is_numeric($value),
        "string|int", "int|string" => is_int($value) || is_string($value),
        "float|int", "int|float" => is_int($value) || is_float($value),
        "null" => is_null($value)
    };
}

function isErrorResponse(mixed $response): bool {
    return is_array($response) && array_key_exists("status", $response) && $response["status"] === "error";
}

function __csrf(): string {
    if(!isset($_SESSION["_csrf"])) $_SESSION["_csrf"] = bin2hex(random_bytes(32));
    return $_SESSION["_csrf"];
}

function __referer(?string $referIfNoMatch = null): ?string{
    if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER'])) return $referIfNoMatch;
    $url = $_SERVER['HTTP_REFERER'];
    if(!str_starts_with($url, HOST)) return $referIfNoMatch;
    return $url;
}

function __accessLevel(): int {
    return isset($_SESSION["access_level"]) ? $_SESSION["access_level"] : 0;
}
function __uuid(): string {
    return isset($_SESSION["uid"]) ? $_SESSION["uid"] : "";
}
function __name(): string {
    return isset($_SESSION["full_name"]) ? $_SESSION["full_name"] : "";
}
function __initials(?string $name): string {
    return empty($name) ? "" : substr($name, 0, 1) . substr(strstr($name, ' '), 1, 1);
}
#[Pure] function __oid(): ?string {
    return !isEmpty(\features\Settings::$organisation) ? (string)\features\Settings::$organisation->organisation->uid : null;
}
function paymentGatewayIsTest(): int {
    return (int)(!(\features\Settings::$app->stripe_live_keys));
}
function __oUuid(): ?string {
    $role = \classes\Methods::roles()->name();
    debugLog([$role, __oid(), toArray(\features\Settings::$organisation)], 'functions_oUuid');
    return match ($role) {
        default => null,
        'merchant' => __oid(),
        'consumer' => __uuid(),
    };
}
function generateUniqueId($length = 9, $type = 'MIX', bool $includeUppercase = false): string {
    $numbers = '0123456789';
    $letters = 'abcdefghijklmnopqrstuvwxyz' . ($includeUppercase ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '');
    $specialCharacters = '-_.';
    $characters = match($type) {
        default => $numbers . $letters . $specialCharacters,
        "INT" => $numbers,
        "STRING" => $letters,
        "SPECIAL" => $specialCharacters,
        "INT_STRING", "STRING_INT" => $numbers . $letters,
        "INT_SPECIAL", "SPECIAL_INT" => $numbers . $specialCharacters,
        "STRING_SPECIAL", "SPECIAL_STRING" => $letters . $specialCharacters,
    };
    $uid = '';
    $charLength = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) $uid .= $characters[rand(0, $charLength)];
    if($type === 'INT') return $uid;
    while (true) {
        if(str_starts_with((string)$uid, "0")) return generateUniqueId($length, $type, $includeUppercase);
        if(!is_numeric($uid)) break;
        if((int)$uid === 0) return generateUniqueId($length, $type, $includeUppercase);
    }

    return $uid;
}



function downloadMedia(
    $url,
    $dest,
    $content = null,
    $filename = "",
    bool $useExtension = true,
    bool $overwrite = false,
    bool $streamOpt = false
): bool|string {
    // Use basename() function to return the base name of file
    $filename = empty($filename) ? basename($url) : $filename;
    $fileInfoSource = filenameInfo(basename($url));

    if(empty($filename)) {
        $outputFilename = $fileInfoSource["fn"];
        if(str_contains($outputFilename, "?")) $outputFilename = explode("?", $outputFilename)[0];
    }
    else {
        if(str_contains($filename, "?")) $filename = explode("?", $filename)[0];
        $outputFilename = $useExtension && !str_ends_with($filename, $fileInfoSource["ext"]) ? "$filename." . $fileInfoSource["ext"] : $filename;
    }

    $path = $dest . $outputFilename;

    if(!$overwrite && file_exists($path)) return basename($path);
    if($content !== null && $content !== false) {
        try {
            $size = file_put_contents($path,$content);
            if((int)$size === 0) {
                if(file_exists($path)) unlink($path);
                return false;
            }
            return $outputFilename;
        } catch (\Exception $e) {
            return false;
        }
    } else {
        // Gets the file from url and saves the file by using its base name
        try {
            if(!empty($streamOpt)) {
                $content = getFileAndHeaderFilename($url);
                $fileData = $content["data"];
                $responseHeaders = $content["headers"];
                if(array_key_exists("filename", $responseHeaders)) $outputFilename = $responseHeaders["filename"];
                else {
                    $contentType = contentTypeFromHeaders($responseHeaders);
                    $ext = extensionFromContentType($contentType);
                    if(empty($contentType) || empty($contentExt)) $ext = KNOWN_CONTENT_TYPES[(array_keys(KNOWN_CONTENT_TYPES)[0])];
                    $outputFilename = explode(".", $outputFilename)[0];
                    if(!str_ends_with($outputFilename, ".$ext")) $outputFilename .= ".$ext";
                }
                $path = $dest . $outputFilename;
            }
            else $fileData = file_get_contents($url);


            $size = file_put_contents($path, $fileData);
            if((int)$size === 0) {
                if(file_exists($path)) unlink($path);
                return false;
            }
            return basename($path);
        } catch (\Exception $e) {
            errorLog($e->getMessage(), "downloadMedia");
            return false;
        }
    }
}



function extToMediaType(?string $ext): ?string {
    $knownList = [
        "jpeg" => "image",
        "jpg" => "image",
        "heic" => "image",
        "gif" => "image",
        "png" => "image",
        "svg" => "image",
        "webp" => "image",
        "avi" => "video",
        "mp4" => "video",
        "mov" => "video",
        "wmv" => "video",
        "avchd" => "video",
        "webm," => "video",
        "flv," => "video"
    ];
    if(empty($ext)) return $ext;
    return !array_key_exists(strtolower($ext), $knownList) ? null : $knownList[strtolower($ext)];
}

function contentTypeFromHeaders(array $headers): ?string {
    if(empty($headers)) return null;
    foreach (["Content-Type", "content-type", "ContentType"] as $key) {
        if(array_key_exists($key, $headers)) return $headers[$key];
    }
    return null;
}
function extensionFromContentType(?string $contentType): ?string {
    if(empty($contentType)) return $contentType;
    return !array_key_exists(strtolower($contentType), KNOWN_CONTENT_TYPES) ? null : KNOWN_CONTENT_TYPES[strtolower($contentType)];
}


#[ArrayShape(["data" => "false|string", "headers" => "array"])]
function getFileAndHeaderFilename(string $url): array {
    try {
        $streamOpt = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"
            ]
        ];
        $context = stream_context_create($streamOpt);
        $fileData = file_get_contents($url, false, $context);
        $responseHeaders = cleanHttpResponseHeaders($http_response_header);

        return [
            "data" => $fileData,
            "headers" => $responseHeaders
        ];
    }
    catch (\Exception $e) {
        errorLog($e->getMessage(), "getFileAndHeaderFilename");
    }
    return [
        "data" => $fileData,
        "headers" => []
    ];
}


#[ArrayShape(["ext" => "array|string|string[]", "fn" => "string", "fnid" => "array|string|string[]"])]
function filenameInfo($pathToFile): array {
    $filename = basename($pathToFile);
    if(strpos($filename,"?") !== false)
        $filename = (explode("?",$filename))[0];
    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
    if($file_ext == false || $file_ext === "image")
        $file_ext = "png";
    if(strpos($filename,"~") !== false)
        $filename = (explode("~",$filename))[0].".".$file_ext;
    $name = str_replace(".".$file_ext,"",$filename);
    return array(
        "ext" => $file_ext,
        "fn" =>  $filename,
        "fnid" => $name
    );
}


function cleanHttpResponseHeaders(array $headers): array {
    if(empty($headers)) return [];
    $collection = [];
    foreach ($headers as $header) {
        $split = explode(":", $header);
        $key = array_shift($split);
        $collection[$key] = trim(implode(":",$split));
    }

    if(array_key_exists("Content-Disposition", $collection)) {
        $disposition = $collection["Content-Disposition"];
        if(str_contains($disposition, "filename=")) {
            $split = explode(";", $disposition);
            foreach ($split as $str) {
                if(!str_contains($str, "filename=")) continue;
                $keyPair = explode("=", $disposition);
                if(count($keyPair) > 1) {
                    $collection["filename"] = $keyPair[1];
                    break;
                }
            }
        }
    }

    return $collection;
}

function cleanString(?string $string): string {
    if (empty($string)) return "";
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    $unwantedChars = ['„', '“', '”', '‘', '’', '•', '…']; // Add any other problematic characters here
    $string = str_replace($unwantedChars, '', $string);
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function isValidUTF8(?string $string): bool {
    if (!is_string($string) || !mb_check_encoding($string, 'UTF-8')) return false;
    return $string === cleanString($string);
}




function isDivisible(null|string|float|int $n): bool {
    if(is_null($n) || is_string($n)) return false;
    return $n > 0 || $n < 0;
}
function calculateTax(int|float $amount, null|string|float|int $taxPercentage): int|float {
    if(!isDivisible($taxPercentage)) return 0;
    $taxRate = $taxPercentage / 100;
    $amountInclTax = $amount * ($taxRate + 1);
    return $amountInclTax - $amount;
}

function currencySymbol(?string $currency): string {
    if(empty($currency)) return "";
    $currency = strtoupper($currency);
    $list = json_decode(file_get_contents(ROOT . CURRENCIES), true);
    return nestedArray($list, [$currency, "symbol_native"], "");
}

function formatPhone(?string $phone, ?string $countryCode = null, ?array $dialerLib = null): string {
    if(isEmpty($phone)) return 'N/A';
    $dialerCode = !isEmpty($countryCode) ? \classes\utility\Misc::callerCode($countryCode, true, $dialerLib) : null;
    return !isEmpty($dialerCode) ? "+{$dialerCode} {$phone}" : "+{$phone}";
}

function creditCardSvg(?string $brand): string {
    $cardSvgs = [
        "visa" =>  "https://img.icons8.com/color/48/visa.png",
        "mastercard" => "https://img.icons8.com/color/48/mastercard.png",
        "amex" => "https://img.icons8.com/color/48/american-express.png",
        "discover" => "https://img.icons8.com/color/48/discover.png",
        "diners" => "https://img.icons8.com/color/48/diners-club.png",
        "jcb" => "https://img.icons8.com/color/48/jcb.png",
        "unionpay" => "https://img.icons8.com/color/48/unionpay.png",
        "unknown" => __asset("media/icons/credit-card-generic.svg")
    ];
    return array_key_exists($brand,$cardSvgs) ? $cardSvgs[$brand] : $cardSvgs['unknown'];
}

function keysExist(array $array, array $keys, bool $canBeEmpty = true): string|int|bool {
    foreach ($keys as $key => $value) {
        if (is_array($value)) {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $key;
            }
            $val = keysExist($array[$key], $value, $canBeEmpty);
            if ($val !== true) {
                return $key;
            }
        } else {
            if (!array_key_exists($value, $array)) {
                return $value;
            }
            $val = $array[$value];
            if (!$canBeEmpty && ($val !== 0 && empty($val))) {
                return $value;
            }
        }
    }
    return true;
}

function mapItemToKeyValuePairs(object|array|null $object, string|int $keyColumn, string|int $valueColumn): array {
    if(isEmpty($object)) return [];
    if($object instanceof \Database\Collection) $object = $object->toArray();
    else $object = toArray($object);
    $res = [];
    foreach ($object as $item) {
        if (!array_key_exists($keyColumn, $item)) continue;
        if (!array_key_exists($valueColumn, $item)) continue;
        $res[$item[$keyColumn]] = $item[$valueColumn];
    }
    return $res;
}



function requiresOrganisation(): bool {
    $headers = apache_request_headers();
    $isApi = array_key_exists("Request-Type", $headers) && $headers["Request-Type"] === "api";
    $exceptionPaths = [
        Links::$merchant->organisation->add,
        Links::$app->logout
    ];
    return !$isApi &&
        isLoggedIn() &&
        \classes\Methods::isMerchant() &&
        !\classes\Methods::organisationMembers()->hasOrganisation() &&
        !in_array(realUrlPath(), $exceptionPaths);
}
function requiresSelectedOrganisation(): bool {
    $headers = apache_request_headers();
    $isApi = array_key_exists("Request-Type", $headers) && $headers["Request-Type"] === "api";
    $exceptionPaths = [
        Links::$merchant->organisation->switch,
        Links::$merchant->organisation->home,
        Links::$merchant->organisation->add,
        Links::$app->logout
    ];
    return !$isApi &&
        isLoggedIn() &&
        \classes\Methods::isMerchant() &&
        isEmpty(\features\Settings::$organisation?->organisation) &&
        !in_array(realUrlPath(), $exceptionPaths);
}
function requiresSelectedOrganisationWallet(): bool {
    $headers = apache_request_headers();
    if(array_key_exists("Request-Type", $headers) && $headers["Request-Type"] === "api") return false;
    $exceptionPaths = [
        Links::$merchant->organisation->switch,
        Links::$merchant->organisation->home,
        Links::$merchant->organisation->add,
        Links::$app->logout
    ];
    return \classes\Methods::isMerchant() &&
        isEmpty(\features\Settings::$organisation?->organisation?->merchant_prid) &&
        !in_array(realUrlPath(), $exceptionPaths);
}
function requiresProfileCompletion(): bool {
    if(adminImpersonating()) return false;
    $headers = apache_request_headers();
    if(array_key_exists("Request-Type", $headers) && $headers["Request-Type"] === "api") return false;
    $exceptionPaths = [
        Links::$app->auth->consumerSignup . '/complete-profile',
        Links::$app->logout
    ];
    return \classes\Methods::isConsumer() &&
        (isEmpty(\features\Settings::$user?->phone) ||
        isEmpty(\features\Settings::$user?->full_name)) &&
        !in_array(realUrlPath(), $exceptionPaths);
}

function requiresPasswordChange(): bool {
    // Skip for non-logged-in users
    if(!isLoggedIn()) return false;
    if(adminImpersonating()) return false;
    if(!isOidcAuthenticated()) return false;

    $headers = apache_request_headers();
    // Skip for API requests
    if(array_key_exists("Request-Type", $headers) && $headers["Request-Type"] === "api") return false;

    $exceptionPaths = [
        Links::$app->auth->changePassword,
        Links::$app->logout
    ];

    // Check if current path is an exception
    if(in_array(realUrlPath(), $exceptionPaths)) return false;

    // Get user's auth record to check force_password_change flag
    $authRecord = \classes\Methods::localAuthentication()->excludeForeignKeys()->getFirst(['user' => __uuid()]);
    if(isEmpty($authRecord)) return false;

    return (int)$authRecord->force_password_change === 1;
}

function requiresWhitelistedIp(): bool {
    // Skip for non-logged-in users (landing pages, public pages)
    if(!isLoggedIn()) return false;

    $headers = apache_request_headers();
    // Skip for API requests
    if(array_key_exists("Request-Type", $headers) && $headers["Request-Type"] === "api") return false;

    // Only applies to merchants
    if(!\classes\Methods::isMerchant()) return false;

    // Must have an organisation selected
    if(isEmpty(\features\Settings::$organisation?->organisation)) return false;

    $organisation = \features\Settings::$organisation->organisation;
    $generalSettings = $organisation->general_settings ?? (object)[];

    // Check if whitelist is enabled
    $whitelistEnabled = $generalSettings->whitelist_enabled ?? false;
    if(!$whitelistEnabled) return false;

    // Owner is exempt from whitelist check
    $memberRole = \features\Settings::$organisation->role ?? null;
    if($memberRole === 'owner') return false;

    // Exception paths that should always be accessible
    $exceptionPaths = [
        Links::$merchant->organisation->switch,
        Links::$merchant->organisation->add,
        Links::$app->logout,
        Links::$merchant->accessDenied
    ];
    if(in_array(realUrlPath(), $exceptionPaths)) return false;

    // Get user's IP
    $userIp = getUserIp();

    // Get whitelisted IPs
    $whitelistIps = $generalSettings->whitelist_ips ?? [];

    // Check if user's IP is in the whitelist
    return !in_array($userIp, $whitelistIps);
}

function adminImpersonating(): bool {
    return !empty($_SESSION["admin_impersonating_uid"]) &&
        (!empty($_SESSION["admin_impersonating_org"]) || !empty($_SESSION["admin_impersonating_user"]));
}
function getUserIp(): string {
    // Check for forwarded IP (from proxy/load balancer)
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if(!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function scriptStart(): void {
    if (!isset($_SESSION['registered_scripts'])) $_SESSION['registered_scripts'] = [];
    ob_start();
}
function scriptEnd():void {
    $content = ob_get_clean();
    if (!empty($content)) $_SESSION['registered_scripts'][] = $content;
}
function loadRegisteredScripts():void {
    if (isset($_SESSION['registered_scripts']) && !empty($_SESSION['registered_scripts'])) {
        foreach ($_SESSION['registered_scripts'] as $script) echo $script;
        $_SESSION['registered_scripts'] = [];
    }
}











/*
 * LOGGING
 */


function debugLog(mixed $content, string $keyword = "", string $dir = "debug"): void {
    if(is_array($content) || is_object($content)) $content = json_encode($content);
    if(is_bool($content)) $content = $content ? "(BOOLEAN) TRUE" : "(BOOLEAN) FALSE";
    if(!empty($keyword)) $content = " {$keyword}  " . $content;
    $content = "[" . date("Y-m-d H:i:s") . "] - " . $content .PHP_EOL;
    $dir = "logs/$dir/" . date("Y-m") . "/";
    if(!is_dir(ROOT . $dir)) mkdir(ROOT . $dir);
    $fn = date("d") . ".log";
    file_put_contents(ROOT . $dir . $fn, $content, 8);
}
function migrationLog(mixed $content, string $keyword = ""): void {
    $class = "\\features\\Settings";
    if(!class_exists($class)) return;
    if($class::$migrating) debugLog($content, $keyword, "migration");
}



function errorLog(mixed $content, string $keyword = ""): void {
    if(is_array($content) || is_object($content)) $content = json_encode($content);
    if(is_bool($content)) $content = $content ? "(BOOLEAN) TRUE" : "(BOOLEAN) FALSE";
    if(!empty($keyword)) $content = " {$keyword}  " . $content;
    $content = "[" . date("Y-m-d H:i:s") . "] - " . $content .PHP_EOL;
    $dir = "logs/errors/" . date("Y-m") . "/";
    if(!is_dir(ROOT . $dir)) mkdir(ROOT . $dir);
    $fn = date("d") . ".log";
    file_put_contents(ROOT . $dir . $fn, $content, 8);
}


function hookLog(mixed $content, string $keyword = ""): void {
    if(is_array($content) || is_object($content)) $content = json_encode($content);
    if(is_bool($content)) $content = $content ? "(BOOLEAN) TRUE" : "(BOOLEAN) FALSE";
    if(!empty($keyword)) $content = " {$keyword}  " . $content;
    $content = "[" . date("Y-m-d H:i:s") . "] - " . $content .PHP_EOL;
    $dir = "logs/webhook/" . date("Y-m") . "/";
    if(!is_dir(ROOT . $dir)) mkdir(ROOT . $dir);
    $fn = date("d") . ".log";
    file_put_contents(ROOT . $dir . $fn, $content, 8);
}



function testLog(mixed $content, string $fn = "", string $ext = "log"): void {
    if($content instanceof \classes\actors\Media) $content = $content->data();
    if(is_array($content) || is_object($content)) {
        $content = json_encode($content, JSON_PRETTY_PRINT);
        $ext = "json";
    }
    if(is_bool($content)) $content = $content ? "(BOOLEAN) TRUE" : "(BOOLEAN) FALSE";
    if($ext !== "json" && $ext !== "html") $content = "[" . date("Y-m-d H:i:s") . "] - " . $content .PHP_EOL;

    $dir = "logs/test/" . date("Y-m") . "/";
    if(!is_dir(ROOT . $dir)) mkdir(ROOT . $dir);
    $dir = $dir . date("d") . "/";
    if(!is_dir(ROOT . $dir)) mkdir(ROOT . $dir);
    $fn .= ".$ext";
    file_put_contents(ROOT . $dir . $fn, $content, (in_array($ext, ["json", "html"]) ? 0 : 8));
}

function scraperLog(mixed $content, string $fn = "", string $ext = "html"): string {
    if(is_array($content) || is_object($content)) {
        $content = json_encode($content, JSON_PRETTY_PRINT);
        $ext = "json";
    }
    if(is_bool($content)) $content = $content ? "(BOOLEAN) TRUE" : "(BOOLEAN) FALSE";
    if($ext !== "json" && $ext !== "html") $content = "[" . date("Y-m-d H:i:s") . "] - " . $content .PHP_EOL;
    $dir = "logs/scraper/" . date("Y-m-d") . "/";
    if(!is_dir(ROOT . $dir)) mkdir(ROOT . $dir);
    $fn .= ".$ext";
    file_put_contents(ROOT . $dir . $fn, $content, (in_array($ext, ["json", "html"]) ? 0 : 8));
    return $dir . $fn;
}

function cronLog(mixed $content, string $fn, bool $append = true): void {
    if(is_bool($content)) $content = $content ? "(BOOLEAN) TRUE" : "(BOOLEAN) FALSE";
    file_put_contents($fn, $content . PHP_EOL, ($append ? 8 : 0));
}

















