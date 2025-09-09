# Realtime Chat API - Бесплатные решения

## Обзор

Предоставляем несколько бесплатных способов реализации realtime чата без использования платных сервисов типа Pusher.

## Варианты реализации

### 1. 🚀 Polling (Самый простой)
Периодические запросы к серверу для получения новых сообщений.

**Преимущества:**
- Простота реализации
- Работает везде
- Не требует дополнительных сервисов

**Недостатки:**
- Задержка в доставке сообщений
- Больше нагрузки на сервер

### 2. 📡 Server-Sent Events (SSE)
Односторонняя связь от сервера к клиенту.

**Преимущества:**
- Мгновенная доставка
- Встроенная поддержка в браузерах
- Автоматическое переподключение

**Недостатки:**
- Только от сервера к клиенту
- Ограничения на количество соединений

### 3. 🔌 WebSocket (Самый мощный)
Двусторонняя связь в реальном времени.

**Преимущества:**
- Полноценный realtime
- Двусторонняя связь
- Минимальная задержка

**Недостатки:**
- Сложнее в настройке
- Требует отдельный сервер

## API Endpoints

### Основные методы чата

#### Отправить сообщение
```http
POST /api/chats/{chatId}/messages
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Текст сообщения"
}
```

#### Получить сообщения чата
```http
GET /api/chats/{chatId}/messages
Authorization: Bearer {token}
```

### Realtime методы

#### 1. Polling - Получить новые сообщения
```http
GET /api/chats/{chatId}/poll?last_message_id=123
Authorization: Bearer {token}
```

**Ответ:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 124,
            "content": "Новое сообщение",
            "user": {
                "id": 1,
                "name": "Пользователь",
                "avatar": "avatar.jpg"
            },
            "created_at": "2025-08-09T10:00:00.000000Z"
        }
    ],
    "has_new_messages": true,
    "last_message_id": 124,
    "source": "cache"
}
```

#### 2. Server-Sent Events - Поток сообщений
```http
GET /api/chats/{chatId}/stream?last_message_id=123
Authorization: Bearer {token}
Accept: text/event-stream
```

**Ответ (поток):**
```
data: {"type":"message","data":{"id":124,"content":"Привет","user":{"id":1,"name":"Пользователь"}}}

data: {"type":"message","data":{"id":125,"content":"Как дела?","user":{"id":2,"name":"Другой"}}}
```

## Реализация во Flutter

### 1. Polling подход

```dart
class PollingChatService {
  Timer? _pollTimer;
  int _lastMessageId = 0;
  
  void startPolling(int chatId, Function(List<Message>) onNewMessages) {
    _pollTimer = Timer.periodic(Duration(seconds: 2), (timer) async {
      try {
        final response = await http.get(
          Uri.parse('$baseUrl/chats/$chatId/poll?last_message_id=$_lastMessageId'),
          headers: {'Authorization': 'Bearer $token'},
        );
        
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          if (data['has_new_messages']) {
            final messages = (data['data'] as List)
                .map((m) => Message.fromJson(m))
                .toList();
            
            _lastMessageId = data['last_message_id'];
            onNewMessages(messages);
          }
        }
      } catch (e) {
        print('Polling error: $e');
      }
    });
  }
  
  void stopPolling() {
    _pollTimer?.cancel();
  }
}
```

### 2. Server-Sent Events подход

```dart
class SSEChatService {
  EventSource? _eventSource;
  
  void connectToChat(int chatId, Function(Message) onNewMessage) {
    _eventSource = EventSource(
      Uri.parse('$baseUrl/chats/$chatId/stream'),
      headers: {'Authorization': 'Bearer $token'},
    );
    
    _eventSource!.listen((event) {
      try {
        final data = json.decode(event.data!);
        if (data['type'] == 'message') {
          final message = Message.fromJson(data['data']);
          onNewMessage(message);
        }
      } catch (e) {
        print('SSE error: $e');
      }
    });
  }
  
  void disconnect() {
    _eventSource?.close();
  }
}
```

### 3. WebSocket подход (с web_socket_channel)

```dart
dependencies:
  web_socket_channel: ^2.4.0
```

```dart
class WebSocketChatService {
  WebSocketChannel? _channel;
  
  void connectToChat(int chatId, Function(Message) onNewMessage) {
    _channel = WebSocketChannel.connect(
      Uri.parse('ws://yourserver.com:8080/chat/$chatId'),
    );
    
    _channel!.stream.listen((data) {
      try {
        final message = Message.fromJson(json.decode(data));
        onNewMessage(message);
      } catch (e) {
        print('WebSocket error: $e');
      }
    });
  }
  
  void sendMessage(String content) {
    if (_channel != null) {
      _channel!.sink.add(json.encode({
        'type': 'message',
        'content': content,
      }));
    }
  }
  
  void disconnect() {
    _channel?.sink.close();
  }
}
```

## Пример использования в Flutter виджете

```dart
class ChatScreen extends StatefulWidget {
  final int chatId;
  
  const ChatScreen({Key? key, required this.chatId}) : super(key: key);
  
  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final List<Message> _messages = [];
  final TextEditingController _controller = TextEditingController();
  late PollingChatService _chatService;
  
  @override
  void initState() {
    super.initState();
    _chatService = PollingChatService();
    _loadInitialMessages();
    _startRealtime();
  }
  
  void _loadInitialMessages() async {
    // Загрузить существующие сообщения
    final response = await http.get(
      Uri.parse('$baseUrl/chats/${widget.chatId}/messages'),
      headers: {'Authorization': 'Bearer $token'},
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      setState(() {
        _messages.addAll((data['data'] as List)
            .map((m) => Message.fromJson(m))
            .toList());
      });
    }
  }
  
  void _startRealtime() {
    _chatService.startPolling(widget.chatId, (newMessages) {
      setState(() {
        _messages.addAll(newMessages);
      });
    });
  }
  
  void _sendMessage() async {
    if (_controller.text.trim().isEmpty) return;
    
    final response = await http.post(
      Uri.parse('$baseUrl/chats/${widget.chatId}/messages'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: json.encode({'content': _controller.text}),
    );
    
    if (response.statusCode == 200) {
      _controller.clear();
      // Сообщение добавится через polling
    }
  }
  
  @override
  void dispose() {
    _chatService.stopPolling();
    super.dispose();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Чат')),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                return ListTile(
                  title: Text(message.content),
                  subtitle: Text(message.user.name),
                  trailing: Text(
                    DateFormat('HH:mm').format(message.createdAt),
                  ),
                );
              },
            ),
          ),
          Padding(
            padding: EdgeInsets.all(8.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: InputDecoration(
                      hintText: 'Введите сообщение...',
                    ),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.send),
                  onPressed: _sendMessage,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
```

## Рекомендации

### Для начала проекта:
**Используйте Polling** - просто и надежно

### Для продакшена с небольшой нагрузкой:
**Используйте Server-Sent Events** - хороший баланс

### Для высоконагруженных приложений:
**Используйте WebSocket** - максимальная производительность

## Настройка

1. Все endpoints уже готовы к использованию
2. Кеширование работает автоматически
3. Для WebSocket нужно будет настроить отдельный сервер

Выберите подходящий вариант в зависимости от ваших потребностей!