# Оптимизация видео для экономии места на сервере

## Обзор

Система автоматически оптимизирует загружаемые видео для экономии места на сервере и улучшения производительности.

## Что делает оптимизация

### 1. 🎬 Сжатие видео
- **Кодек**: Конвертация в H.264 (MP4)
- **Качество**: CRF 28 (оптимальный баланс качества/размера)
- **Разрешение**: Максимум 1280x720 (720p)
- **Аудио**: AAC 128kbps

### 2. 📱 Адаптивное сжатие
- **1080p+**: Сжимается до 720p с CRF 30
- **720p**: Умеренное сжатие с CRF 28
- **<720p**: Легкое сжатие с CRF 26

### 3. 🖼️ Создание превью
- **Размер**: 320x240 пикселей
- **Время**: 3 секунды или 10% от длительности
- **Формат**: JPEG

## Настройка

### Переменные окружения (.env)

```env
# Основные настройки видео
VIDEO_MAX_FILE_SIZE=104857600          # 100MB максимум
VIDEO_OPTIMIZATION_ENABLED=true        # Включить оптимизацию
VIDEO_GENERATE_THUMBNAILS=true         # Создавать превью

# Параметры оптимизации
VIDEO_MAX_WIDTH=1280                   # Максимальная ширина
VIDEO_MAX_HEIGHT=720                   # Максимальная высота
VIDEO_QUALITY=28                       # Качество (CRF)
VIDEO_PRESET=medium                    # Скорость сжатия
VIDEO_AUDIO_BITRATE=128k              # Битрейт аудио

# FFmpeg пути
FFMPEG_ENABLED=true
FFMPEG_PATH=/opt/homebrew/bin/ffmpeg
FFPROBE_PATH=/opt/homebrew/bin/ffprobe
```

## Установка FFmpeg

### macOS (Homebrew)
```bash
brew install ffmpeg
```

### Ubuntu/Debian
```bash
sudo apt update
sudo apt install ffmpeg
```

### CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install ffmpeg
```

## API изменения

### Ответ при загрузке видео

**Было:**
```json
{
    "path": "videos/video.mov",
    "size": 50000000,
    "mime_type": "video/quicktime"
}
```

**Стало:**
```json
{
    "path": "videos/video_optimized.mp4",
    "url": "https://yourapi.com/storage/videos/video_optimized.mp4",
    "thumbnail": "https://yourapi.com/storage/thumbnails/video.jpg",
    "original_name": "video.mov",
    "original_size": 50000000,
    "optimized_size": 15000000,
    "compression_ratio": 70.0,
    "mime_type": "video/mp4",
    "duration": 120
}
```

## Команды Artisan

### Оптимизация существующих видео

```bash
# Оптимизировать все неоптимизированные видео
php artisan videos:optimize

# Принудительно переоптимизировать все видео
php artisan videos:optimize --force
```

**Пример вывода:**
```
Starting video optimization...
Found 25 videos to process.
 25/25 [████████████████████████████] 100%

Optimized: iPhone видео.mov
Size: 45.2 MB → 12.8 MB
Saved: 32.4 MB (71.7%)

Video optimization completed!
Optimized: 23 videos
Skipped: 2 videos
Errors: 0 videos
Total space saved: 456.7 MB
Average compression: 68.3%
```

## Экономия места

### Типичные результаты сжатия:

| Исходный формат | Размер до | Размер после | Экономия |
|----------------|-----------|--------------|----------|
| iPhone MOV     | 45 MB     | 13 MB        | 71%      |
| Android MP4    | 32 MB     | 11 MB        | 66%      |
| Камера AVI     | 78 MB     | 18 MB        | 77%      |
| Drone 4K       | 120 MB    | 25 MB        | 79%      |

### Среднее сжатие: **70-75%**

## Качество видео

### Настройки CRF (Constant Rate Factor):
- **18-23**: Высокое качество (большой размер)
- **24-28**: Хорошее качество (рекомендуется) ✅
- **29-35**: Среднее качество (маленький размер)
- **36+**: Низкое качество

### Текущие настройки:
- **CRF 28**: Оптимальный баланс
- **Preset medium**: Хорошая скорость сжатия
- **720p max**: Подходит для мобильных устройств

## Мониторинг

### Проверка статуса оптимизации

```php
// В контроллере
$product = Product::find(1);
$isOptimized = str_contains($product->video, '_optimized');
$hasThumb = !empty($product->thumbnail);
```

### Статистика сжатия

```php
// Получить статистику по всем видео
$stats = Product::whereNotNull('video')
    ->selectRaw('
        COUNT(*) as total_videos,
        SUM(CASE WHEN video LIKE "%_optimized%" THEN 1 ELSE 0 END) as optimized_count,
        AVG(CASE WHEN video LIKE "%_optimized%" THEN compression_ratio ELSE NULL END) as avg_compression
    ')
    ->first();
```

## Troubleshooting

### FFmpeg не найден
```bash
# Проверить установку
which ffmpeg
ffmpeg -version

# Обновить путь в .env
FFMPEG_PATH=/usr/local/bin/ffmpeg
```

### Ошибки сжатия
```bash
# Проверить логи
tail -f storage/logs/laravel.log

# Тестовое сжатие
ffmpeg -i input.mov -c:v libx264 -crf 28 output.mp4
```

### Недостаточно места
```bash
# Очистить временные файлы
php artisan cache:clear
rm -rf storage/app/public/temp/*

# Проверить место на диске
df -h
```

## Рекомендации

### Для продакшена:
1. **Настройте cron** для автоматической оптимизации:
   ```bash
   0 2 * * * cd /path/to/project && php artisan videos:optimize
   ```

2. **Мониторьте место на диске**:
   ```bash
   # Добавить в cron
   0 6 * * * df -h | mail -s "Disk Usage" admin@yoursite.com
   ```

3. **Резервное копирование** оптимизированных видео

### Для разработки:
1. Отключите оптимизацию: `VIDEO_OPTIMIZATION_ENABLED=false`
2. Используйте меньшие тестовые файлы
3. Проверяйте логи на ошибки

## Производительность

### Время оптимизации:
- **10 MB видео**: ~30 секунд
- **50 MB видео**: ~2 минуты  
- **100 MB видео**: ~4 минуты

### Рекомендации:
- Используйте **очереди** для больших файлов
- Оптимизируйте в **фоновом режиме**
- Показывайте **прогресс** пользователю

Оптимизация видео поможет сэкономить до **75% места** на сервере при сохранении приемлемого качества для мобильных приложений!