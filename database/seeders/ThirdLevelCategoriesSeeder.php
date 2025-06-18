<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class ThirdLevelCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * Создает категории третьего уровня для существующих категорий в базе данных.
     */
    public function run(): void
    {
        $this->command->info('Создаем категории третьего уровня...');

        // Создаем третий уровень для Электроники
        $this->createElectronicsThirdLevel();
        
        // Создаем третий уровень для Спорта и отдыха
        $this->createSportsThirdLevel();
        
        // Создаем третий уровень для других категорий
        $this->createOtherThirdLevel();

        $this->command->info('Категории третьего уровня созданы успешно!');
    }

    private function createElectronicsThirdLevel(): void
    {
        $mappings = [
            'Телефоны и планшеты' => [
                ['name' => 'iPhone', 'description' => 'Смартфоны Apple iPhone всех моделей'],
                ['name' => 'Samsung Galaxy', 'description' => 'Смартфоны Samsung Galaxy серии'],
                ['name' => 'Xiaomi', 'description' => 'Смартфоны Xiaomi, Redmi, POCO'],
                ['name' => 'Huawei', 'description' => 'Смартфоны Huawei и Honor'],
                ['name' => 'Планшеты Android', 'description' => 'Планшеты на операционной системе Android'],
                ['name' => 'iPad', 'description' => 'Планшеты Apple iPad'],
            ],
            'Компьютерная техника' => [
                ['name' => 'Игровые ноутбуки', 'description' => 'Мощные ноутбуки для игр'],
                ['name' => 'Бизнес ноутбуки', 'description' => 'Ноутбуки для работы и бизнеса'],
                ['name' => 'Ультрабуки', 'description' => 'Тонкие и легкие ультрабуки'],
                ['name' => 'Настольные ПК', 'description' => 'Стационарные компьютеры'],
                ['name' => 'Мониторы', 'description' => 'Компьютерные мониторы и дисплеи'],
                ['name' => 'Комплектующие', 'description' => 'Процессоры, видеокарты, память'],
            ],
            'Фото- и видеокамеры' => [
                ['name' => 'Зеркальные камеры', 'description' => 'Профессиональные зеркальные фотоаппараты'],
                ['name' => 'Беззеркальные камеры', 'description' => 'Компактные беззеркальные камеры'],
                ['name' => 'Экшн-камеры', 'description' => 'Камеры для экстремальной съемки'],
                ['name' => 'Видеокамеры', 'description' => 'Камеры для видеосъемки'],
                ['name' => 'Объективы', 'description' => 'Объективы для фотоаппаратов'],
                ['name' => 'Аксессуары', 'description' => 'Штативы, фильтры, вспышки'],
            ],
            'ТВ, аудио, видео' => [
                ['name' => 'Телевизоры LED', 'description' => 'LED телевизоры различных размеров'],
                ['name' => 'Smart TV', 'description' => 'Умные телевизоры с интернетом'],
                ['name' => 'Проекторы', 'description' => 'Мультимедийные проекторы'],
                ['name' => 'Наушники', 'description' => 'Проводные и беспроводные наушники'],
                ['name' => 'Акустические системы', 'description' => 'Колонки и звуковые системы'],
                ['name' => 'Аудиоплееры', 'description' => 'MP3 плееры и аудиоустройства'],
            ],
            'Бытовая техника' => [
                ['name' => 'Холодильники', 'description' => 'Холодильники и морозильные камеры'],
                ['name' => 'Стиральные машины', 'description' => 'Автоматические стиральные машины'],
                ['name' => 'Кухонная техника', 'description' => 'Плиты, духовки, микроволновки'],
                ['name' => 'Мелкая техника', 'description' => 'Чайники, блендеры, тостеры'],
                ['name' => 'Пылесосы', 'description' => 'Обычные и роботы-пылесосы'],
                ['name' => 'Климатическая техника', 'description' => 'Кондиционеры, обогреватели'],
            ],
        ];

        $this->createThirdLevelFromMappings($mappings);
    }

    private function createSportsThirdLevel(): void
    {
        $mappings = [
            'Тренажеры и фитнес' => [
                ['name' => 'Силовые тренажеры', 'description' => 'Тренажеры для силовых тренировок'],
                ['name' => 'Кардио тренажеры', 'description' => 'Беговые дорожки, велотренажеры'],
                ['name' => 'Свободные веса', 'description' => 'Гантели, штанги, гири'],
                ['name' => 'Коврики и аксессуары', 'description' => 'Коврики для йоги, фитнес аксессуары'],
                ['name' => 'Спортивное питание', 'description' => 'Протеины, аминокислоты, витамины'],
            ],
            'Игры с мячом' => [
                ['name' => 'Футбол', 'description' => 'Мячи, форма, бутсы для футбола'],
                ['name' => 'Баскетбол', 'description' => 'Мячи, кольца, форма для баскетбола'],
                ['name' => 'Волейбол', 'description' => 'Мячи, сетки, форма для волейбола'],
                ['name' => 'Теннис', 'description' => 'Ракетки, мячи, аксессуары для тенниса'],
                ['name' => 'Настольный теннис', 'description' => 'Ракетки и мячи для пинг-понга'],
            ],
            'Велосипеды' => [
                ['name' => 'Горные велосипеды', 'description' => 'MTB велосипеды для бездорожья'],
                ['name' => 'Шоссейные велосипеды', 'description' => 'Скоростные велосипеды для асфальта'],
                ['name' => 'Городские велосипеды', 'description' => 'Комфортные велосипеды для города'],
                ['name' => 'Детские велосипеды', 'description' => 'Велосипеды для детей всех возрастов'],
                ['name' => 'Электровелосипеды', 'description' => 'Велосипеды с электрическим приводом'],
            ],
            'Зимние виды спорта' => [
                ['name' => 'Горные лыжи', 'description' => 'Лыжи, ботинки, крепления для горных лыж'],
                ['name' => 'Сноуборд', 'description' => 'Сноуборды, ботинки, крепления'],
                ['name' => 'Беговые лыжи', 'description' => 'Лыжи для классического и конькового хода'],
                ['name' => 'Коньки', 'description' => 'Фигурные, хоккейные, прогулочные коньки'],
                ['name' => 'Санки и тюбинги', 'description' => 'Санки, ледянки, тюбинги для катания'],
            ],
            'Водные виды спорта' => [
                ['name' => 'Плавание', 'description' => 'Купальники, очки, аксессуары для плавания'],
                ['name' => 'Серфинг', 'description' => 'Доски для серфинга и аксессуары'],
                ['name' => 'Дайвинг', 'description' => 'Снаряжение для подводного плавания'],
                ['name' => 'Водные лыжи', 'description' => 'Лыжи и доски для катания по воде'],
                ['name' => 'Каякинг', 'description' => 'Каяки, весла, спасательные жилеты'],
            ],
            'Охота и рыбалка' => [
                ['name' => 'Удочки и спиннинги', 'description' => 'Удилища для разных видов рыбалки'],
                ['name' => 'Катушки', 'description' => 'Безынерционные и мультипликаторные катушки'],
                ['name' => 'Приманки', 'description' => 'Блесны, воблеры, мягкие приманки'],
                ['name' => 'Лодки', 'description' => 'Надувные и пластиковые лодки'],
                ['name' => 'Экипировка', 'description' => 'Одежда и снаряжение для охоты и рыбалки'],
            ],
        ];

        $this->createThirdLevelFromMappings($mappings);
    }

    private function createOtherThirdLevel(): void
    {
        // Создаем третий уровень для других популярных категорий
        $mappings = [
            'Собаки' => [
                ['name' => 'Корм для собак', 'description' => 'Сухой и влажный корм для собак'],
                ['name' => 'Игрушки для собак', 'description' => 'Мячики, канаты, интерактивные игрушки'],
                ['name' => 'Аксессуары', 'description' => 'Ошейники, поводки, намордники'],
                ['name' => 'Уход и гигиена', 'description' => 'Шампуни, расчески, когтерезы'],
                ['name' => 'Домики и лежанки', 'description' => 'Спальные места для собак'],
            ],
            'Кошки' => [
                ['name' => 'Корм для кошек', 'description' => 'Сухой и влажный корм для кошек'],
                ['name' => 'Наполнители', 'description' => 'Наполнители для кошачьих туалетов'],
                ['name' => 'Игрушки для кошек', 'description' => 'Мышки, дразнилки, когтеточки'],
                ['name' => 'Переноски', 'description' => 'Сумки и контейнеры для транспортировки'],
                ['name' => 'Домики и лежанки', 'description' => 'Спальные места и домики для кошек'],
            ],
            'Автомобили' => [
                ['name' => 'Седаны', 'description' => 'Легковые автомобили седан'],
                ['name' => 'Хэтчбеки', 'description' => 'Компактные автомобили хэтчбек'],
                ['name' => 'Внедорожники', 'description' => 'SUV и кроссоверы'],
                ['name' => 'Универсалы', 'description' => 'Автомобили с увеличенным багажником'],
                ['name' => 'Купе и кабриолеты', 'description' => 'Спортивные и открытые автомобили'],
            ],
        ];

        $this->createThirdLevelFromMappings($mappings);
    }

    private function createThirdLevelFromMappings(array $mappings): void
    {
        foreach ($mappings as $subcategoryName => $thirdLevelData) {
            $subcategory = Category::where('name', $subcategoryName)->first();
            
            if (!$subcategory) {
                $this->command->warn("Подкатегория '{$subcategoryName}' не найдена, пропускаем.");
                continue;
            }

            $this->command->info("Создаем третий уровень для: {$subcategoryName}");

            foreach ($thirdLevelData as $categoryData) {
                // Проверим, не существует ли уже такая категория
                $exists = Category::where('name', $categoryData['name'])
                    ->where('parent_id', $subcategory->id)
                    ->exists();

                if (!$exists) {
                    $category = Category::create([
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'parent_id' => $subcategory->id,
                    ]);

                    $this->command->info("  ✓ Создана: {$category->name}");
                } else {
                    $this->command->info("  - Уже существует: {$categoryData['name']}");
                }
            }
        }
    }
}
