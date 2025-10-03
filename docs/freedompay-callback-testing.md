# Тестирование FreedomPay Callback

## Обзор логики callback'а

Callback от FreedomPay обрабатывается в двух местах:

1. **HTTP Endpoint**: `/api/payments/freedompay/callback` (PaymentController::freedomPayCallback)
2. **Service Method**: `FreedomPayService::handleCallback()`

## Логика обработки

### 1. Проверка подписи
```php
if (!$this->verifySignature($data)) {
    Log::warning('Invalid FreedomPay callback signature', $data);
    return false;
}
```

### 2. Поиск платежной сессии
```php
$paymentSession = PaymentSession::where('order_id', $orderId)->first();
if (!$paymentSession) {
    Log::warning('Payment session not found', ['order_id' => $orderId]);
    return false;
}
```

### 3. Обработка успешного платежа
```php
if ($status === '1' || $status === 1 || $status === 'ok') {
    $paymentSession->markAsPaid();
    
    // Проверка на дублирование
    if (!$paymentSession->hasBalanceToppedUp()) {
        $this->walletService->deposit(
            $paymentSession->user,
            $paymentSession->amount,
            'Пополнение через FreedomPay',
            $paymentSession->order_id
        );
    }
}
```

## Тестовые сценарии

### Сценарий 1: Успешный платеж
**Входные данные:**
```json
{
    "pg_order_id": "WALLET-123-1234567890-abc123",
    "pg_payment_id": "12345",
    "pg_result": "1",
    "pg_description": "Успешная оплата",
    "pg_salt": "random_salt_123",
    "pg_sig": "generated_signature"
}
```

**Ожидаемый результат:**
- Статус платежной сессии: `paid`
- Баланс пользователя увеличен на сумму платежа
- Создана транзакция в кошельке
- Лог: "Payment completed and balance topped up successfully"

### Сценарий 2: Повторный callback
**Входные данные:** Те же, что и в сценарии 1

**Ожидаемый результат:**
- Баланс НЕ изменяется (защита от дублирования)
- Лог: "Payment completed but balance already topped up"

### Сценарий 3: Неуспешный платеж
**Входные данные:**
```json
{
    "pg_order_id": "WALLET-123-1234567890-abc123",
    "pg_payment_id": "12346",
    "pg_result": "0",
    "pg_description": "Неуспешная оплата",
    "pg_salt": "random_salt_456",
    "pg_sig": "generated_signature"
}
```

**Ожидаемый результат:**
- Статус платежной сессии: `failed`
- Баланс пользователя НЕ изменяется
- Лог: "Payment failed"

### Сценарий 4: Неверная подпись
**Входные данные:** Любые данные с неверной подписью

**Ожидаемый результат:**
- Callback отклонен
- Лог: "Invalid FreedomPay callback signature"
- HTTP ответ: 200 OK (FreedomPay требует этого)

## Генерация подписи для тестирования

Для callback'а используется скрипт `result`:

```php
// Параметры для подписи (без pg_sig)
$params = [
    'pg_order_id' => 'WALLET-123-1234567890-abc123',
    'pg_payment_id' => '12345',
    'pg_result' => '1',
    'pg_description' => 'Успешная оплата',
    'pg_salt' => 'random_salt_123',
];

// Сортировка по ключам
ksort($params);

// Формирование строки для подписи
$signParts = ['result']; // Для callback используется 'result'
foreach ($params as $value) {
    $signParts[] = (string) $value;
}
$signParts[] = $secretKey; // Секретный ключ в конце

$signatureString = implode(';', $signParts);
$signature = md5($signatureString);
```

## Мониторинг и логирование

Все операции логируются в `storage/logs/laravel.log`:

- `FreedomPay callback received` - получен callback
- `Payment completed and balance topped up successfully` - успешное пополнение
- `Payment completed but balance already topped up` - дублирование предотвращено
- `Payment failed` - неуспешный платеж
- `Invalid FreedomPay callback signature` - неверная подпись
- `Payment session not found` - сессия не найдена

## Проверка работы

1. **Создайте платежную сессию** через API
2. **Отправьте callback** с правильной подписью
3. **Проверьте логи** на наличие сообщений
4. **Проверьте базу данных**:
   - Статус платежной сессии
   - Баланс пользователя
   - Транзакции кошелька

## Безопасность

- ✅ Проверка подписи MD5
- ✅ Защита от дублирования пополнения
- ✅ Валидация всех обязательных полей
- ✅ Логирование всех операций
- ✅ Атомарные операции с базой данных
