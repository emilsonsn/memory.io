<?php

namespace Tests\Feature;

use App\Clients\AI\OpenAIChatClient;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_chat_endpoint(): void
    {
        $this->postJson('/api/chat/messages', [
            'message' => 'qual o numero daquela mulher?',
        ])->assertUnauthorized();
    }

    public function test_user_can_ask_chat_and_get_answer_from_memories_with_sources(): void
    {
        config()->set('openai.api_key', 'test-key');

        $user = User::factory()->create();

        $memory = Memory::factory()->for($user)->create([
            'title' => 'Contato Maria',
            'content' => 'Telefone da Maria: 11999998888',
        ]);

        $this->instance(OpenAIChatClient::class, new class($memory) extends OpenAIChatClient
        {
            public function __construct(private readonly Memory $memory) {}

            public function chat(array $messages): array
            {
                return [
                    'content' => json_encode([
                        'answer' => 'O numero e 11999998888.',
                        'memory_ids' => [(string) $this->memory->id],
                    ], JSON_THROW_ON_ERROR),
                    'prompt_tokens' => 120,
                    'completion_tokens' => 22,
                    'total_tokens' => 142,
                ];
            }
        });

        $this->actingAs($user, 'api')
            ->postJson('/api/chat/messages', [
                'message' => 'qual o numero daquela mulher?',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assistant_message.content', 'O numero e 11999998888.')
            ->assertJsonFragment([
                'id' => $memory->id,
                'title' => 'Contato Maria',
            ]);
    }

    public function test_chat_falls_back_when_model_returns_invalid_json(): void
    {
        config()->set('openai.api_key', 'test-key');

        $this->instance(OpenAIChatClient::class, new class extends OpenAIChatClient
        {
            public function chat(array $messages): array
            {
                return [
                    'content' => 'not-json',
                    'prompt_tokens' => null,
                    'completion_tokens' => null,
                    'total_tokens' => null,
                ];
            }
        });

        $user = User::factory()->create();
        Memory::factory()->for($user)->create([
            'title' => 'Any note',
            'content' => 'Any content',
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/chat/messages', [
                'message' => 'qual o numero?',
            ])
            ->assertOk()
            ->assertJsonPath('data.assistant_message.content', 'Nao encontrei essa informacao nas suas memorias.');
    }
}
