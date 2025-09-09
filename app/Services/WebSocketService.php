<?php

namespace App\Services;

use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;

class WebSocketService
{
    protected $connections = [];
    
    public function __construct()
    {
        // Инициализация WebSocket соединений
    }
    
    /**
     * Отправить сообщение в чат через WebSocket
     */
    public function sendMessageToChat($chatId, $message)
    {
        // Здесь можно реализовать отправку через WebSocket
        // Для простоты используем файловый кеш как временное хранилище
        $cacheKey = "chat_messages_{$chatId}";
        $messages = cache()->get($cacheKey, []);
        $messages[] = [
            'id' => $message->id,
            'content' => $message->content,
            'user' => $message->user,
            'created_at' => $message->created_at->toISOString(),
            'timestamp' => time()
        ];
        
        // Храним только последние 50 сообщений
        if (count($messages) > 50) {
            $messages = array_slice($messages, -50);
        }
        
        cache()->put($cacheKey, $messages, 3600); // 1 час
        
        return true;
    }
    
    /**
     * Получить новые сообщения из кеша
     */
    public function getNewMessages($chatId, $lastMessageId = 0)
    {
        $cacheKey = "chat_messages_{$chatId}";
        $messages = cache()->get($cacheKey, []);
        
        return array_filter($messages, function($msg) use ($lastMessageId) {
            return $msg['id'] > $lastMessageId;
        });
    }

    /**
     * Уведомить об удалении чата
     */
    public function notifyChatDeleted($chatId, $userId)
    {
        // Очищаем кеш сообщений для удаленного чата
        $cacheKey = "chat_messages_{$chatId}";
        cache()->forget($cacheKey);
        
        // Здесь можно добавить отправку уведомления через WebSocket
        // о том, что чат был удален
        
        return true;
    }

    /**
     * Уведомить об очистке чата
     */
    public function notifyChatCleared($chatId, $userId)
    {
        // Очищаем кеш сообщений для очищенного чата
        $cacheKey = "chat_messages_{$chatId}";
        cache()->forget($cacheKey);
        
        // Здесь можно добавить отправку уведомления через WebSocket
        // о том, что чат был очищен
        
        return true;
    }

    /**
     * Уведомить об удалении сообщения
     */
    public function notifyMessageDeleted($chatId, $messageId, $userId)
    {
        // Удаляем сообщение из кеша
        $cacheKey = "chat_messages_{$chatId}";
        $messages = cache()->get($cacheKey, []);
        
        // Фильтруем сообщения, удаляя удаленное
        $messages = array_filter($messages, fn($msg) => $msg['id'] != $messageId);
        
        // Обновляем кеш
        cache()->put($cacheKey, array_values($messages), 3600);
        
        // Здесь можно добавить отправку уведомления через WebSocket
        // о том, что сообщение было удалено
        
        return true;
    }
}