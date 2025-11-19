<?php

namespace classes\http;

use JetBrains\PhpStorm\Pure;

class Requests {

    private const ALLOWED_METHODS = ["GET","POST", "PUT", "PATCH", "DELETE", "HEAD"];

    private array $headers =  [];
    private string $method = "GET";
    private string $url = "";
    private array|string $payload = [];
    private mixed $responseBody = null;
    private string|array|null $responseHeaders = null;
    private array $errors = [];
    private int $responseCode = 500;


    private bool $returnTransfer = true;
    private int $timeout = 500;
    private int $followLocation = 1;
    private bool $withCookies = false;



    function __construct() {
        $this->setDefaults();
    }

    public static function init(): static {
        return new static();
    }


    public function isWithCookies(): bool { return $this->withCookies; }
    public function withCookies(): void { $this->withCookies = true; }
    public function setMethod(string $method): void { if(in_array(strtoupper($method), self::ALLOWED_METHODS)) $this->method = strtoupper($method); }
    public function setUrl(string $url): void { $this->url = $url; }
    public function setBody(string|array $payload): void { $this->payload = $payload; }
    public function setHeaders(array $headers): void { $this->headers = $headers; }
    public function addHeader(string $value, string $key = ""): void { if(!empty($key)) {$this->headers[$key] = $value; } else {$this->headers[] = $value;} }
    public function basicAuth(string $username, string $password): void { $this->addHeader("Authorization: Basic " . base64_encode("$username:$password")); }
    public function setAuthorization(?string $auth): void { $this->addHeader("Authorization: $auth", "authorization"); }
    public function setBearerToken(string $token): void { $this->addHeader("Authorization: Bearer $token", "authorization"); }
    public function setHeaderContentTypeJson(): void { $this->addHeader('Content-type: application/json; charset=UTF-8', "content_type"); }
    public function setHeaderContentTypeFormEncoded(): void { $this->addHeader('Content-type: application/x-www-form-urlencoded', "content_type"); }



    public function post(string $url = "", array|string $payload = [], array $headers = []): static {
        return $this->request("POST", $url, $payload, $headers);
    }
    public function head(string $url = "", array|string $payload = [], array $headers = []): static {
        return $this->request("HEAD", $url, $payload, $headers);
    }
    public function put(string $url = "", array|string $payload = [], array $headers = []): static {
        return $this->request("PUT", $url, $payload, $headers);
    }
    public function patch(string $url = "", array|string $payload = [], array $headers = []): static {
        return $this->request("PATCH", $url, $payload, $headers);
    }
    public function get(string $url = "", array|string $payload = [], array $headers = []): static {
        return $this->request("GET", $url, $payload, $headers);
    }
    public function delete(string $url = "", array|string $payload = [], array $headers = []): static {
        return $this->request("DELETE", $url, $payload, $headers);
    }
    public function request(string $method = "", string $url = "", array|string $payload = [], array $headers = []): static {
        if(empty($url)) $url = $this->url;
        if(empty($method)) $method = $this->method;
        if(empty($url)) return $this->setError("No url provided.");
        if(empty($method)) return $this->setError("No method provided.");

        $this->method = $method;
        $this->url = $url;
        if(!empty($payload)) $this->payload = $payload;
        if(!empty($headers)) $this->headers = $headers;

        if(!in_array($method, self::ALLOWED_METHODS)) return $this->setError("Invalid method: $method");
        return $this->send();
    }



    private function send(): static {
        if(empty($this->url)) return $this->setError("Missing url.");
        if(empty($this->method)) return $this->setError("Missing method.");
        if($this->withCookies) {
            if(!Cookies::isUnusedCookies()) return $this->setError("No more available cookies.");
            list($cookie, $csrf) = array_map("trim", Cookies::cookieGetCsrf());
            $this->headers["cookie"] = $cookie;
            $this->headers["x-csrftoken"] = $csrf;
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch,$this->setCurlOptions());
            testLog($this->setCurlOptions(), "raw-send-headers");
            $response = curl_exec($ch);
            $this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->responseHeaders = $this->get_headers_from_curl_response(substr($response, 0, $header_size));
            $this->responseBody = substr($response, $header_size);

            if ($this->responseCode >= 400) {
                $this->setError([
                    "message" => "Http code $this->responseCode returned",
                    "error_code" => $this->responseCode,
                    "data" => $this->responseBody
                ]);
            }

        } catch (\Exception $exception) {
            $this->setError(["message" => $exception->getMessage(), "trace" => $exception->getTraceAsString(), "error_code" => $exception->getCode()]);
        }
        return $this;
    }

    function get_headers_from_curl_response($response): array {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }


    private function processPayload(): string|array {
        if(!in_array($this->method, ["POST", "PUT", "DELETE", "PATCH"])) return $this->payload;
        if(!array_key_exists("content_type", $this->headers)) return $this->payload;
        if(str_contains($this->headers["content_type"], "x-www-form-urlencoded")) return http_build_query($this->payload);
        if(str_contains($this->headers["content_type"], "application/json") && is_array($this->payload)) return json_encode($this->payload);
        return $this->payload;
    }

    private function setCurlOptions(): array {
        $curlOpt = array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => $this->returnTransfer,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_FOLLOWLOCATION => $this->followLocation,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => array_values($this->headers),
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTP_VERSION => "CURL_HTTP_VERSION_1_1",
        );
        if(in_array($this->method, ["POST", "PUT", "DELETE", "PATCH"])) $curlOpt[CURLOPT_POSTFIELDS] = $this->processPayload();
        if($this->method === "HEAD") $curlOpt[CURLOPT_NOBODY] = true;
        return $curlOpt;
    }


    public function getResponse(bool $clean = true): mixed {
        if($this->isError()) return ["status" => "error", "errors" => $this->getErrors()];

        if(!is_array($this->responseBody)) {
            $res = $this->responseBody;
            return $clean && !empty($res) && !is_array($res) ? json_decode($res, true) : $res;
        }
        $res = [];
        foreach ($this->responseBody as $item) $res[] = $clean && !empty($item) && !is_array($item) ? json_decode($item, true) : $item;
        return $res;
    }

    public function getRequestHeaders(): array { return $this->headers; }
    public function getHeaders(): string|array|null     {
        return $this->responseHeaders;
    }
    public function getResponseCode(): int { return $this->responseCode; }

    public function clear(): void { $this->setDefaults(); }
    #[Pure] public function getErrorMessage(): ?string {
        if(!$this->isError()) return null;
        $error = $this->getErrors()[0];
        return is_array($error) ? $error["message"] : $error;
    }
    public function getErrors(): array {return $this->errors; }
    #[Pure] public function isError(): bool {return !empty($this->getErrors());}
    private function setError(string|array $error): static {
        if(is_string($error)) $this->errors[] = ["message" => $error];
        else $this->errors[] = $error;
        return $this;
    }

    public function clearResponse(): void {
        $this->errors = [];
        $this->responseBody = null;
        $this->responseHeaders = null;
        $this->responseCode = 500;
    }


    private function setDefaults(): void {
        $currentHeaders = $this->headers;
        $this->headers = [
            "Origin: " . HOST,
            "Referer: " . HOST,
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
        ];
        $importantKeys = ["authorization", "content-type"];
        foreach ($currentHeaders as $key => $header) {
            foreach ($importantKeys as $importantKey) {
                if(str_contains(strtolower($header), $importantKey)) $this->addHeader($header, $key);
            }
        }


        $this->payload = [];
        $this->url = "";
        $this->method = "GET";
        $this->responseBody = null;
        $this->responseHeaders = null;
        $this->errors = [];
        $this->returnTransfer = true;
        $this->timeout = 500;
        $this->followLocation = 1;
    }




}