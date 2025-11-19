<?php

namespace classes\api;
use classes\http\Requests;
use env\api\OAI;

class OpenAi {




    private function build(): Requests {
        $handler = new Requests();
        $handler->setBearerToken(OAI::API_KEY);
        $handler->setHeaderContentTypeJson();
        return $handler;
    }

    public function imageAndTextPrompt(string $imageUrl, string $text, string $systemText = ''): ?array {
        $model = "gpt-4o";
        $payload = [
            "model" => $model,
            "messages" => []
        ];
        if (!empty($systemText)) {
            $payload["messages"][] = [
                "role" => "system",
                "content" => $systemText
            ];
        }
        $payload["messages"][] = [
            "role" => "user",
            "content" => [
                ["type" => "text", "text" => $text],
                ["type" => "image_url", "image_url" => ["url" => $imageUrl]]
            ]
        ];
        $payload["max_tokens"] = 500; // Increased from 300 to handle detailed JSON

        $handler = $this->build();
        $handler->post(OAI::COMPLETION_ENDPOINT, $payload);
        $response = $handler->getResponse();
        debugLog($response, "openai-image-text-prompt");
        $response = is_array($response) ? $response : null;
        $response = nestedArray($response, ["choices", 0, "message", "content"]);
        if (is_string($response)) {
            $response = str_replace(['```json', '```'], '', $response);
            $response = json_decode($response, true);
        }
        return is_array($response) ? $response : null;
    }

//    public function imageAndTextPrompt(string $imageUrl, string $text): ?array {
//        $model = "gpt-4o-mini";
//        $payload = [
//            "model" => $model,
//            "messages" => [
//                [
//                    "role" => "user",
//                    "content" => [
//                        [
//                            "type" => "text",
//                            "text" => $text
//                        ],
//                        [
//                            "type" => "image_url",
//                            "image_url" => [
//                                "url" => $imageUrl
//                            ]
//                        ]
//                    ]
//                ]
//            ],
//            "max_tokens" => 300
//        ];
//
//
//        $handler = $this->build();
//        $handler->post(OAI::COMPLETION_ENDPOINT, $payload);
//        $response = $handler->getResponse();
//        debugLog($response, "openai-image-text-prompt");
//        $response = is_array($response) ? $response : null;
//        $response = nestedArray($response, ["choices", 0, "message", "content"]);
//        if(is_string($response)) {
//            $response = str_replace(['```json', '```'], '', $response);
//            $response = json_decode($response, true);
//        }
//        return is_array($response) ? $response : null;
//    }










    public function textPrompt(string $userText, string $systemText = "", int $maxTokens = 1000, string $model = "gpt-3.5-turbo"): null|string|array {
        $payload = [
            "model" => $model,
            "messages" => [
                [
                    "role" => "user",
                    "content" => $userText
                ]
            ],
            "max_tokens" => $maxTokens
        ];
        if(!empty($systemText)) {
            $payload["messages"] = array_merge(
                [[
                    "role" => "system",
                    "content" => $systemText
                ]],
                $payload["messages"]
            );
        }


        $handler = $this->build();
        $handler->post(OAI::COMPLETION_ENDPOINT, $payload);
        $response = $handler->getResponse();
        debugLog($response, "openai-text-prompt");
        testLog($response, "openai-text-prompt");

        $response = is_array($response) ? $response : null;
        $response = nestedArray($response, ["choices", 0, "message", "content"]);
        if(is_string($response)) {
            $result = preg_replace('/\s*([{}\[\]"])\\s*/', '$1', $response);
            $result = preg_replace('/\s+/', ' ', trim($result));
            $result = trim($result, "\"");
            $result = stripslashes($result);
            testLog([$result], "trmmed-preg");
            $result = json_decode($result, true);
            testLog($result, "trmmed1-preg");
            if (json_last_error() === JSON_ERROR_NONE) return $result;
        }
        return $response;
    }












}