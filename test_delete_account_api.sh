#!/bin/bash

# Тестирование API удаления аккаунта

BASE_URL="http://localhost:8000/api"

echo "=== Тестирование API удаления аккаунта ==="
echo

# 1. Регистрация нового пользователя
echo "1. Регистрация нового пользователя..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com", 
    "phone_number": "+77771234567",
    "password": "password123",
    "password_confirmation": "password123"
  }')

echo "Response: $REGISTER_RESPONSE"
echo

# Извлекаем токен из ответа (простой способ)
TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "Ошибка: Не удалось получить токен"
    exit 1
fi

echo "Токен получен: ${TOKEN:0:20}..."
echo

# 2. Попытка удаления с неверным паролем
echo "2. Попытка удаления с неверным паролем..."
DELETE_WRONG_PASSWORD=$(curl -s -X DELETE "$BASE_URL/delete-account" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "password": "wrongpassword"
  }')

echo "Response: $DELETE_WRONG_PASSWORD"
echo

# 3. Попытка удаления без пароля
echo "3. Попытка удаления без пароля..."
DELETE_NO_PASSWORD=$(curl -s -X DELETE "$BASE_URL/delete-account" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{}')

echo "Response: $DELETE_NO_PASSWORD"
echo

# 4. Успешное удаление аккаунта
echo "4. Успешное удаление аккаунта..."
DELETE_SUCCESS=$(curl -s -X DELETE "$BASE_URL/delete-account" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "password": "password123"
  }')

echo "Response: $DELETE_SUCCESS"
echo

# 5. Попытка использования токена после удаления
echo "5. Попытка использования токена после удаления..."
USER_INFO=$(curl -s -X GET "$BASE_URL/user" \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $USER_INFO"
echo

echo "=== Тестирование завершено ==="
