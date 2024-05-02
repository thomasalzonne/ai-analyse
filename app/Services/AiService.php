<?php

namespace App\Services;

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Illuminate\Support\Facades\Log;

class AiService {

    /**
     * Retrieves an answer from Claude based on the given prompt.
     */
    public function ask(string $message, string $system = '')
    {
        $messages = [
            [
                'role' => 'user',
                'content' => $message,
            ],
        ];
        $client = new BedrockRuntimeClient([
            'region' => config('anthropic.aws_region', 'us-east-1'),
        ]);
        
        $body = json_encode([
            'max_tokens' => 50000,
            'system' => $system,
            'messages' => $messages,
            'temperature' => 0.5,
            'top_k' => 250,
            'top_p' => 1,
            'anthropic_version' => config('anthropic.aws_anthropic_version', 'bedrock-2023-05-31'),
        ]);

        $payload = [
            'body' => $body,
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'modelId' => config('anthropic.aws_anthropic_model_id', 'anthropic.claude-3-haiku-20240307-v1:0'), //'anthropic.claude-3-sonnet-20240229-v1:0', //
        ];

        $response = $client->invokeModel($payload);

        $body = json_decode($response['body']->__toString(), true);
        $completion = end($body['content'])['text'];
        $metadata = $response['@metadata']['headers'];
        $inputTokens = $metadata['x-amzn-bedrock-input-token-count'];
        $outputTokens = $metadata['x-amzn-bedrock-output-token-count'];

        $answer = compact('completion', 'metadata', 'inputTokens', 'outputTokens');

        return $answer;
    }
}
