<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Product;
use App\Events\MessageSent;
use App\Services\WebSocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $webSocketService;

    public function __construct(WebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        // Получаем чаты где пользователь является либо покупателем, либо продавцом
        $chats = Chat::with(['user', 'seller', 'product', 'messages.user'])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->latest()
            ->get();

        // Добавляем информацию о собеседнике для каждого чата
        $chats->each(function ($chat) use ($userId) {
            $chat->other_participant = $chat->getOtherParticipant($userId);
            $chat->is_buyer = $chat->user_id == $userId;
            $chat->is_seller = $chat->seller_id == $userId;
        });

        return response()->json($chats);
    }

    public function createChat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();
        $product = Product::findOrFail($validated['product_id']);

        // Проверяем, что пользователь не пытается создать чат со своим товаром
        if ($product->user_id == $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot create a chat with your own product'
            ], 400);
        }

        // Проверяем, существует ли уже чат между этими пользователями для этого товара
        $existingChat = Chat::where('user_id', $userId)
            ->where('seller_id', $product->user_id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingChat) {
            return response()->json([
                'status' => 'success',
                'message' => 'Chat already exists',
                'data' => $existingChat->load(['user', 'seller', 'product'])
            ]);
        }

        // Создаем новый чат
        $chat = Chat::create([
            'user_id' => $userId,
            'seller_id' => $product->user_id,
            'product_id' => $product->id,
        ]);

        $chat->load(['user', 'seller', 'product']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chat created successfully',
            'data' => $chat
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'content' => 'required|string|max:1000',
        ]);

        // Проверяем, что пользователь является участником чата
        $chat = Chat::where('id', $validated['chat_id'])
            ->where(function ($query) {
                $userId = Auth::id();
                $query->where('user_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        $message = Message::create([
            'chat_id' => $validated['chat_id'],
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        // Загружаем связанные данные
        $message->load(['user:id,name,avatar']);

        // Отправляем через WebSocket сервис
        $this->webSocketService->sendMessageToChat($validated['chat_id'], $message);

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent successfully',
            'data' => $message
        ]);
    }

    public function getChatMessages($chatId): JsonResponse
    {
        $userId = Auth::id();

        $chat = Chat::with(['messages.user:id,name,avatar'])
            ->where('id', $chatId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $chat->messages
        ]);
    }

    /**
     * Join chat room for realtime updates
     */
    public function joinChat($chatId): JsonResponse
    {
        $userId = Auth::id();

        $chat = Chat::where('id', $chatId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully joined chat',
            'data' => [
                'chat_id' => $chat->id,
                'channel' => "chat.{$chat->id}",
                'user' => Auth::user()->only(['id', 'name', 'avatar']),
                'is_buyer' => $chat->user_id == $userId,
                'is_seller' => $chat->seller_id == $userId
            ]
        ]);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $chatId): JsonResponse
    {
        $validated = $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id'
        ]);

        $chat = Chat::where('id', $chatId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Здесь можно добавить логику для отметки сообщений как прочитанных
        // Например, создать таблицу message_reads или добавить поле read_at в messages

        return response()->json([
            'status' => 'success',
            'message' => 'Messages marked as read'
        ]);
    }

    /**
     * Server-Sent Events stream for realtime messages
     */
    public function streamMessages($chatId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $chat = Chat::where('id', $chatId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return response()->stream(function () use ($chatId) {
            $lastMessageId = request()->get('last_message_id', 0);

            while (true) {
                // Получаем новые сообщения
                $newMessages = Message::where('chat_id', $chatId)
                    ->where('id', '>', $lastMessageId)
                    ->with(['user:id,name,avatar'])
                    ->orderBy('id')
                    ->get();

                if ($newMessages->count() > 0) {
                    foreach ($newMessages as $message) {
                        echo "data: " . json_encode([
                            'type' => 'message',
                            'data' => [
                                'id' => $message->id,
                                'chat_id' => $message->chat_id,
                                'content' => $message->content,
                                'user' => $message->user,
                                'created_at' => $message->created_at->toISOString(),
                            ]
                        ]) . "\n\n";

                        $lastMessageId = $message->id;
                    }

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }

                // Проверяем каждые 2 секунды
                sleep(2);

                // Проверяем, не закрыто ли соединение
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]);
    }

    /**
     * Polling endpoint for getting new messages
     */
    public function pollMessages(Request $request, $chatId): JsonResponse
    {
        $chat = Chat::where('id', $chatId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $lastMessageId = $request->get('last_message_id', 0);

        // Сначала пробуем получить из кеша (быстрее)
        $cachedMessages = $this->webSocketService->getNewMessages($chatId, $lastMessageId);

        if (!empty($cachedMessages)) {
            return response()->json([
                'status' => 'success',
                'data' => array_values($cachedMessages),
                'has_new_messages' => true,
                'last_message_id' => end($cachedMessages)['id'],
                'source' => 'cache'
            ]);
        }

        // Если в кеше нет, получаем из базы данных
        $newMessages = Message::where('chat_id', $chatId)
            ->where('id', '>', $lastMessageId)
            ->with(['user:id,name,avatar'])
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $newMessages,
            'has_new_messages' => $newMessages->count() > 0,
            'last_message_id' => $newMessages->last()?->id ?? $lastMessageId,
            'source' => 'database'
        ]);
    }

    /**
     * Delete entire chat with all messages
     */
    public function deleteChat($chatId): JsonResponse
    {
        $userId = Auth::id();

        $chat = Chat::where('id', $chatId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        // Удаляем все сообщения чата
        $chat->messages()->delete();

        // Удаляем сам чат
        $chat->delete();

        // Уведомляем через WebSocket о удалении чата
        $this->webSocketService->notifyChatDeleted($chatId, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Chat deleted successfully'
        ]);
    }

    /**
     * Clear all messages in chat (keep chat but remove all messages)
     */
    public function clearChat($chatId): JsonResponse
    {
        $chat = Chat::where('id', $chatId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Удаляем все сообщения чата, но оставляем сам чат
        $messagesCount = $chat->messages()->count();
        $chat->messages()->delete();

        // Уведомляем через WebSocket об очистке чата
        $this->webSocketService->notifyChatCleared($chatId, Auth::id());

        return response()->json([
            'status' => 'success',
            'message' => 'Chat cleared successfully',
            'data' => [
                'chat_id' => $chat->id,
                'deleted_messages_count' => $messagesCount
            ]
        ]);
    }

    /**
     * Delete all chats for current user
     */
    public function deleteAllChats(): JsonResponse
    {
        $userId = Auth::id();

        // Получаем все чаты пользователя (как покупателя и как продавца)
        $chats = Chat::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhere('seller_id', $userId);
        })->get();

        if ($chats->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No chats found to delete',
                'data' => [
                    'deleted_chats_count' => 0,
                    'deleted_messages_count' => 0
                ]
            ]);
        }

        $deletedChatsCount = $chats->count();
        $deletedMessagesCount = 0;

        // Удаляем все сообщения и чаты
        foreach ($chats as $chat) {
            $messagesCount = $chat->messages()->count();
            $deletedMessagesCount += $messagesCount;

            // Удаляем сообщения чата
            $chat->messages()->delete();

            // Уведомляем через WebSocket об удалении чата
            $this->webSocketService->notifyChatDeleted($chat->id, $userId);
        }

        // Удаляем все чаты пользователя
        Chat::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhere('seller_id', $userId);
        })->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All chats deleted successfully',
            'data' => [
                'deleted_chats_count' => $deletedChatsCount,
                'deleted_messages_count' => $deletedMessagesCount
            ]
        ]);
    }

    /**
     * Delete specific message
     */
    public function deleteMessage($chatId, $messageId): JsonResponse
    {
        $userId = Auth::id();

        // Проверяем, что пользователь является участником чата
        $chat = Chat::where('id', $chatId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->firstOrFail();

        // Находим сообщение в этом чате
        $message = Message::where('id', $messageId)
            ->where('chat_id', $chatId)
            ->firstOrFail();

        // Проверяем, что пользователь является автором сообщения
        if ($message->user_id !== $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only delete your own messages'
            ], 403);
        }

        // Сохраняем данные сообщения для ответа
        $messageData = [
            'id' => $message->id,
            'content' => $message->content,
            'chat_id' => $message->chat_id,
            'created_at' => $message->created_at
        ];

        // Удаляем сообщение
        $message->delete();

        // Уведомляем через WebSocket об удалении сообщения
        $this->webSocketService->notifyMessageDeleted($chatId, $messageId, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Message deleted successfully',
            'data' => $messageData
        ]);
    }
}
