<?php

namespace classes\http;

class Proxy {


    public function run(array $args): array {
        if(!array_key_exists("url", $args)) return ["status" => "error", "error" => ["message" => "Missing url"]];
        $url = $args["url"];
        $method = array_key_exists("method", $args) ? strtoupper($args["method"]) : "GET";
        $payload = array_key_exists("payload", $args) ? $args["payload"] : [];
        $headers = array_key_exists("headers", $args) ? $args["headers"] : [];
        $doClean = array_key_exists("return_type", $args) ? $args["return_type"] : "string";

        $handler = (new Requests())->request($method, $url, $payload, $headers);
        return [
            "status" => "success",
            "headers" => $handler->getHeaders(),
            "body" => $handler->getResponse(($doClean === "array"))
        ];
    }









}