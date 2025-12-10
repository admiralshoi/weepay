<?php
namespace routing;

use classes\enumerations\Links;
use Exception;
use features\Settings;
use routing\routes\auth\PageController;
use routing\routes\ErrorController;
use routing\routes\LandingController;

class Routes {

    private static bool $pathIsset = false;
    private static string $path = "";
    private static array $groupMiddleware = [];
    private static array $routes = [];

    public static function any(string $routePath, string|array $classMethod, array $middleware = []): void {
        self::addRoute('any', $routePath, $classMethod, $middleware);
    }
    public static function get(string $routePath, string|array $classMethod, array $middleware = []): void {
        self::addRoute('get', $routePath, $classMethod, $middleware);
    }

    public static function post(string $routePath, string|array $classMethod, array $middleware = []): void {
        self::addRoute('post', $routePath, $classMethod, $middleware);
    }

    public static function delete(string $routePath, string|array $classMethod, array $middleware = []): void {
        self::addRoute('delete', $routePath, $classMethod, $middleware);
    }

    public static function patch(string $routePath, string|array $classMethod, array $middleware = []): void {
        self::addRoute('patch', $routePath, $classMethod, $middleware);
    }

    private static function addRoute(string $method, string $routePath, string|array $classMethod, array $middleware): void {
        $middleware = array_merge(self::$groupMiddleware, $middleware);
        self::$routes[] = ['method' => $method, 'path' => $routePath, 'handler' => $classMethod, 'middleware' => $middleware];
    }

    public static function group(array $middleware, callable $routes) {
        $previousGroupMiddleware = self::$groupMiddleware;
        self::$groupMiddleware = array_merge(self::$groupMiddleware, $middleware);
        call_user_func($routes);
        self::$groupMiddleware = $previousGroupMiddleware;
    }

    public static function dispatch() {
        $path = self::getPath();
        $method = strtolower($_SERVER["REQUEST_METHOD"]);
        debugLog($path, "request-path");

        if(requiresOrganisation()) {
            header("location: " . __url(Links::$merchant->organisation->add));
            exit;
        }
        if(requiresSelectedOrganisation()) {
            header("location: " . __url(Links::$merchant->organisation->home));
            exit;
        }
        if(requiresSelectedOrganisationWallet()) {
            header("location: " . __url(Links::$merchant->organisation->home));
            exit;
        }

        $matches = [];
        foreach (self::$routes as $index => $route) {
            $params = [];
            if (in_array($route["method"], ['any', $method]) && self::matchRoute($route['path'], $path, $params)) {
                $matches[] = ['index' => $index, 'route' => $route, 'params' => $params];
            }
        }

        if (empty($matches)) {
            printView(ErrorController::e404());
            return;
        }

        // Sort matches: prioritize fewer dynamic segments first, then by original index
        usort($matches, function($a, $b) {
            $priA = substr_count($a['route']['path'], '{');
            $priB = substr_count($b['route']['path'], '{');
            if ($priA !== $priB) {
                return $priA - $priB;
            }
            return $a['index'] - $b['index'];
        });

        foreach ($matches as $match) {
            $route = $match['route'];
            $params = $match['params'];
            $middleware = $route['middleware'];
            $pass = true;
            $failedMiddlewares = [];

            // Execute middleware in order, tracking which one fails
            foreach ($middleware as $mw) {
                if (is_callable($mw)) {
                    $pass = call_user_func($mw, $params);
                    if (!$pass) {
                        $failedMiddlewares[] = $mw;
                    }
                } else {
                    throw new Exception("Invalid middleware: $mw");
                }
            }

            $args = array_merge($_GET, Settings::$postData, $params);

            if ($pass) {
                if (!is_array($route['handler'])) $route['handler'] = explode("::", $route['handler']);
                if (count($route['handler']) < 2) throw new Exception("The handler must have both the class and method.");
                $result = self::returnMethod($route['handler'][0], $route['handler'][1], $args);


                $responseCode = 200;
                if (is_array($result)) {
                    http_response_code($responseCode);
                    if (array_key_exists("return_as", $result) && $result["return_as"] === "view") {
                        printView($result);
                        return;
                    }
                    if (array_key_exists("return_as", $result) && $result["return_as"] === "html") {
                        printHtml($result["result"]);
                        return;
                    }
                    if (array_key_exists("return_as", $result) && $result["return_as"] === 404) {
                        printView(ErrorController::e404());
                        return;
                    }
                    printJson($result["result"], $result["response_code"]);
                }
                if($result === null) {
                    if(!str_starts_with($route['path'], 'api')) {
                        printView(ErrorController::e404());
                        return;
                    }
                    else Response()->e401Json();
                }
                return;
            } else {
                if(in_array("requiresLogin", $failedMiddlewares)) {
                    if(in_array("merchant", $failedMiddlewares)) printView(PageController::merchantDashboardLogin($args));
                    else printView(PageController::consumerDashboardLogin($args));
                } elseif(in_array("requiresApiLogin", $failedMiddlewares)) {
                    Response()->e401Json();
                } elseif(in_array("requiresApiLogout", $failedMiddlewares)) {
                    Response()->e404Json();
                }
                // If not a handled failure, continue to next match
            }
        }

        printView(ErrorController::e404());
    }

    private static function matchRoute(string $routePath, string $path, array &$params): bool {
        $routePath = trimPath($routePath);
        $path = trimPath($path);
        if ($routePath === '' && $path === '') return true;

        // Convert route path to regex pattern
        $pattern = preg_replace_callback('/\{(\w+)\}/', function($matches) use (&$params) {
            $params[$matches[1]] = null;
            return '([^\/]+)';
        }, $routePath);

        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {

            array_shift($matches); // Remove full match
            $i = 0;
            foreach ($params as $key => &$value) {
                $value = $matches[$i++];
            }
            return true;
        }


        return false;
    }

    private static function getPath(): ?string {
        if (self::$pathIsset) return self::$path;
        self::$path = realUrlPath();
        self::$pathIsset = true;
        return self::$path;
    }

    private static function returnMethod(string $className, string $classMethodName, array $args): mixed {
        if (!str_starts_with($className, "routing.routes.")) $className = "routing.routes.$className";
        $className = str_replace(".", "\\", $className);

        if (!class_exists($className)) {
            throw new Exception("Invalid class: $className");
        }
        if (!method_exists($className, $classMethodName)) {
            throw new Exception("Invalid class method: $classMethodName on class $className.");
        }

        return call_user_func(implode("::", [$className, $classMethodName]), $args);
    }
}