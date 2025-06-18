<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(): JsonResponse
    {
        $chats = Chat::with(['user', 'product', 'messages.user'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($chats);
    }

    public function createChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
        ]);

        $chat = Chat::create([
            'user_id' => Auth::id(),
            'product_id' => $validated['product_id'] ?? null,
        ]);

        return response()->json($chat);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'content' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'chat_id' => $validated['chat_id'],
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        return response()->json($message);
    }

    public function getChatMessages($chatId): JsonResponse
    {
        $chat = Chat::with(['messages.user'])
            ->where('id', $chatId)
            ->firstOrFail();

        return response()->json($chat->messages);
    }
}
