# Примеры использования Referral API

## 📋 Базовая информация

**Base URL:** `http://localhost:8000/api`

**Все защищенные endpoints требуют авторизации:**
```
Authorization: Bearer {your_token}
```

---

## 🚀 1. Регистрация с реферальным кодом

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Иван",
    "email": "ivan@example.com",
    "phone_number": "+77071234567",
    "password": "password123",
    "password_confirmation": "password123",
    "referral_code": "ABC12345"
  }'
```

**Ответ:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Иван",
    "email": "ivan@example.com"
  },
  "token": "1|xyz..."
}
```

---

## 🎫 2. Генерация реферального кода

```bash
curl -X POST http://localhost:8000/api/referrals/generate \
  -H "Authorization: Bearer YOUR_TOKEN"
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

---

## ✅ 3. Валидация реферального кода (без авторизации)

```bash
curl -X POST http://localhost:8000/api/referrals/validate \
  -H "Content-Type: application/json" \
  -d '{
    "referral_code": "ABC12345"
  }'
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

---

## 📊 4. Получение информации о рефералах

```bash
curl -X GET http://localhost:8000/api/referrals \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "my_referral_code": "XYZ98765",
    "total_referrals": 3,
    "total_earnings": 30.00,
    "pending_earnings": 20.00,
    "paid_earnings": 10.00,
    "referrals": [
      {
        "id": 1,
        "referred_user": {
          "name": "Мария",
          "surname": "Сидорова",
          "email": "maria@example.com"
        },
        "reward_amount": 10.00,
        "is_paid": true,
        "created_at": "2025-06-16T10:30:00.000000Z",
        "paid_at": "2025-07-16T10:30:00.000000Z"
      }
    ]
  }
}
```

---

## 💰 5. Применение реферального кода

```bash
curl -X POST http://localhost:8000/api/referrals/apply \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "referral_code": "ABC12345"
  }'
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

---

## 📈 6. Статистика рефералов

```bash
curl -X GET http://localhost:8000/api/referrals/statistics \
  -H "Authorization: Bearer YOUR_TOKEN"
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

---

## 🚫 Примеры ошибок

### Неверный формат кода:
```json
{
  "success": false,
  "message": "Invalid referral code format",
  "errors": {
    "referral_code": ["The referral code must be at least 6 characters."]
  }
}
```

### Код уже использован:
```json
{
  "success": false,
  "message": "You have already used a referral code"
}
```

### Попытка использовать свой код:
```json
{
  "success": false,
  "message": "You cannot use your own referral code"
}
```

### Код не найден:
```json
{
  "success": false,
  "message": "Invalid or already used referral code"
}
```

---

## 📱 Пример для мобильного приложения (JavaScript/React Native)

```javascript
// Класс для работы с Referral API
class ReferralAPI {
  constructor(baseURL, token) {
    this.baseURL = baseURL;
    this.token = token;
  }

  // Получить мой реферальный код и статистику
  async getMyReferrals() {
    const response = await fetch(`${this.baseURL}/referrals`, {
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    return await response.json();
  }

  // Сгенерировать новый реферальный код
  async generateCode() {
    const response = await fetch(`${this.baseURL}/referrals/generate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    return await response.json();
  }

  // Применить реферальный код
  async applyCode(referralCode) {
    const response = await fetch(`${this.baseURL}/referrals/apply`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ referral_code: referralCode })
    });
    return await response.json();
  }

  // Валидация кода (без токена)
  async validateCode(referralCode) {
    const response = await fetch(`${this.baseURL}/referrals/validate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ referral_code: referralCode })
    });
    return await response.json();
  }
}

// Использование:
const api = new ReferralAPI('http://localhost:8000/api', 'your_token_here');

// Получить мои рефералы
api.getMyReferrals().then(data => {
  console.log('Мой код:', data.data.my_referral_code);
  console.log('Заработок:', data.data.total_earnings);
});
```

---

## 🔄 Типичный flow использования

1. **Пользователь A регистрируется** → получает токен
2. **Генерирует реферальный код** → получает уникальный код
3. **Делится кодом с друзьями** → код ABC12345
4. **Пользователь B вводит код при регистрации** → автоматически применяется
5. **Пользователь A получает награду** → 10.00 добавляется к заработку
6. **Отслеживание статистики** → через `/referrals` и `/referrals/statistics`
