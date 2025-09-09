# Flutter интеграция с оптимизированными видео

## Обзор

API теперь автоматически оптимизирует видео и предоставляет дополнительную информацию для Flutter приложения.

## Новый формат ответа API

### Загрузка видео

**POST** `/api/products/{id}`

**Ответ:**
```json
{
    "status": "success",
    "message": "Product created successfully",
    "data": {
        "id": 1,
        "name": "iPhone 15 Pro",
        "video": "videos/iphone_optimized.mp4",
        "video_thumbnail": "thumbnails/iphone.jpg",
        "original_video_size": 45000000,
        "optimized_video_size": 13000000,
        "compression_ratio": 71.11,
        "video_duration": 120,
        "video_url": "https://api.com/storage/videos/iphone_optimized.mp4",
        "thumbnail_url": "https://api.com/storage/thumbnails/iphone.jpg"
    }
}
```

### Статистика видео

**GET** `/api/products/video-stats`

**Ответ:**
```json
{
    "status": "success",
    "data": {
        "total_videos": 150,
        "optimized_count": 145,
        "thumbnails_count": 145,
        "optimization_percentage": 96.7,
        "average_compression": 72.3,
        "total_saved_mb": 2847.5,
        "average_duration_seconds": 95
    }
}
```

## Flutter модели

### Product модель

```dart
class Product {
  final int id;
  final String name;
  final String? video;
  final String? videoThumbnail;
  final int? originalVideoSize;
  final int? optimizedVideoSize;
  final double? compressionRatio;
  final int? videoDuration;
  final String? videoUrl;
  final String? thumbnailUrl;

  Product({
    required this.id,
    required this.name,
    this.video,
    this.videoThumbnail,
    this.originalVideoSize,
    this.optimizedVideoSize,
    this.compressionRatio,
    this.videoDuration,
    this.videoUrl,
    this.thumbnailUrl,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      video: json['video'],
      videoThumbnail: json['video_thumbnail'],
      originalVideoSize: json['original_video_size'],
      optimizedVideoSize: json['optimized_video_size'],
      compressionRatio: json['compression_ratio']?.toDouble(),
      videoDuration: json['video_duration'],
      videoUrl: json['video_url'],
      thumbnailUrl: json['thumbnail_url'],
    );
  }

  // Проверка наличия видео
  bool get hasVideo => video != null && video!.isNotEmpty;
  
  // Проверка наличия превью
  bool get hasThumbnail => videoThumbnail != null && videoThumbnail!.isNotEmpty;
  
  // Проверка оптимизации
  bool get isOptimized => video?.contains('_optimized') ?? false;
  
  // Форматированная длительность
  String get formattedDuration {
    if (videoDuration == null) return '';
    final minutes = videoDuration! ~/ 60;
    final seconds = videoDuration! % 60;
    return '${minutes}:${seconds.toString().padLeft(2, '0')}';
  }
  
  // Форматированный размер
  String get formattedSize {
    if (optimizedVideoSize == null) return '';
    return _formatBytes(optimizedVideoSize!);
  }
  
  // Экономия места
  String get savedSpace {
    if (originalVideoSize == null || optimizedVideoSize == null) return '';
    final saved = originalVideoSize! - optimizedVideoSize!;
    return _formatBytes(saved);
  }
  
  String _formatBytes(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
  }
}
```

### VideoStats модель

```dart
class VideoStats {
  final int totalVideos;
  final int optimizedCount;
  final int thumbnailsCount;
  final double optimizationPercentage;
  final double averageCompression;
  final double totalSavedMb;
  final int averageDurationSeconds;

  VideoStats({
    required this.totalVideos,
    required this.optimizedCount,
    required this.thumbnailsCount,
    required this.optimizationPercentage,
    required this.averageCompression,
    required this.totalSavedMb,
    required this.averageDurationSeconds,
  });

  factory VideoStats.fromJson(Map<String, dynamic> json) {
    return VideoStats(
      totalVideos: json['total_videos'],
      optimizedCount: json['optimized_count'],
      thumbnailsCount: json['thumbnails_count'],
      optimizationPercentage: json['optimization_percentage'].toDouble(),
      averageCompression: json['average_compression'].toDouble(),
      totalSavedMb: json['total_saved_mb'].toDouble(),
      averageDurationSeconds: json['average_duration_seconds'],
    );
  }
}
```

## Flutter виджеты

### Видео плеер с превью

```dart
class OptimizedVideoPlayer extends StatefulWidget {
  final Product product;
  
  const OptimizedVideoPlayer({Key? key, required this.product}) : super(key: key);
  
  @override
  _OptimizedVideoPlayerState createState() => _OptimizedVideoPlayerState();
}

class _OptimizedVideoPlayerState extends State<OptimizedVideoPlayer> {
  VideoPlayerController? _controller;
  bool _isLoading = true;
  bool _showThumbnail = true;

  @override
  void initState() {
    super.initState();
    if (widget.product.hasVideo) {
      _initializeVideo();
    }
  }

  void _initializeVideo() async {
    _controller = VideoPlayerController.network(widget.product.videoUrl!);
    await _controller!.initialize();
    setState(() {
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.product.hasVideo) {
      return Container(
        height: 200,
        color: Colors.grey[300],
        child: Icon(Icons.videocam_off, size: 50),
      );
    }

    return Stack(
      children: [
        // Превью изображение
        if (_showThumbnail && widget.product.hasThumbnail)
          Image.network(
            widget.product.thumbnailUrl!,
            height: 200,
            width: double.infinity,
            fit: BoxFit.cover,
          ),
        
        // Видео плеер
        if (!_showThumbnail && _controller != null)
          AspectRatio(
            aspectRatio: _controller!.value.aspectRatio,
            child: VideoPlayer(_controller!),
          ),
        
        // Кнопка воспроизведения
        Positioned.fill(
          child: Center(
            child: GestureDetector(
              onTap: _togglePlayback,
              child: Container(
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.black54,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  _showThumbnail ? Icons.play_arrow : Icons.pause,
                  color: Colors.white,
                  size: 32,
                ),
              ),
            ),
          ),
        ),
        
        // Информация о видео
        Positioned(
          bottom: 8,
          right: 8,
          child: Container(
            padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.black54,
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              widget.product.formattedDuration,
              style: TextStyle(color: Colors.white, fontSize: 12),
            ),
          ),
        ),
        
        // Индикатор оптимизации
        if (widget.product.isOptimized)
          Positioned(
            top: 8,
            left: 8,
            child: Container(
              padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: Colors.green,
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                'Optimized',
                style: TextStyle(color: Colors.white, fontSize: 10),
              ),
            ),
          ),
      ],
    );
  }

  void _togglePlayback() {
    if (_showThumbnail) {
      setState(() {
        _showThumbnail = false;
      });
      _controller?.play();
    } else {
      if (_controller!.value.isPlaying) {
        _controller?.pause();
      } else {
        _controller?.play();
      }
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }
}
```

### Информация об оптимизации

```dart
class VideoOptimizationInfo extends StatelessWidget {
  final Product product;
  
  const VideoOptimizationInfo({Key? key, required this.product}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    if (!product.hasVideo) return SizedBox.shrink();

    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Информация о видео',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            SizedBox(height: 8),
            
            _buildInfoRow('Длительность', product.formattedDuration),
            _buildInfoRow('Размер', product.formattedSize),
            
            if (product.compressionRatio != null)
              _buildInfoRow(
                'Сжатие', 
                '${product.compressionRatio!.toStringAsFixed(1)}%'
              ),
            
            if (product.originalVideoSize != null && product.optimizedVideoSize != null)
              _buildInfoRow('Экономия', product.savedSpace),
            
            SizedBox(height: 8),
            
            Row(
              children: [
                Icon(
                  product.isOptimized ? Icons.check_circle : Icons.warning,
                  color: product.isOptimized ? Colors.green : Colors.orange,
                  size: 16,
                ),
                SizedBox(width: 4),
                Text(
                  product.isOptimized ? 'Оптимизировано' : 'Не оптимизировано',
                  style: TextStyle(
                    color: product.isOptimized ? Colors.green : Colors.orange,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 14)),
          Text(value, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
```

### Статистика видео (админ панель)

```dart
class VideoStatsWidget extends StatefulWidget {
  @override
  _VideoStatsWidgetState createState() => _VideoStatsWidgetState();
}

class _VideoStatsWidgetState extends State<VideoStatsWidget> {
  VideoStats? _stats;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadStats();
  }

  void _loadStats() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/products/video-stats'),
        headers: {'Authorization': 'Bearer $token'},
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          _stats = VideoStats.fromJson(data['data']);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Center(child: CircularProgressIndicator());
    }

    if (_stats == null) {
      return Center(child: Text('Ошибка загрузки статистики'));
    }

    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Статистика видео',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            SizedBox(height: 16),
            
            _buildStatRow('Всего видео', _stats!.totalVideos.toString()),
            _buildStatRow('Оптимизировано', '${_stats!.optimizedCount} (${_stats!.optimizationPercentage.toStringAsFixed(1)}%)'),
            _buildStatRow('С превью', _stats!.thumbnailsCount.toString()),
            _buildStatRow('Среднее сжатие', '${_stats!.averageCompression.toStringAsFixed(1)}%'),
            _buildStatRow('Сэкономлено места', '${_stats!.totalSavedMb.toStringAsFixed(1)} MB'),
            _buildStatRow('Средняя длительность', '${_stats!.averageDurationSeconds} сек'),
            
            SizedBox(height: 16),
            
            LinearProgressIndicator(
              value: _stats!.optimizationPercentage / 100,
              backgroundColor: Colors.grey[300],
              valueColor: AlwaysStoppedAnimation<Color>(Colors.green),
            ),
            
            SizedBox(height: 8),
            
            Text(
              'Прогресс оптимизации: ${_stats!.optimizationPercentage.toStringAsFixed(1)}%',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label),
          Text(value, style: TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
```

## Рекомендации для Flutter

### 1. Кеширование превью
```dart
// Используйте cached_network_image для превью
CachedNetworkImage(
  imageUrl: product.thumbnailUrl!,
  placeholder: (context, url) => CircularProgressIndicator(),
  errorWidget: (context, url, error) => Icon(Icons.error),
)
```

### 2. Прогрессивная загрузка
```dart
// Сначала показывайте превью, затем загружайте видео
if (product.hasThumbnail) {
  // Показать превью
} else {
  // Показать placeholder
}
```

### 3. Индикация качества
```dart
// Показывайте пользователю информацию об оптимизации
if (product.isOptimized) {
  Icon(Icons.hd, color: Colors.green)
} else {
  Icon(Icons.warning, color: Colors.orange)
}
```

Оптимизация видео поможет значительно сократить трафик и улучшить производительность Flutter приложения!