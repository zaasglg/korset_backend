# API для поделиться объявлением

## Описание
API позволяет пользователям поделиться объявлением и получить данные для шаринга в социальных сетях или мессенджерах.

## Endpoints

### 1. Поделиться объявлением (авторизованные пользователи)
**POST** `/api/products/{product}/share`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Ответ:**
```json
{
    "status": "success",
    "message": "Share data generated successfully",
    "data": {
        "url": "https://yourapp.com/products/product-slug",
        "title": "Название объявления",
        "description": "Описание объявления (до 150 символов)...",
        "image": "https://yourapi.com/storage/products/image.jpg",
        "price": "1000.00",
        "location": "Город",
        "shares_count": 5
    }
}
```

### 2. Поделиться объявлением (публичный доступ)
**POST** `/api/public/products/{product}/share`

**Headers:**
- `Content-Type: application/json`

**Ответ:** Аналогичен авторизованному endpoint

### 3. Получить статистику поделившихся
**GET** `/api/products/{product}/share-stats`

**Headers:**
- `Authorization: Bearer {token}`

**Ответ:**
```json
{
    "status": "success",
    "data": {
        "product_id": 1,
        "shares_count": 5,
        "views_count": 150,
        "created_at": "2025-08-09T07:00:00.000000Z"
    }
}
```

### 4. Получить статистику поделившихся (публичный доступ)
**GET** `/api/public/products/{product}/share-stats`

**Ответ:** Аналогичен авторизованному endpoint

## Использование во Flutter

### Пример кода для поделиться объявлением:

```dart
import 'package:share_plus/share_plus.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ShareService {
  static const String baseUrl = 'https://yourapi.com/api';
  
  Future<Map<String, dynamic>?> shareProduct(int productId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/public/products/$productId/share'),
        headers: {
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error sharing product: $e');
      return null;
    }
  }
  
  Future<void> shareProductToSocial(int productId) async {
    final shareData = await shareProduct(productId);
    
    if (shareData != null) {
      final shareText = '''
${shareData['title']}

${shareData['description']}

💰 Цена: ${shareData['price']} ₽
📍 ${shareData['location']}

Посмотреть: ${shareData['url']}
      ''';
      
      await Share.share(
        shareText,
        subject: shareData['title'],
      );
    }
  }
}
```

### Пример использования в виджете:

```dart
class ProductCard extends StatelessWidget {
  final Product product;
  
  const ProductCard({Key? key, required this.product}) : super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Column(
        children: [
          // ... другие элементы карточки
          
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              IconButton(
                icon: Icon(Icons.share),
                onPressed: () async {
                  await ShareService().shareProductToSocial(product.id);
                },
              ),
              // ... другие кнопки
            ],
          ),
        ],
      ),
    );
  }
}
```

## Настройка

1. Добавьте в `.env` файл:
```
FRONTEND_URL=https://yourapp.com
```

2. Убедитесь, что миграция выполнена:
```bash
php artisan migrate
```

## Возможные ошибки

- **404**: Объявление не найдено
- **422**: Ошибка валидации данных
- **500**: Внутренняя ошибка сервера

## Дополнительные возможности

- Счетчик поделившихся автоматически увеличивается при каждом вызове
- Поддержка как авторизованных, так и неавторизованных пользователей
- Генерация SEO-friendly ссылок с slug
- Автоматическое обрезание описания до 150 символов