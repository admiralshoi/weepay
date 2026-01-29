<?php

namespace routing\routes\api;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;

class ContentController {



    #[NoReturn] public static function getTemplateModal(array $args): void  {
        $name = array_key_exists("name", $args) ? $args["name"] : "";
        $file = __view("templates.modals.$name.html", "html");
        if(!file_exists($file)) Response()->html(null, 400);
        Response()->html(file_get_contents($file));
    }


    #[NoReturn] public static function getTemplateElement(array $args): void  {
        $name = array_key_exists("name", $args) ? $args["name"] : "";
        $file = __view("templates.elements.$name.html", "html");
        if(!file_exists($file)) Response()->html(null, 400);
        Response()->html(file_get_contents($file));
    }


    /**
     * Proxy endpoint for fetching external URLs (for media info detection)
     * Used by fetchMediaInfo in features.js
     */
    #[NoReturn] public static function proxy(array $args): void {
        $url = $args['url'] ?? null;
        $method = strtoupper($args['method'] ?? 'HEAD');

        if (isEmpty($url)) {
            Response()->jsonError("URL is required", [], 400);
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Response()->jsonError("Invalid URL", [], 400);
        }

        // Only allow HEAD and GET for security
        if (!in_array($method, ['HEAD', 'GET'])) {
            Response()->jsonError("Method not allowed", [], 405);
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'WeePay/1.0');

            if ($method === 'HEAD') {
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

            if (curl_errno($ch)) {
                curl_close($ch);
                Response()->jsonError("Failed to fetch URL: " . curl_error($ch), [], 500);
            }

            curl_close($ch);

            Response()->jsonSuccess("OK", [
                'http_code' => $httpCode,
                'content_type' => $contentType,
                'content_length' => $contentLength
            ]);
        } catch (\Exception $e) {
            Response()->jsonError("Proxy error: " . $e->getMessage(), [], 500);
        }
    }

}