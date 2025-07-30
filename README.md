<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# API Документация

## Аутентификация

### Регистрация
```http
POST /api/register
```

**Параметры:**
- `name` (string, required) - Имя пользователя
- `surname` (string, required) - Фамилия пользователя
- `email` (string, required) - Email пользователя
- `phone_number` (string, required) - Номер телефона
- `password` (string, required) - Пароль
- `city_id` (integer, required) - ID города

### Вход
```http
POST /api/login
```

**Параметры:**
- `email` (string, required) - Email пользователя
- `password` (string, required) - Пароль

### Выход
```http
POST /api/logout
```
Требуется авторизация.

## Профиль пользователя

### Получение информации о пользователе
```http
GET /api/user
```
Требуется авторизация.

### Обновление профиля
```http
PUT /api/update-profile
```
Требуется авторизация.

**Параметры:**
- `name` (string, optional) - Имя пользователя
- `surname` (string, optional) - Фамилия пользователя
- `phone_number` (string, optional) - Номер телефона
- `city_id` (integer, optional) - ID города

### Обновление аватара
```http
POST /api/update-avatar
```
Требуется авторизация.

**Параметры:**
- `avatar` (file, required) - Файл изображения

### Обновление пароля
```http
PUT /api/update-password
```
Требуется авторизация.

**Параметры:**
- `current_password` (string, required) - Текущий пароль
- `password` (string, required) - Новый пароль
- `password_confirmation` (string, required) - Подтверждение нового пароля

## Тарифы

### Получение списка тарифов
```http
GET /api/tariffs
```

### Получение информации о тарифе
```http
GET /api/tariffs/{tariff}
```

### Создание заявки на тариф
```http
POST /api/tariff-requests
```
Требуется авторизация.

**Параметры:**
- `tariff_id` (integer, required) - ID тарифа
- `comment` (string, optional) - Комментарий к заявке

### Получение заявок пользователя
```http
GET /api/tariff-requests/user
```
Требуется авторизация.

### Получение всех заявок
```http
GET /api/tariff-requests
```
Требуется авторизация.

### Обновление статуса заявки
```http
PUT /api/tariff-requests/{tariffRequest}/status
```
Требуется авторизация.

**Параметры:**
- `status` (string, required) - Новый статус (approved/rejected)

## Верификация паспорта

### Создание заявки на верификацию
```http
POST /api/passport-verification
```
Требуется авторизация.

**Параметры:**
- `passport_number` (string, required) - Номер паспорта
- `passport_series` (string, required) - Серия паспорта
- `passport_photo` (file, required) - Фото паспорта

### Получение статуса верификации
```http
GET /api/passport-verification
```
Требуется авторизация.

### Получение всех заявок на верификацию
```http
GET /api/passport-verifications
```
Требуется авторизация.

### Обновление статуса верификации
```http
PUT /api/passport-verifications/{verification}/status
```
Требуется авторизация.

**Параметры:**
- `status` (string, required) - Новый статус (approved/rejected)

## Категории

### Получение списка категорий
```http
GET /api/categories
```

### Получение информации о категории
```http
GET /api/categories/{category}
```

### Получение параметров категории
```http
GET /api/categories/{category}/parameters
```

## Продукты

### Публичные маршруты

#### Получение списка продуктов
```http
GET /api/public/products
```

**Параметры:**
- `category_id` (integer, optional) - ID категории
- `city_id` (integer, optional) - ID города
- `search` (string, optional) - Поисковый запрос
- `min_price` (number, optional) - Минимальная цена
- `max_price` (number, optional) - Максимальная цена
- `sort` (string, optional) - Сортировка (price_asc, price_desc, newest)

#### Получение информации о продукте
```http
GET /api/public/products/{product}
```

### Защищенные маршруты

#### Получение списка продуктов пользователя
```http
GET /api/products
```
Требуется авторизация.

#### Создание продукта
```http
POST /api/products
```
Требуется авторизация.

**Параметры:**
- `category_id` (integer, required) - ID категории
- `name` (string, required) - Название продукта
- `description` (string, required) - Описание продукта
- `main_photo` (file, required) - Главное фото
- `video` (file, optional) - Видео
- `price` (number, required) - Цена
- `address` (string, required) - Адрес
- `city_id` (integer, required) - ID города
- `is_video_call_available` (boolean, optional) - Доступность видеозвонка
- `expires_at` (datetime, required) - Дата окончания публикации
- `parameters` (array, optional) - Параметры продукта

#### Обновление продукта
```http
PUT /api/products/{product}
```
Требуется авторизация.

**Параметры:** те же, что и при создании

#### Удаление продукта
```http
DELETE /api/products/{product}
```
Требуется авторизация.

#### Обновление параметров продукта
```http
POST /api/products/{product}/parameters
```
Требуется авторизация.

**Параметры:**
- `parameters` (array, required) - Массив параметров

## Избранное

### Получение списка избранных продуктов
```http
GET /api/favorites
```
Требуется авторизация.

### Добавление в избранное
```http
POST /api/favorites
```
Требуется авторизация.

**Параметры:**
- `product_id` (integer, required) - ID продукта

### Удаление из избранного
```http
DELETE /api/favorites/{product}
```
Требуется авторизация.

### Проверка избранного
```http
GET /api/favorites/check/{product}
```
Требуется авторизация.

## Магазины

### Публичные маршруты

#### Получение списка магазинов
```http
GET /api/public/shops
```

**Параметры:**
- `city_id` (integer, optional) - ID города
- `search` (string, optional) - Поисковый запрос
- `min_rating` (number, optional) - Минимальный рейтинг

#### Получение информации о магазине
```http
GET /api/public/shops/{shop}
```

#### Получение отзывов о магазине
```http
GET /api/public/shops/{shop}/reviews
```

### Защищенные маршруты

#### Получение информации о своем магазине
```http
GET /api/my-shop
```
Требуется авторизация.

#### Создание магазина
```http
POST /api/shops
```
Требуется авторизация.

**Параметры:**
- `name` (string, required) - Название магазина
- `description` (string, required) - Описание магазина
- `banner` (file, optional) - Баннер магазина
- `logo` (file, optional) - Логотип магазина
- `phone` (string, optional) - Телефон
- `email` (string, optional) - Email
- `address` (string, optional) - Адрес
- `city_id` (integer, optional) - ID города
- `working_hours` (array, optional) - Часы работы
- `social_links` (array, optional) - Ссылки на соц. сети

#### Обновление магазина
```http
PUT /api/shops/{shop}
```
Требуется авторизация.

**Параметры:** те же, что и при создании

#### Удаление магазина
```http
DELETE /api/shops/{shop}
```
Требуется авторизация.

### Отзывы о магазине

#### Создание отзыва
```http
POST /api/shops/{shop}/reviews
```
Требуется авторизация.

**Параметры:**
- `rating` (integer, required) - Оценка (1-5)
- `comment` (string, optional) - Комментарий

#### Обновление отзыва
```http
PUT /api/shops/{shop}/reviews/{review}
```
Требуется авторизация.

**Параметры:** те же, что и при создании

#### Удаление отзыва
```http
DELETE /api/shops/{shop}/reviews/{review}
```
Требуется авторизация.
# korset_backend

