<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryThirdLevelSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Найдем все категории второго уровня (подкатегории)
        $subcategories = Category::whereHas('parent', function ($query) {
            $query->whereNull('parent_id');
        })->get();

        // Создадим подподкатегории для каждой подкатегории
        foreach ($subcategories as $subcategory) {
            $this->createThirdLevelCategories($subcategory);
        }
    }

    private function createThirdLevelCategories(Category $subcategory): void
    {
        // Определим подподкатегории на основе названия подкатегории
        $thirdLevelCategories = $this->getThirdLevelCategoriesFor($subcategory->name);

        foreach ($thirdLevelCategories as $categoryData) {
            // Проверим, не существует ли уже такая категория
            $exists = Category::where('name', $categoryData['name'])
                ->where('parent_id', $subcategory->id)
                ->exists();

            if (!$exists) {
                Category::create([
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'parent_id' => $subcategory->id,
                    'photo' => null, // Можно добавить фото позже
                ]);
            }
        }
    }

    private function getThirdLevelCategoriesFor(string $subcategoryName): array
    {
        // Возвращаем массив подподкатегорий в зависимости от названия подкатегории
        $mappings = [
            // Для одежды
            'Мужская одежда' => [
                ['name' => 'Рубашки деловые', 'description' => 'Деловые и офисные рубашки'],
                ['name' => 'Рубашки повседневные', 'description' => 'Повседневные рубашки для отдыха'],
                ['name' => 'Джинсы классические', 'description' => 'Классические джинсы прямого кроя'],
                ['name' => 'Джинсы зауженные', 'description' => 'Зауженные и облегающие джинсы'],
            ],
            'Женская одежда' => [
                ['name' => 'Платья вечерние', 'description' => 'Вечерние и коктейльные платья'],
                ['name' => 'Платья повседневные', 'description' => 'Повседневные платья на каждый день'],
                ['name' => 'Блузки деловые', 'description' => 'Деловые блузки для офиса'],
                ['name' => 'Блузки летние', 'description' => 'Летние легкие блузки'],
            ],
            'Детская одежда' => [
                ['name' => 'Одежда для новорожденных', 'description' => 'Одежда для малышей 0-12 месяцев'],
                ['name' => 'Одежда для мальчиков', 'description' => 'Одежда для мальчиков 1-16 лет'],
                ['name' => 'Одежда для девочек', 'description' => 'Одежда для девочек 1-16 лет'],
                ['name' => 'Школьная форма', 'description' => 'Школьная форма и аксессуары'],
            ],

            // Для электроники
            'Смартфоны' => [
                ['name' => 'iPhone', 'description' => 'Смартфоны Apple iPhone всех моделей'],
                ['name' => 'Samsung Galaxy', 'description' => 'Смартфоны Samsung Galaxy серии'],
                ['name' => 'Xiaomi', 'description' => 'Смартфоны Xiaomi, Redmi, POCO'],
                ['name' => 'Huawei', 'description' => 'Смартфоны Huawei и Honor'],
            ],
            'Ноутбуки' => [
                ['name' => 'Игровые ноутбуки', 'description' => 'Мощные ноутбуки для игр'],
                ['name' => 'Бизнес ноутбуки', 'description' => 'Ноутбуки для работы и бизнеса'],
                ['name' => 'Ультрабуки', 'description' => 'Тонкие и легкие ультрабуки'],
                ['name' => 'Трансформеры', 'description' => 'Ноутбуки-трансформеры 2 в 1'],
            ],
            'Аксессуары' => [
                ['name' => 'Чехлы для телефонов', 'description' => 'Защитные чехлы и бамперы'],
                ['name' => 'Наушники', 'description' => 'Проводные и беспроводные наушники'],
                ['name' => 'Зарядные устройства', 'description' => 'Зарядки, кабели, powerbank'],
                ['name' => 'Защитные стекла', 'description' => 'Защитные стекла для экранов'],
            ],

            // Для дома и сада
            'Мебель для дома' => [
                ['name' => 'Диваны и кресла', 'description' => 'Мягкая мебель для гостиной'],
                ['name' => 'Столы и стулья', 'description' => 'Обеденные и письменные столы'],
                ['name' => 'Шкафы и комоды', 'description' => 'Мебель для хранения'],
                ['name' => 'Кровати и матрасы', 'description' => 'Спальная мебель'],
            ],
            'Садовая техника' => [
                ['name' => 'Газонокосилки', 'description' => 'Электрические и бензиновые газонокосилки'],
                ['name' => 'Триммеры', 'description' => 'Травокосилки и триммеры'],
                ['name' => 'Садовые инструменты', 'description' => 'Лопаты, грабли, секаторы'],
                ['name' => 'Поливочное оборудование', 'description' => 'Шланги, распылители, системы полива'],
            ],
            'Декор' => [
                ['name' => 'Картины и постеры', 'description' => 'Настенный декор и искусство'],
                ['name' => 'Вазы и горшки', 'description' => 'Декоративные вазы и цветочные горшки'],
                ['name' => 'Свечи и подсвечники', 'description' => 'Ароматические свечи и подсвечники'],
                ['name' => 'Текстиль', 'description' => 'Декоративные подушки, пледы, шторы'],
            ],

            // Для спорта
            'Фитнес оборудование' => [
                ['name' => 'Тренажеры силовые', 'description' => 'Силовые тренажеры для дома'],
                ['name' => 'Кардио тренажеры', 'description' => 'Беговые дорожки, велотренажеры'],
                ['name' => 'Свободные веса', 'description' => 'Гантели, штанги, гири'],
                ['name' => 'Коврики и аксессуары', 'description' => 'Коврики для йоги, фитнес аксессуары'],
            ],
            'Командные виды спорта' => [
                ['name' => 'Футбол', 'description' => 'Мячи, форма, бутсы для футбола'],
                ['name' => 'Баскетбол', 'description' => 'Мячи, кольца, форма для баскетбола'],
                ['name' => 'Волейбол', 'description' => 'Мячи, сетки, форма для волейбола'],
                ['name' => 'Теннис', 'description' => 'Ракетки, мячи, аксессуары для тенниса'],
            ],
        ];

        return $mappings[$subcategoryName] ?? [
            ['name' => 'Разное', 'description' => 'Прочие товары в категории ' . $subcategoryName],
        ];
    }
}
