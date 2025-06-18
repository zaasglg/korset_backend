<?php

// Пример использования реферальной системы через tinker
// Запустите: php artisan tinker

use App\Models\User;
use App\Models\Referral;

// 1. Создаем тестовых пользователей
$user1 = User::factory()->create([
    'name' => 'Иван',
    'surname' => 'Петров',
    'email' => 'ivan@example.com',
    'phone_number' => '+77071234567'
]);

$user2 = User::factory()->create([
    'name' => 'Мария', 
    'surname' => 'Сидорова',
    'email' => 'maria@example.com',
    'phone_number' => '+77071234568'
]);

// 2. Создаем реферальный код для первого пользователя
$referralCode = Referral::generateUniqueCode();
$referral = Referral::create([
    'referrer_id' => $user1->id,
    'referral_code' => $referralCode,
]);

echo "Создан реферальный код: " . $referralCode . "\n";

// 3. Второй пользователь использует реферальный код
$referral->update([
    'referred_id' => $user2->id,
    'reward_amount' => config('referral.reward_amount', 10.00)
]);

echo "Пользователь {$user2->name} использовал код {$referralCode}\n";
echo "Награда за реферал: " . $referral->reward_amount . "\n";

// 4. Проверяем статистику первого пользователя
$stats = [
    'total_referrals' => $user1->referralsMade()->whereNotNull('referred_id')->count(),
    'total_earnings' => $user1->referralsMade()->sum('reward_amount'),
    'pending_earnings' => $user1->referralsMade()->where('is_paid', false)->sum('reward_amount'),
];

echo "Статистика пользователя {$user1->name}:\n";
echo "- Всего рефералов: {$stats['total_referrals']}\n";
echo "- Общий заработок: {$stats['total_earnings']}\n";
echo "- Ожидает выплаты: {$stats['pending_earnings']}\n";

// 5. Отмечаем награду как выплаченную
$referral->markAsPaid();
echo "Награда выплачена пользователю {$user1->name}\n";
