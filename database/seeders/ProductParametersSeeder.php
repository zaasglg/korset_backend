<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\ProductParameter;

class ProductParametersSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Получаем категории 3-го уровня
        $thirdLevelCategories = Category::whereNotNull('parent_id')
            ->whereHas('parent', function($q) { 
                $q->whereNotNull('parent_id'); 
            })
            ->get();

        // Получаем категории 2-го уровня, которые не имеют дочерних категорий
        $secondLevelCategoriesWithoutChildren = Category::whereNotNull('parent_id')
            ->whereHas('parent', function($q) { 
                $q->whereNull('parent_id'); 
            })
            ->whereDoesntHave('children')
            ->get();

        // Объединяем категории для создания параметров
        $categoriesForParameters = $thirdLevelCategories->concat($secondLevelCategoriesWithoutChildren);

        // Параметры для разных типов категорий
        $parameterTemplates = [
            // Электроника
            'electronics' => [
                ['name' => 'Бренд', 'type' => 'select', 'options' => [
                    ['label' => 'Apple', 'value' => 'apple'],
                    ['label' => 'Samsung', 'value' => 'samsung'],
                    ['label' => 'Xiaomi', 'value' => 'xiaomi'],
                    ['label' => 'Huawei', 'value' => 'huawei'],
                    ['label' => 'LG', 'value' => 'lg'],
                    ['label' => 'Sony', 'value' => 'sony'],
                    ['label' => 'Другой', 'value' => 'other']
                ], 'is_required' => true],
                ['name' => 'Состояние', 'type' => 'select', 'options' => [
                    ['label' => 'Новый', 'value' => 'new'],
                    ['label' => 'Б/у отличное', 'value' => 'excellent'],
                    ['label' => 'Б/у хорошее', 'value' => 'good'],
                    ['label' => 'Б/у удовлетворительное', 'value' => 'fair']
                ], 'is_required' => true],
                ['name' => 'Цвет', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Гарантия', 'type' => 'checkbox', 'options' => null, 'is_required' => false],
                ['name' => 'Модель', 'type' => 'text', 'options' => null, 'is_required' => false]
            ],
            
            // Автомобили
            'vehicles' => [
                ['name' => 'Марка', 'type' => 'select', 'options' => [
                    ['label' => 'Toyota', 'value' => 'toyota'],
                    ['label' => 'BMW', 'value' => 'bmw'],
                    ['label' => 'Mercedes-Benz', 'value' => 'mercedes'],
                    ['label' => 'Audi', 'value' => 'audi'],
                    ['label' => 'Volkswagen', 'value' => 'volkswagen'],
                    ['label' => 'Hyundai', 'value' => 'hyundai'],
                    ['label' => 'Kia', 'value' => 'kia'],
                    ['label' => 'Другая', 'value' => 'other']
                ], 'is_required' => true],
                ['name' => 'Год выпуска', 'type' => 'number', 'options' => null, 'is_required' => true],
                ['name' => 'Пробег (км)', 'type' => 'number', 'options' => null, 'is_required' => true],
                ['name' => 'Объем двигателя', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Тип топлива', 'type' => 'select', 'options' => [
                    ['label' => 'Бензин', 'value' => 'gasoline'],
                    ['label' => 'Дизель', 'value' => 'diesel'],
                    ['label' => 'Газ', 'value' => 'gas'],
                    ['label' => 'Электро', 'value' => 'electric'],
                    ['label' => 'Гибрид', 'value' => 'hybrid']
                ], 'is_required' => false],
                ['name' => 'Коробка передач', 'type' => 'select', 'options' => [
                    ['label' => 'Механическая', 'value' => 'manual'],
                    ['label' => 'Автоматическая', 'value' => 'automatic'],
                    ['label' => 'Вариатор', 'value' => 'cvt']
                ], 'is_required' => false],
                ['name' => 'Привод', 'type' => 'select', 'options' => [
                    ['label' => 'Передний', 'value' => 'front'],
                    ['label' => 'Задний', 'value' => 'rear'],
                    ['label' => 'Полный', 'value' => 'all']
                ], 'is_required' => false]
            ],
            
            // Недвижимость
            'real_estate' => [
                ['name' => 'Количество комнат', 'type' => 'select', 'options' => [
                    ['label' => '1 комната', 'value' => '1'],
                    ['label' => '2 комнаты', 'value' => '2'],
                    ['label' => '3 комнаты', 'value' => '3'],
                    ['label' => '4 комнаты', 'value' => '4'],
                    ['label' => '5+ комнат', 'value' => '5+']
                ], 'is_required' => true],
                ['name' => 'Площадь (м²)', 'type' => 'number', 'options' => null, 'is_required' => true],
                ['name' => 'Этаж', 'type' => 'number', 'options' => null, 'is_required' => false],
                ['name' => 'Этажность дома', 'type' => 'number', 'options' => null, 'is_required' => false],
                ['name' => 'Материал стен', 'type' => 'select', 'options' => [
                    ['label' => 'Кирпич', 'value' => 'brick'],
                    ['label' => 'Панель', 'value' => 'panel'],
                    ['label' => 'Монолит', 'value' => 'monolith'],
                    ['label' => 'Дерево', 'value' => 'wood']
                ], 'is_required' => false],
                ['name' => 'Ремонт', 'type' => 'select', 'options' => [
                    ['label' => 'Евроремонт', 'value' => 'euro'],
                    ['label' => 'Хороший', 'value' => 'good'],
                    ['label' => 'Обычный', 'value' => 'normal'],
                    ['label' => 'Требуется', 'value' => 'required']
                ], 'is_required' => false]
            ],
            
            // Одежда и аксессуары
            'fashion' => [
                ['name' => 'Размер', 'type' => 'select', 'options' => [
                    ['label' => 'XS', 'value' => 'xs'],
                    ['label' => 'S', 'value' => 's'],
                    ['label' => 'M', 'value' => 'm'],
                    ['label' => 'L', 'value' => 'l'],
                    ['label' => 'XL', 'value' => 'xl'],
                    ['label' => 'XXL', 'value' => 'xxl']
                ], 'is_required' => true],
                ['name' => 'Бренд', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Состояние', 'type' => 'select', 'options' => [
                    ['label' => 'Новое', 'value' => 'new'],
                    ['label' => 'Отличное', 'value' => 'excellent'],
                    ['label' => 'Хорошее', 'value' => 'good'],
                    ['label' => 'Удовлетворительное', 'value' => 'fair']
                ], 'is_required' => true],
                ['name' => 'Цвет', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Материал', 'type' => 'text', 'options' => null, 'is_required' => false]
            ],
            
            // Мебель и интерьер
            'furniture' => [
                ['name' => 'Состояние', 'type' => 'select', 'options' => [
                    ['label' => 'Новое', 'value' => 'new'],
                    ['label' => 'Отличное', 'value' => 'excellent'],
                    ['label' => 'Хорошее', 'value' => 'good'],
                    ['label' => 'Удовлетворительное', 'value' => 'fair']
                ], 'is_required' => true],
                ['name' => 'Материал', 'type' => 'select', 'options' => [
                    ['label' => 'Дерево', 'value' => 'wood'],
                    ['label' => 'Металл', 'value' => 'metal'],
                    ['label' => 'Пластик', 'value' => 'plastic'],
                    ['label' => 'Стекло', 'value' => 'glass'],
                    ['label' => 'Ткань', 'value' => 'fabric']
                ], 'is_required' => false],
                ['name' => 'Цвет', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Размеры', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Возможность доставки', 'type' => 'checkbox', 'options' => null, 'is_required' => false]
            ],
            
            // Услуги
            'services' => [
                ['name' => 'Тип услуги', 'type' => 'text', 'options' => null, 'is_required' => true],
                ['name' => 'Опыт работы (лет)', 'type' => 'number', 'options' => null, 'is_required' => false],
                ['name' => 'Образование', 'type' => 'select', 'options' => [
                    ['label' => 'Высшее', 'value' => 'higher'],
                    ['label' => 'Среднее специальное', 'value' => 'special'],
                    ['label' => 'Курсы', 'value' => 'courses'],
                    ['label' => 'Самоучка', 'value' => 'self_taught']
                ], 'is_required' => false],
                ['name' => 'Выезд на дом', 'type' => 'checkbox', 'options' => null, 'is_required' => false],
                ['name' => 'Срочные заказы', 'type' => 'checkbox', 'options' => null, 'is_required' => false]
            ],
            
            // Работа
            'jobs' => [
                ['name' => 'Тип занятости', 'type' => 'select', 'options' => [
                    ['label' => 'Полная занятость', 'value' => 'full_time'],
                    ['label' => 'Частичная занятость', 'value' => 'part_time'],
                    ['label' => 'Проектная работа', 'value' => 'project'],
                    ['label' => 'Стажировка', 'value' => 'internship']
                ], 'is_required' => true],
                ['name' => 'Опыт работы', 'type' => 'select', 'options' => [
                    ['label' => 'Без опыта', 'value' => 'no_experience'],
                    ['label' => 'От 1 года', 'value' => '1_year'],
                    ['label' => 'От 3 лет', 'value' => '3_years'],
                    ['label' => 'От 5 лет', 'value' => '5_years']
                ], 'is_required' => false],
                ['name' => 'Образование', 'type' => 'select', 'options' => [
                    ['label' => 'Среднее', 'value' => 'secondary'],
                    ['label' => 'Среднее специальное', 'value' => 'special'],
                    ['label' => 'Высшее', 'value' => 'higher']
                ], 'is_required' => false],
                ['label' => 'Удаленная работа', 'type' => 'checkbox', 'options' => null, 'is_required' => false]
            ],
            
            // Универсальные параметры
            'default' => [
                ['name' => 'Состояние', 'type' => 'select', 'options' => [
                    ['label' => 'Новое', 'value' => 'new'],
                    ['label' => 'Отличное', 'value' => 'excellent'],
                    ['label' => 'Хорошее', 'value' => 'good'],
                    ['label' => 'Удовлетворительное', 'value' => 'fair']
                ], 'is_required' => true],
                ['name' => 'Описание', 'type' => 'text', 'options' => null, 'is_required' => false],
                ['name' => 'Торг уместен', 'type' => 'checkbox', 'options' => null, 'is_required' => false]
            ]
        ];

        foreach ($categoriesForParameters as $category) {
            // Определяем тип параметров на основе названия категории или её родителей
            $parameterType = $this->getCategoryParameterType($category);
            $parameters = $parameterTemplates[$parameterType] ?? $parameterTemplates['default'];

            foreach ($parameters as $parameterData) {
                ProductParameter::create([
                    'category_id' => $category->id,
                    'name' => $parameterData['name'],
                    'type' => $parameterData['type'],
                    'options' => $parameterData['options'],
                    'is_required' => $parameterData['is_required']
                ]);
            }

            $this->command->info("Created parameters for category: {$category->name} (ID: {$category->id})");
        }

        $this->command->info("Successfully created parameters for " . $categoriesForParameters->count() . " categories");
    }

    /**
     * Определяет тип параметров для категории
     */
    private function getCategoryParameterType(Category $category): string
    {
        // Получаем полную иерархию категории
        $hierarchy = [];
        $current = $category;
        
        while ($current) {
            $hierarchy[] = mb_strtolower($current->name);
            $current = $current->parent;
        }
        
        $fullPath = implode(' ', array_reverse($hierarchy));
        
        // Определяем тип на основе ключевых слов
        if (str_contains($fullPath, 'электроника') || 
            str_contains($fullPath, 'телефон') || 
            str_contains($fullPath, 'компьютер') ||
            str_contains($fullPath, 'техника')) {
            return 'electronics';
        }
        
        if (str_contains($fullPath, 'транспорт') || 
            str_contains($fullPath, 'автомобил') ||
            str_contains($fullPath, 'мотоцикл')) {
            return 'vehicles';
        }
        
        if (str_contains($fullPath, 'недвижимость') || 
            str_contains($fullPath, 'квартир') ||
            str_contains($fullPath, 'дом') ||
            str_contains($fullPath, 'аренда')) {
            return 'real_estate';
        }
        
        if (str_contains($fullPath, 'одежда') || 
            str_contains($fullPath, 'обувь') ||
            str_contains($fullPath, 'аксессуар') ||
            str_contains($fullPath, 'мода')) {
            return 'fashion';
        }
        
        if (str_contains($fullPath, 'мебель') || 
            str_contains($fullPath, 'интерьер') ||
            str_contains($fullPath, 'дом и сад')) {
            return 'furniture';
        }
        
        if (str_contains($fullPath, 'услуги')) {
            return 'services';
        }
        
        if (str_contains($fullPath, 'работа') || 
            str_contains($fullPath, 'вакансия')) {
            return 'jobs';
        }
        
        return 'default';
    }
}
