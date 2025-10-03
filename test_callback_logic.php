<?php

/**
 * Простой тест логики FreedomPay Callback
 * Этот скрипт проверяет логику без запуска Laravel
 */

echo "=== Тест логики FreedomPay Callback ===\n\n";

// Симуляция данных callback'а
$testCases = [
    [
        'name' => 'Успешный платеж',
        'data' => [
            'pg_order_id' => 'WALLET-123-1234567890-abc123',
            'pg_payment_id' => '12345',
            'pg_result' => '1',
            'pg_description' => 'Успешная оплата',
            'pg_salt' => 'test_salt_123',
            'pg_sig' => 'test_signature'
        ],
        'expected_status' => 'paid',
        'expected_balance_change' => true
    ],
    [
        'name' => 'Неуспешный платеж',
        'data' => [
            'pg_order_id' => 'WALLET-123-1234567890-abc123',
            'pg_payment_id' => '12346',
            'pg_result' => '0',
            'pg_description' => 'Неуспешная оплата',
            'pg_salt' => 'test_salt_456',
            'pg_sig' => 'test_signature'
        ],
        'expected_status' => 'failed',
        'expected_balance_change' => false
    ],
    [
        'name' => 'Платеж с результатом "ok"',
        'data' => [
            'pg_order_id' => 'WALLET-123-1234567890-abc123',
            'pg_payment_id' => '12347',
            'pg_result' => 'ok',
            'pg_description' => 'Успешная оплата (ok)',
            'pg_salt' => 'test_salt_789',
            'pg_sig' => 'test_signature'
        ],
        'expected_status' => 'paid',
        'expected_balance_change' => true
    ],
    [
        'name' => 'Платеж с числовым результатом 1',
        'data' => [
            'pg_order_id' => 'WALLET-123-1234567890-abc123',
            'pg_payment_id' => '12348',
            'pg_result' => 1,
            'pg_description' => 'Успешная оплата (число)',
            'pg_salt' => 'test_salt_101',
            'pg_sig' => 'test_signature'
        ],
        'expected_status' => 'paid',
        'expected_balance_change' => true
    ]
];

// Функция для проверки статуса платежа
function checkPaymentStatus($data) {
    $status = $data['pg_result'] ?? null;
    
    if ($status === '1' || $status === 1 || $status === 'ok') {
        return 'paid';
    } else {
        return 'failed';
    }
}

// Функция для проверки изменения баланса
function shouldChangeBalance($data) {
    $status = $data['pg_result'] ?? null;
    
    return ($status === '1' || $status === 1 || $status === 'ok');
}

// Функция для генерации тестовой подписи
function generateTestSignature($data, $secretKey = 'test_secret_key') {
    // Убираем подпись если есть
    unset($data['pg_sig']);
    
    // Сортируем параметры
    ksort($data);
    
    // Формируем строку для подписи
    $signParts = ['result']; // Для callback используется 'result'
    
    foreach ($data as $value) {
        $signParts[] = (string) $value;
    }
    
    // Добавляем секретный ключ
    $signParts[] = $secretKey;
    
    $signatureString = implode(';', $signParts);
    
    return md5($signatureString);
}

// Функция для проверки подписи
function verifyTestSignature($data, $secretKey = 'test_secret_key') {
    $signature = $data['pg_sig'] ?? '';
    unset($data['pg_sig']);
    
    $expectedSignature = generateTestSignature($data, $secretKey);
    
    return hash_equals($expectedSignature, $signature);
}

// Запуск тестов
foreach ($testCases as $index => $testCase) {
    echo "🧪 Тест " . ($index + 1) . ": {$testCase['name']}\n";
    echo str_repeat("=", strlen($testCase['name']) + 10) . "\n";
    
    $data = $testCase['data'];
    
    // Генерируем правильную подпись для тестирования
    $correctSignature = generateTestSignature($data);
    $data['pg_sig'] = $correctSignature;
    
    echo "📝 Входные данные:\n";
    foreach ($data as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    echo "\n";
    
    // Проверяем статус платежа
    $actualStatus = checkPaymentStatus($data);
    $statusCorrect = ($actualStatus === $testCase['expected_status']);
    
    echo "📊 Результат проверки статуса:\n";
    echo "   - Ожидаемый статус: {$testCase['expected_status']}\n";
    echo "   - Фактический статус: {$actualStatus}\n";
    echo "   - " . ($statusCorrect ? "✅ Корректно" : "❌ Ошибка") . "\n\n";
    
    // Проверяем изменение баланса
    $shouldChange = shouldChangeBalance($data);
    $balanceCorrect = ($shouldChange === $testCase['expected_balance_change']);
    
    echo "💰 Результат проверки баланса:\n";
    echo "   - Ожидается изменение: " . ($testCase['expected_balance_change'] ? "Да" : "Нет") . "\n";
    echo "   - Фактически изменяется: " . ($shouldChange ? "Да" : "Нет") . "\n";
    echo "   - " . ($balanceCorrect ? "✅ Корректно" : "❌ Ошибка") . "\n\n";
    
    // Проверяем подпись
    $signatureValid = verifyTestSignature($data);
    echo "🔐 Проверка подписи:\n";
    echo "   - Подпись валидна: " . ($signatureValid ? "✅ Да" : "❌ Нет") . "\n\n";
    
    // Общий результат теста
    $testPassed = $statusCorrect && $balanceCorrect && $signatureValid;
    echo "🎯 Общий результат: " . ($testPassed ? "✅ ПРОЙДЕН" : "❌ ПРОВАЛЕН") . "\n";
    
    echo str_repeat("-", 50) . "\n\n";
}

// Тест генерации подписи
echo "🔧 Тест генерации подписи\n";
echo "========================\n";

$testData = [
    'pg_order_id' => 'WALLET-123-1234567890-abc123',
    'pg_payment_id' => '12345',
    'pg_result' => '1',
    'pg_description' => 'Успешная оплата',
    'pg_salt' => 'test_salt_123',
];

$signature = generateTestSignature($testData);
echo "📝 Тестовые данные:\n";
foreach ($testData as $key => $value) {
    echo "   - {$key}: {$value}\n";
}
echo "\n";

echo "🔐 Сгенерированная подпись: {$signature}\n\n";

// Проверяем валидацию подписи
$testData['pg_sig'] = $signature;
$isValid = verifyTestSignature($testData);
echo "✅ Проверка подписи: " . ($isValid ? "Валидна" : "Невалидна") . "\n\n";

echo "=== Тестирование завершено ===\n";
