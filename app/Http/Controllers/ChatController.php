<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chat\AskChatRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatSessionResource;
use App\Models\ChatSession;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function ask(AskChatRequest $request): JsonResponse
    {
        $result = $this->chatService->ask(
            user: auth()->user(),
            question: (string) $request->validated('message'),
            chatSessionId: $request->validated('chat_session_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Chat response generated successfully.',
            'data' => [
                'session' => ChatSessionResource::make($result['session']),
                'assistant_message' => ChatMessageResource::make($result['assistant_message']),
            ],
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $sessions = $this->chatService->listSessions($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Chat sessions retrieved successfully.',
            'data' => ChatSessionResource::collection($sessions),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function messages(Request $request, ChatSession $chatSession): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 20), 100));
        $messages = $this->chatService->listMessages($chatSession, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Chat messages retrieved successfully.',
            'data' => ChatMessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }
}
