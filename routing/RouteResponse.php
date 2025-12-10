<?php

namespace routing;

use classes\utility\Titles;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;

class RouteResponse {

    private ?string $redirectUrl = null;
    private bool $redirect = false;

    #[NoReturn] public function redirect(string $path, string $url = ""): void {
        if(empty($url)) $url = __url($path);
        http_response_code(301);
        header("Location: $url");
        exit;
    }

    #[NoReturn] public function refresh(int $time = 0): void {
        http_response_code(200);
        header("Refresh:$time");
        exit;
    }

    #[NoReturn] public function json(string|array|null $res, int $responseCode = 200): void {
        printJson($res, $responseCode);
    }

    #[NoReturn] public function mimeType(mixed $res, string $mimeType, int $responseCode = 200): void {
        printMimeType($res, $mimeType, $responseCode);
    }


    #[NoReturn] public function html(?string $res, int $responseCode = 200): void {
        printHtml($res, $responseCode);
    }


    #[NoReturn] public function e404Json(): void {
        $this->jsonError("Page not found.", [], 404);
    }

    #[NoReturn] public function e401Json(): void {
        $this->jsonError("Unauthorized.", [], 401);
    }

    public function setRedirect(string $redirectUri  = ""): static {
        if(!empty($redirectUri)) $this->redirectUrl = $redirectUri;
        $this->redirect = true;
        return $this;
    }

    #[NoReturn] public function jsonError(string $message, array $data = [], int $responseCode = 200): void {
        if($this->redirect) {
            $data["redirect"] = $this->redirect;
            if(!empty($this->redirectUrl)) $data["redirect_uri"] = $this->redirectUrl;
        }
        $this->json(["status" => "error", "error" => array_merge(["message" => $message], $data)], $responseCode);
    }
    #[NoReturn] public function jsonSuccess(string $message, array $data = [], int $responseCode = 200): void {
        if($this->redirect) {
            $data["redirect"] = $this->redirect;
            if(!empty($this->redirectUrl)) $data["redirect_uri"] = $this->redirectUrl;
        }
        $this->json(["status" => "success", "message" => $message, "data" => $data], $responseCode);
    }

    #[ArrayShape(["status" => "string", "error" => "array|object", "code" => "integer"])]
    public function arrayError(string $message, array|object $data = [], int $code = 200): array {
        return ["status" => "error", "error" => array_merge(["message" => $message], toArray($data)), "code" => $code];
    }
    #[ArrayShape(["status" => "string", "message" => "string", "data" => "array|object", "code" => "integer"])]
    public function arraySuccess(string $message, array|object $data = [], int $code = 200): array {
        return ["status" => "success", "message" => $message, "data" => $data, "code" => $code];
    }

    #[NoReturn] public function jsonPermissionError(string $type, string $object, array $data = []): void {
        $object = Titles::cleanUcAll($object);
        $this->jsonError("Du har ikke tilladelse til at udøve '$type-handlinger' på objektet '$object'", $data, 401);
    }

}