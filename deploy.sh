#!/bin/bash

# Скрипт автоматического развертывания Laravel проекта
# Использование: ./deploy.sh

set -e

echo "🚀 Начинаем развертывание Laravel проекта..."

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для вывода сообщений
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Проверка прав root
if [[ $EUID -ne 0 ]]; then
   print_error "Этот скрипт должен быть запущен с правами root"
   exit 1
fi

# Обновление системы
print_status "Обновление системы..."
apt update && apt upgrade -y

# Установка необходимых пакетов
print_status "Установка необходимых пакетов..."
apt install -y nginx mysql-server redis-server supervisor curl git unzip software-properties-common

# Добавление репозитория PHP
print_status "Добавление репозитория PHP..."
add-apt-repository ppa:ondrej/php -y
apt update

# Установка PHP и расширений
print_status "Установка PHP 8.2 и расширений..."
apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-pgsql php8.2-sqlite3 \
php8.2-curl php8.2-dom php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath \
php8.2-intl php8.2-gd php8.2-imagick php8.2-redis

# Установка Node.js
print_status "Установка Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Установка Composer
print_status "Установка Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Установка FFmpeg
print_status "Установка FFmpeg..."
apt install -y ffmpeg

# Запуск сервисов
print_status "Запуск сервисов..."
systemctl enable nginx mysql redis-server supervisor
systemctl start nginx mysql redis-server supervisor

# Настройка MySQL
print_status "Настройка MySQL..."
mysql_secure_installation

print_status "✅ Базовая настройка сервера завершена!"
print_warning "Теперь выполните следующие шаги:"
echo "1. Клонируйте ваш проект в /var/www/"
echo "2. Настройте .env файл"
echo "3. Выполните: composer install --optimize-autoloader --no-dev"
echo "4. Выполните: npm ci && npm run build"
echo "5. Настройте Nginx конфигурацию"
echo "6. Выполните миграции: php artisan migrate"
echo "7. Настройте SSL сертификат"

print_status "Подробные инструкции смотрите в DEPLOYMENT_GUIDE.md"