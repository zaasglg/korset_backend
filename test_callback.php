<?php

require_once 'vendor/autoload.php';

use App\Services\FreedomPayService;
use App\Services\WalletService;
use App\Models\PaymentSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;

// Инициализация Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Тестирование FreedomPay Callback ===\n\n";

// Создаем тестового пользователя
$user = User::first();
if (!$user) {
    echo "❌ Пользователь не найден. Создайте пользователя в базе данных.\n";
    exit(1);
}

echo "👤 Тестовый пользователь: {$user->name} (ID: {$user->id})\n";
echo "💰 Текущий баланс: {$user->balance} KZT\n\n";

// Создаем тестовую платежную сессию
$paymentSession = PaymentSession::create([
    'user_id' => $user->id,
    'order_id' => 'TEST-' . time() . '-' . $user->id,
    'amount' => 1000.00,
    'currency' => 'KZT',
    'description' => 'Тестовое пополнение',
    'status' => 'pending',
    'payment_provider' => 'freedompay',
    'expires_at' => now()->addHours(1),
]);

echo "📝 Создана тестовая платежная сессия:\n";
echo "   - Order ID: {$paymentSession->order_id}\n";
echo "   - Сумма: {$paymentSession->amount} KZT\n";
echo "   - Статус: {$paymentSession->status}\n\n";

// Создаем сервисы
$walletService = new WalletService();
$freedomPayService = new FreedomPayService($walletService);

// Тест 1: Успешный callback
echo "🧪 Тест 1: Успешный callback\n";
echo "============================\n";

$successCallbackData = [
    'pg_order_id' => $paymentSession->order_id,
    'pg_payment_id' => '12345',
    'pg_result' => '1',
    'pg_description' => 'Успешная оплата',
    'pg_salt' => 'test_salt_' . time(),
];

// Генерируем подпись для тестового callback'а
$testSignature = generateTestSignature($successCallbackData);
$successCallbackData['pg_sig'] = $testSignature;

echo "📨 Данные callback'а:\n";
foreach ($successCallbackData as $key => $value) {
    echo "   - {$key}: {$value}\n";
}
echo "\n";

// Обрабатываем callback
$result = $freedomPayService->handleCallback($successCallbackData);

if ($result) {
    echo "✅ Callback обработан успешно\n";
    
    // Обновляем данные из базы
    $paymentSession->refresh();
    $user->refresh();
    
    echo "📊 Результат:\n";
    echo "   - Статус платежа: {$paymentSession->status}\n";
    echo "   - Время оплаты: {$paymentSession->paid_at}\n";
    echo "   - Новый баланс пользователя: {$user->balance} KZT\n";
    
    // Проверяем транзакцию
    $transaction = $paymentSession->getBalanceTopUpTransaction();
    if ($transaction) {
        echo "   - Транзакция пополнения: ID {$transaction->id}, сумма {$transaction->amount}\n";
    }
} else {
    echo "❌ Callback не обработан\n";
}

echo "\n";

// Тест 2: Повторный callback (должен быть проигнорирован)
echo "🧪 Тест 2: Повторный callback (защита от дублирования)\n";
echo "====================================================\n";

$balanceBefore = $user->balance;
$result2 = $freedomPayService->handleCallback($successCallbackData);

if ($result2) {
    echo "✅ Повторный callback обработан\n";
    $user->refresh();
    echo "📊 Результат:\n";
    echo "   - Баланс до: {$balanceBefore} KZT\n";
    echo "   - Баланс после: {$user->balance} KZT\n";
    
    if ($balanceBefore == $user->balance) {
        echo "✅ Защита от дублирования работает!\n";
    } else {
        echo "❌ Ошибка: баланс изменился при повторном callback'е\n";
    }
} else {
    echo "❌ Повторный callback не обработан\n";
}

echo "\n";

// Тест 3: Неуспешный callback
echo "🧪 Тест 3: Неуспешный callback\n";
echo "==============================\n";

$failedCallbackData = [
    'pg_order_id' => $paymentSession->order_id,
    'pg_payment_id' => '12346',
    'pg_result' => '0',
    'pg_description' => 'Неуспешная оплата',
    'pg_salt' => 'test_salt_failed_' . time(),
];

$failedSignature = generateTestSignature($failedCallbackData);
$failedCallbackData['pg_sig'] = $failedSignature;

echo "📨 Данные неуспешного callback'а:\n";
foreach ($failedCallbackData as $key => $value) {
    echo "   - {$key}: {$value}\n";
}
echo "\n";

$result3 = $freedomPayService->handleCallback($failedCallbackData);

if ($result3) {
    echo "✅ Неуспешный callback обработан\n";
    $paymentSession->refresh();
    echo "📊 Результат:\n";
    echo "   - Статус платежа: {$paymentSession->status}\n";
    echo "   - Баланс пользователя: {$user->balance} KZT (не изменился)\n";
} else {
    echo "❌ Неуспешный callback не обработан\n";
}

echo "\n=== Тестирование завершено ===\n";

/**
 * Генерирует тестовую подпись для callback'а
 */
function generateTestSignature(array $data): string
{
    // Убираем подпись если есть
    unset($data['pg_sig']);
    
    // Сортируем параметры
    ksort($data);
    
    // Формируем строку для подписи
    $signParts = ['result']; // Для callback используется 'result'
    
    foreach ($data as $value) {
        $signParts[] = (string) $value;
    }
    
    // Добавляем тестовый секретный ключ
    $signParts[] = 'test_secret_key';
    
    $signatureString = implode(';', $signParts);
    
    return md5($signatureString);
}
