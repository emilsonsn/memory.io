<?php

namespace App\Clients\AI;

use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class OpenAIChatClient
{
    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function chat(array $messages): array
    {
        if ((string) config('openai.api_key') === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $response = OpenAI::chat()->create([
            'model' => (string) config('openai.chat_model', 'gpt-4.1-mini'),
            'temperature' => 0.1,
            'max_tokens' => (int) config('openai.max_output_tokens', 350),
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => $messages,
        ]);

        return [
            'content' => (string) ($response->choices[0]->message->content ?? ''),
            'prompt_tokens' => $response->usage->promptTokens ?? null,
            'completion_tokens' => $response->usage->completionTokens ?? null,
            'total_tokens' => $response->usage->totalTokens ?? null,
        ];
    }
}
