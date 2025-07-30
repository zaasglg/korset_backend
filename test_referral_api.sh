#!/bin/bash

# Тестирование реферальной системы через API
# Убедитесь, что сервер запущен: php artisan serve

BASE_URL="http://localhost:8000/api"

echo "=== Тестирование реферальной системы ==="

# 1. Регистрация первого пользователя (будущий реферер)
echo "1. Регистрация первого пользователя..."
RESPONSE1=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Иван",
    "email": "ivan@example.com", 
    "phone_number": "+77071234567",
    "password": "password123",
    "password_confirmation": "password123"
  }')

TOKEN1=$(echo $RESPONSE1 | jq -r '.token')
echo "Токен первого пользователя: ${TOKEN1:0:50}..."

# 2. Генерация реферального кода
echo -e "\n2. Генерация реферального кода..."
REFERRAL_RESPONSE=$(curl -s -X POST "$BASE_URL/referrals/generate" \
  -H "Authorization: Bearer $TOKEN1" \
  -H "Content-Type: application/json")

REFERRAL_CODE=$(echo $REFERRAL_RESPONSE | jq -r '.data.referral_code')
echo "Сгенерированный код: $REFERRAL_CODE"

# 3. Валидация реферального кода (публичный endpoint)
echo -e "\n3. Валидация реферального кода..."
VALIDATION_RESPONSE=$(curl -s -X POST "$BASE_URL/referrals/validate" \
  -H "Content-Type: application/json" \
  -d "{\"referral_code\": \"$REFERRAL_CODE\"}")

echo "Результат валидации: $(echo $VALIDATION_RESPONSE | jq -r '.message')"

# 4. Регистрация второго пользователя с реферальным кодом
echo -e "\n4. Регистрация второго пользователя с реферальным кодом..."
RESPONSE2=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Мария\",
    \"email\": \"maria@example.com\",
    \"phone_number\": \"+77071234568\", 
    \"password\": \"password123\",
    \"password_confirmation\": \"password123\",
    \"referral_code\": \"$REFERRAL_CODE\"
  }")

TOKEN2=$(echo $RESPONSE2 | jq -r '.token')
echo "Второй пользователь зарегистрирован успешно"

# 5. Проверка информации о рефералах первого пользователя
echo -e "\n5. Информация о рефералах первого пользователя..."
REFERRALS_INFO=$(curl -s -X GET "$BASE_URL/referrals" \
  -H "Authorization: Bearer $TOKEN1")

echo "Общий заработок: $(echo $REFERRALS_INFO | jq -r '.data.total_earnings')"
echo "Количество рефералов: $(echo $REFERRALS_INFO | jq -r '.data.total_referrals')"
echo "Ожидает выплаты: $(echo $REFERRALS_INFO | jq -r '.data.pending_earnings')"

# 6. Статистика рефералов
echo -e "\n6. Детальная статистика..."
STATS_RESPONSE=$(curl -s -X GET "$BASE_URL/referrals/statistics" \
  -H "Authorization: Bearer $TOKEN1")

echo "Статистика: $(echo $STATS_RESPONSE | jq '.data')"

echo -e "\n=== Тестирование завершено ==="
