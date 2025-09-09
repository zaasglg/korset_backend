#!/bin/bash

# Скрипт оптимизации Laravel проекта для продакшена
# Использование: ./optimize.sh

set -e

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Проверка, что мы в директории Laravel проекта
if [ ! -f "artisan" ]; then
    echo "❌ Ошибка: artisan файл не найден. Убедитесь, что вы находитесь в корневой директории Laravel проекта."
    exit 1
fi

print_status "🚀 Начинаем оптимизацию Laravel проекта..."

# Включаем режим обслуживания
print_status "Включение режима обслуживания..."
php artisan down --message="Обновление системы" --retry=60

# Очистка всех кэшей
print_status "Очистка кэшей..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

# Установка/обновление зависимостей
print_status "Обновление Composer зависимостей..."
composer install --optimize-autoloader --no-dev --no-interaction

# Сборка фронтенда
if [ -f "package.json" ]; then
    print_status "Сборка фронтенд ресурсов..."
    npm ci --production
    npm run build
fi

# Генерация ключа приложения (если не существует)
if ! grep -q "APP_KEY=base64:" .env; then
    print_status "Генерация ключа приложения..."
    php artisan key:generate --force
fi

# Выполнение миграций
print_status "Выполнение миграций..."
php artisan migrate --force

# Создание символической ссылки для storage
print_status "Создание символической ссылки для storage..."
php artisan storage:link

# Кэширование для продакшена
print_status "Создание кэшей для продакшена..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Оптимизация автозагрузчика
print_status "Оптимизация автозагрузчика..."
php artisan optimize

# Установка правильных прав доступа
print_status "Установка прав доступа..."
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

# Перезапуск очередей (если supervisor настроен)
if systemctl is-active --quiet supervisor; then
    print_status "Перезапуск очередей..."
    supervisorctl restart laravel-worker:* 2>/dev/null || print_warning "Очереди не настроены или не запущены"
fi

# Перезапуск PHP-FPM
print_status "Перезапуск PHP-FPM..."
systemctl reload php8.2-fpm

# Перезапуск Nginx
print_status "Перезапуск Nginx..."
systemctl reload nginx

# Выключение режима обслуживания
print_status "Выключение режима обслуживания..."
php artisan up

print_status "✅ Оптимизация завершена успешно!"

# Проверка статуса
print_status "Проверка статуса приложения..."
echo "📊 Статистика:"
echo "   - PHP версия: $(php -v | head -n1)"
echo "   - Laravel версия: $(php artisan --version)"
echo "   - Размер кэша конфигурации: $(du -h bootstrap/cache/config.php 2>/dev/null | cut -f1 || echo 'не создан')"
echo "   - Размер кэша маршрутов: $(du -h bootstrap/cache/routes-v7.php 2>/dev/null | cut -f1 || echo 'не создан')"
echo "   - Размер кэша представлений: $(du -sh storage/framework/views/ 2>/dev/null | cut -f1 || echo 'пуст')"

print_warning "Рекомендации после оптимизации:"
echo "1. Проверьте работу сайта в браузере"
echo "2. Мониторьте логи: tail -f storage/logs/laravel.log"
echo "3. Проверьте работу очередей: php artisan queue:work --once"
echo "4. Настройте мониторинг производительности"