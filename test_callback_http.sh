#!/bin/bash

# Тест FreedomPay Callback HTTP Endpoint
echo "=== Тест FreedomPay Callback HTTP Endpoint ==="
echo ""

# URL callback'а (замените на ваш домен)
CALLBACK_URL="http://localhost:8000/api/payments/freedompay/callback"

echo "🔗 URL: $CALLBACK_URL"
echo ""

# Проверяем, доступен ли сервер
echo "🔍 Проверка доступности сервера..."
if curl -s --connect-timeout 5 "$CALLBACK_URL" > /dev/null 2>&1; then
    echo "✅ Сервер доступен"
else
    echo "❌ Сервер недоступен. Убедитесь, что Laravel сервер запущен:"
    echo "   php artisan serve"
    echo ""
    echo "Или измените URL в скрипте на ваш домен."
    exit 1
fi

echo ""

# Тест 1: Успешный платеж
echo "🧪 Тест 1: Успешный платеж"
echo "=========================="

ORDER_ID="WALLET-123-$(date +%s)-abc123"
PAYMENT_ID="12345"
TIMESTAMP=$(date +%s)
SALT="test_salt_$TIMESTAMP"

# Формируем данные для подписи
SIGN_DATA="result;$ORDER_ID;$PAYMENT_ID;1;Успешная оплата;$SALT;test_secret_key"
SIGNATURE=$(echo -n "$SIGN_DATA" | md5sum | cut -d' ' -f1)

echo "📝 Order ID: $ORDER_ID"
echo "📝 Payment ID: $PAYMENT_ID"
echo "📝 Signature: $SIGNATURE"
echo ""

# Отправляем POST запрос
echo "📤 Отправка запроса..."
RESPONSE=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_order_id=$ORDER_ID" \
  -d "pg_payment_id=$PAYMENT_ID" \
  -d "pg_result=1" \
  -d "pg_description=Успешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=$SIGNATURE")

echo "📨 Ответ сервера: $RESPONSE"

if [ "$RESPONSE" = "OK" ]; then
    echo "✅ Callback обработан успешно"
else
    echo "❌ Неожиданный ответ от сервера"
fi

echo ""

# Тест 2: Неуспешный платеж
echo "🧪 Тест 2: Неуспешный платеж"
echo "============================"

ORDER_ID_2="WALLET-456-$(date +%s)-def456"
PAYMENT_ID_2="12346"

SIGN_DATA_2="result;$ORDER_ID_2;$PAYMENT_ID_2;0;Неуспешная оплата;$SALT;test_secret_key"
SIGNATURE_2=$(echo -n "$SIGN_DATA_2" | md5sum | cut -d' ' -f1)

echo "📝 Order ID: $ORDER_ID_2"
echo "📝 Payment ID: $PAYMENT_ID_2"
echo "📝 Signature: $SIGNATURE_2"
echo ""

echo "📤 Отправка запроса..."
RESPONSE_2=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_order_id=$ORDER_ID_2" \
  -d "pg_payment_id=$PAYMENT_ID_2" \
  -d "pg_result=0" \
  -d "pg_description=Неуспешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=$SIGNATURE_2")

echo "📨 Ответ сервера: $RESPONSE_2"

if [ "$RESPONSE_2" = "OK" ]; then
    echo "✅ Callback обработан успешно"
else
    echo "❌ Неожиданный ответ от сервера"
fi

echo ""

# Тест 3: Неверная подпись
echo "🧪 Тест 3: Неверная подпись"
echo "=========================="

echo "📝 Order ID: $ORDER_ID"
echo "📝 Неверная подпись: invalid_signature"
echo ""

echo "📤 Отправка запроса..."
RESPONSE_3=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_order_id=$ORDER_ID" \
  -d "pg_payment_id=$PAYMENT_ID" \
  -d "pg_result=1" \
  -d "pg_description=Успешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=invalid_signature")

echo "📨 Ответ сервера: $RESPONSE_3"

if [ "$RESPONSE_3" = "OK" ]; then
    echo "✅ Callback обработан (подпись отклонена, но HTTP 200 OK)"
else
    echo "❌ Неожиданный ответ от сервера"
fi

echo ""

# Тест 4: Отсутствующий order_id
echo "🧪 Тест 4: Отсутствующий order_id"
echo "==============================="

echo "📝 Отсутствует pg_order_id"
echo ""

echo "📤 Отправка запроса..."
RESPONSE_4=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_payment_id=$PAYMENT_ID" \
  -d "pg_result=1" \
  -d "pg_description=Успешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=test_signature")

echo "📨 Ответ сервера: $RESPONSE_4"

if [ "$RESPONSE_4" = "OK" ]; then
    echo "✅ Callback обработан (order_id отсутствует, но HTTP 200 OK)"
else
    echo "❌ Неожиданный ответ от сервера"
fi

echo ""

echo "=== Тестирование завершено ==="
echo ""
echo "💡 Проверьте логи Laravel для детальной информации:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "📋 Ожидаемые логи:"
echo "   - FreedomPay callback received"
echo "   - Payment session not found (для тестов 1-3)"
echo "   - Invalid FreedomPay callback signature (для теста 3)"
echo "   - Missing order_id in FreedomPay callback (для теста 4)"
