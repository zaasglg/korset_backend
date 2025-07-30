# Реферальная система - API документация

## Обзор

Реферальная система позволяет пользователям приглашать друзей через уникальные промокоды и получать вознаграждения.

## Настройка

Настройки системы находятся в файле `config/referral.php`:

```php
'reward_amount' => 10.00,           // Размер награды за приглашение
'minimum_withdrawal' => 50.00,      // Минимальная сумма для вывода
'code_length' => 8,                 // Длина реферального кода
'reward_delay_days' => 30,          // Задержка выплаты в днях
```

## API Endpoints

### 1. Получение информации о рефералах пользователя

```http
GET /api/referrals
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "my_referral_code": "ABC12345",
    "total_referrals": 5,
    "total_earnings": 50.00,
    "pending_earnings": 30.00,
    "paid_earnings": 20.00,
    "referrals": [
      {
        "id": 1,
        "referred_user": {
          "name": "Иван",
          "surname": "Петров",
          "email": "ivan@example.com"
        },
        "reward_amount": 10.00,
        "is_paid": true,
        "created_at": "2025-06-16T14:30:00.000000Z",
        "paid_at": "2025-07-16T14:30:00.000000Z"
      }
    ]
  }
}
```

### 2. Генерация реферального кода

```http
POST /api/referrals/generate
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "referral_code": "XYZ98765",
    "message": "New referral code generated successfully"
  }
}
```

### 3. Применение реферального кода

```http
POST /api/referrals/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "referral_code": "ABC12345"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Referral code applied successfully! Your referrer will receive a reward.",
  "data": {
    "reward_amount": 10.00,
    "referrer_name": "Иван Петров"
  }
}
```

### 4. Валидация реферального кода (публичный)

```http
POST /api/referrals/validate
Content-Type: application/json

{
  "referral_code": "ABC12345"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Valid referral code",
  "data": {
    "referrer_name": "Иван Петров",
    "reward_amount": 10.00
  }
}
```

### 5. Статистика рефералов

```http
GET /api/referrals/statistics
Authorization: Bearer {token}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "total_referrals": 5,
    "total_earnings": 50.00,
    "pending_earnings": 30.00,
    "paid_earnings": 20.00,
    "this_month_referrals": 2,
    "this_month_earnings": 20.00,
    "this_week_referrals": 1,
    "this_week_earnings": 10.00,
    "recent_referrals": [
      {
        "referred_user_name": "Мария Сидорова",
        "reward_amount": 10.00,
        "is_paid": false,
        "created_at": "2025-06-16 14:30:00"
      }
    ]
  }
}
```

### 6. Регистрация с реферальным кодом

```http
POST /api/register
Content-Type: application/json

{
  "name": "Иван",
  "email": "ivan@example.com",
  "phone_number": "+77071234567",
  "password": "password123",
  "password_confirmation": "password123",
  "referral_code": "ABC12345"
}
```

## Ошибки

### Коды ошибок:

- `400` - Некорректный запрос (попытка использовать свой код, уже использованный код)
- `404` - Реферальный код не найден
- `422` - Ошибки валидации

### Примеры ошибок:

```json
{
  "success": false,
  "message": "You cannot use your own referral code"
}
```

```json
{
  "success": false,
  "message": "You have already used a referral code"
}
```

```json
{
  "success": false,
  "message": "Invalid or already used referral code"
}
```

## Консольные команды

### Обработка наград
```bash
php artisan referrals:process-rewards
```

Автоматически выплачивает награды за рефералов, которые прошли период задержки.

## Админ-панель

В Filament админ-панели доступен раздел "Referrals" для управления реферальной системой:

- Просмотр всех рефералов
- Фильтрация по статусу оплаты
- Ручная выплата наград
- Создание новых реферальных кодов

## Использование в мобильном приложении

1. **Получение реферального кода:** Пользователь может получить свой уникальный код через endpoint `/api/referrals/generate`

2. **Приглашение друзей:** Поделиться кодом с друзьями

3. **Регистрация по коду:** При регистрации новый пользователь вводит реферальный код

4. **Отслеживание:** Пользователь может отслеживать свои рефералы и заработок через endpoint `/api/referrals`

## Безопасность

- Реферальные коды генерируются случайным образом
- Нельзя использовать собственный реферальный код
- Один пользователь может использовать только один реферальный код
- Выплаты происходят с задержкой для предотвращения мошенничества
