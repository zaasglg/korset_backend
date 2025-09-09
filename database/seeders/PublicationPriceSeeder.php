<?php

namespace Database\Seeders;

use App\Models\PublicationPrice;
use Illuminate\Database\Seeder;

class PublicationPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Тарифы для сторис
        PublicationPrice::create([
            'type' => PublicationPrice::TYPE_STORY,
            'name' => 'Базовый сторис',
            'description' => 'Стандартная публикация сторис на 24 часа',
            'price' => 500.00,
            'duration_hours' => 24,
            'is_active' => true,
            'sort_order' => 1,
            'features' => [
                'Длительность' => '24 часа',
                'Охват' => 'Стандартный',
                'Поддержка' => 'Базовая'
            ]
        ]);

        PublicationPrice::create([
            'type' => PublicationPrice::TYPE_STORY,
            'name' => 'Премиум сторис',
            'description' => 'Расширенная публикация сторис на 48 часов с повышенным охватом',
            'price' => 800.00,
            'duration_hours' => 48,
            'is_active' => true,
            'sort_order' => 2,
            'features' => [
                'Длительность' => '48 часов',
                'Охват' => 'Повышенный',
                'Поддержка' => 'Приоритетная',
                'Аналитика' => 'Расширенная'
            ]
        ]);

        // Тарифы для объявлений
        PublicationPrice::create([
            'type' => PublicationPrice::TYPE_ANNOUNCEMENT,
            'name' => 'Стандартное объявление',
            'description' => 'Публикация объявления на 7 дней',
            'price' => 1000.00,
            'duration_hours' => 168, // 7 дней
            'is_active' => true,
            'sort_order' => 1,
            'features' => [
                'Длительность' => '7 дней',
                'Размещение' => 'Стандартное',
                'Изображения' => 'До 5 фото',
                'Редактирование' => 'Доступно'
            ]
        ]);

        PublicationPrice::create([
            'type' => PublicationPrice::TYPE_ANNOUNCEMENT,
            'name' => 'VIP объявление',
            'description' => 'Приоритетное размещение объявления на 14 дней',
            'price' => 1800.00,
            'duration_hours' => 336, // 14 дней
            'is_active' => true,
            'sort_order' => 2,
            'features' => [
                'Длительность' => '14 дней',
                'Размещение' => 'VIP (в топе)',
                'Изображения' => 'До 10 фото',
                'Редактирование' => 'Доступно',
                'Выделение' => 'Цветная рамка',
                'Статистика' => 'Подробная'
            ]
        ]);

        PublicationPrice::create([
            'type' => PublicationPrice::TYPE_ANNOUNCEMENT,
            'name' => 'Месячное объявление',
            'description' => 'Долгосрочное размещение объявления на 30 дней',
            'price' => 3000.00,
            'duration_hours' => 720, // 30 дней
            'is_active' => true,
            'sort_order' => 3,
            'features' => [
                'Длительность' => '30 дней',
                'Размещение' => 'Приоритетное',
                'Изображения' => 'Неограниченно',
                'Редактирование' => 'Доступно',
                'Продвижение' => 'Автоматическое',
                'Поддержка' => '24/7'
            ]
        ]);
    }
}
