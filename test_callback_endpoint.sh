#!/bin/bash

# Тест FreedomPay Callback Endpoint
echo "=== Тестирование FreedomPay Callback Endpoint ==="
echo ""

# URL callback'а (замените на ваш домен)
CALLBACK_URL="http://localhost:8000/api/payments/freedompay/callback"

echo "🔗 URL: $CALLBACK_URL"
echo ""

# Тест 1: Успешный платеж
echo "🧪 Тест 1: Успешный платеж"
echo "=========================="

ORDER_ID="TEST-$(date +%s)-123"
PAYMENT_ID="12345"

# Генерируем тестовые данные
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
RESPONSE=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_order_id=$ORDER_ID" \
  -d "pg_payment_id=$PAYMENT_ID" \
  -d "pg_result=1" \
  -d "pg_description=Успешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=$SIGNATURE")

echo "📨 Ответ сервера: $RESPONSE"
echo ""

# Тест 2: Неуспешный платеж
echo "🧪 Тест 2: Неуспешный платеж"
echo "============================"

ORDER_ID_2="TEST-$(date +%s)-456"
PAYMENT_ID_2="12346"

SIGN_DATA_2="result;$ORDER_ID_2;$PAYMENT_ID_2;0;Неуспешная оплата;$SALT;test_secret_key"
SIGNATURE_2=$(echo -n "$SIGN_DATA_2" | md5sum | cut -d' ' -f1)

echo "📝 Order ID: $ORDER_ID_2"
echo "📝 Payment ID: $PAYMENT_ID_2"
echo "📝 Signature: $SIGNATURE_2"
echo ""

RESPONSE_2=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_order_id=$ORDER_ID_2" \
  -d "pg_payment_id=$PAYMENT_ID_2" \
  -d "pg_result=0" \
  -d "pg_description=Неуспешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=$SIGNATURE_2")

echo "📨 Ответ сервера: $RESPONSE_2"
echo ""

# Тест 3: Неверная подпись
echo "🧪 Тест 3: Неверная подпись"
echo "=========================="

echo "📝 Order ID: $ORDER_ID"
echo "📝 Неверная подпись: invalid_signature"
echo ""

RESPONSE_3=$(curl -s -X POST "$CALLBACK_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "pg_order_id=$ORDER_ID" \
  -d "pg_payment_id=$PAYMENT_ID" \
  -d "pg_result=1" \
  -d "pg_description=Успешная оплата" \
  -d "pg_salt=$SALT" \
  -d "pg_sig=invalid_signature")

echo "📨 Ответ сервера: $RESPONSE_3"
echo ""

echo "=== Тестирование завершено ==="
echo ""
echo "💡 Проверьте логи Laravel для детальной информации:"
echo "   tail -f storage/logs/laravel.log"
