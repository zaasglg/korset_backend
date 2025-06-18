<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryDemoSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * Создает демонстрационную структуру категорий, если их еще нет.
     */
    public function run(): void
    {
        // Проверяем, есть ли уже основные категории
        if (Category::whereNull('parent_id')->count() > 0) {
            $this->command->info('Основные категории уже существуют. Пропускаем создание демо-данных.');
            return;
        }

        $this->command->info('Создаем демонстрационную структуру категорий...');

        // Создаем основные категории (уровень 1)
        $mainCategories = [
            [
                'name' => 'Одежда и обувь',
                'description' => 'Мужская, женская и детская одежда, обувь и аксессуары',
                'subcategories' => [
                    'Мужская одежда' => 'Одежда для мужчин всех возрастов',
                    'Женская одежда' => 'Женская одежда и аксессуары',
                    'Детская одежда' => 'Одежда для детей и подростков',
                    'Обувь' => 'Обувь для всей семьи',
                ]
            ],
            [
                'name' => 'Электроника',
                'description' => 'Смартфоны, компьютеры, бытовая техника',
                'subcategories' => [
                    'Смартфоны' => 'Мобильные телефоны всех брендов',
                    'Ноутбуки' => 'Ноутбуки и компьютеры',
                    'Аксессуары' => 'Аксессуары для электроники',
                    'Бытовая техника' => 'Техника для дома',
                ]
            ],
            [
                'name' => 'Дом и сад',
                'description' => 'Товары для дома, дачи и сада',
                'subcategories' => [
                    'Мебель для дома' => 'Мебель для всех комнат',
                    'Садовая техника' => 'Инструменты и техника для сада',
                    'Декор' => 'Декоративные элементы для дома',
                    'Хозяйственные товары' => 'Товары для уборки и хозяйства',
                ]
            ],
            [
                'name' => 'Спорт и отдых',
                'description' => 'Спортивные товары и товары для активного отдыха',
                'subcategories' => [
                    'Фитнес оборудование' => 'Тренажеры и спортивный инвентарь',
                    'Командные виды спорта' => 'Товары для командных видов спорта',
                    'Туризм и кемпинг' => 'Снаряжение для туризма',
                    'Водные виды спорта' => 'Товары для водного спорта',
                ]
            ]
        ];

        foreach ($mainCategories as $mainCategoryData) {
            // Создаем основную категорию
            $mainCategory = Category::create([
                'name' => $mainCategoryData['name'],
                'description' => $mainCategoryData['description'],
                'parent_id' => null,
            ]);

            $this->command->info("Создана основная категория: {$mainCategory->name}");

            // Создаем подкатегории
            foreach ($mainCategoryData['subcategories'] as $subcategoryName => $subcategoryDescription) {
                $subcategory = Category::create([
                    'name' => $subcategoryName,
                    'description' => $subcategoryDescription,
                    'parent_id' => $mainCategory->id,
                ]);

                $this->command->info("  Создана подкатегория: {$subcategory->name}");
            }
        }

        $this->command->info('Демонстрационная структура категорий создана успешно!');
        $this->command->info('Теперь можно запустить CategoryThirdLevelSeeder для создания третьего уровня.');
    }
}
