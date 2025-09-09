<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Демо - Поделиться объявлением</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .demo-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .demo-links {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .demo-link {
            display: block;
            padding: 15px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .demo-link:hover {
            background: #5a6fd8;
        }

        .demo-link strong {
            display: block;
            margin-bottom: 5px;
        }

        .demo-link small {
            opacity: 0.9;
        }

        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>

<body>
    <div class="demo-container">
        <h1>🚀 Демо - Поделиться объявлением</h1>

        <p>Выберите способ тестирования страницы поделиться:</p>

        <div class="demo-links">
            <a href="{{ route('share.product', ['identifier' => 1]) }}" class="demo-link">
                <strong>По ID товара</strong>
                <small>{{ route('share.product', ['identifier' => 1]) }}</small>
            </a>

            <a href="{{ route('share.product.query') }}?id=1" class="demo-link">
                <strong>По ID через параметр</strong>
                <small>{{ route('share.product.query') }}?id=1</small>
            </a>

            <a href="{{ route('share.product.query') }}?slug=test-product" class="demo-link">
                <strong>По slug через параметр</strong>
                <small>{{ route('share.product.query') }}?slug=test-product</small>
            </a>

            <a href="{{ route('share.product', ['identifier' => 'test-product']) }}" class="demo-link">
                <strong>По slug в URL</strong>
                <small>{{ route('share.product', ['identifier' => 'test-product']) }}</small>
            </a>
        </div>

        <div class="info">
            <strong>📝 Примечание:</strong>
            <p>Для тестирования убедитесь, что в базе данных есть товары с соответствующими ID или slug. Товары должны
                иметь статус 'active' и не быть просроченными.</p>

            @if(config('app.debug'))
                <div style="margin-top: 15px;">
                    <button onclick="createTestProduct()"
                        style="padding: 10px 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                        🛠️ Создать тестовый товар
                    </button>
                    <button onclick="checkProducts()"
                        style="padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        📋 Проверить товары в БД
                    </button>
                    <div id="test-result"
                        style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;">
                    </div>
                </div>
            @endif
        </div>

        @if(config('app.debug'))
            <script>
                async function createTestProduct() {
                    const button = event.target;
                    const resultDiv = document.getElementById('test-result');

                    button.disabled = true;
                    button.textContent = 'Создание...';

                    try {
                        const response = await fetch('{{ route("demo.create-test-product") }}');
                        const result = await response.json();

                        if (result.success) {
                            resultDiv.style.display = 'block';
                            resultDiv.innerHTML = `
                                                <strong>✅ Товар создан успешно!</strong><br>
                                                <small>ID: ${result.product.id}, Slug: ${result.product.slug}</small><br><br>
                                                <strong>Ссылки для тестирования:</strong><br>
                                                <a href="${result.share_links.by_id}" target="_blank">По ID</a> | 
                                                <a href="${result.share_links.by_slug}" target="_blank">По Slug</a>
                                            `;
                        } else {
                            throw new Error(result.message || 'Ошибка создания товара');
                        }
                    } catch (error) {
                        resultDiv.style.display = 'block';
                        resultDiv.innerHTML = `<strong>❌ Ошибка:</strong> ${error.message}`;
                    } finally {
                        button.disabled = false;
                        button.textContent = '🛠️ Создать тестовый товар';
                    }
                }

                async function checkProducts() {
                    const button = event.target;
                    const resultDiv = document.getElementById('test-result');

                    button.disabled = true;
                    button.textContent = 'Проверка...';

                    try {
                        const response = await fetch('{{ route("demo.check-products") }}');
                        const result = await response.json();

                        resultDiv.style.display = 'block';
                        let html = `
                                            <strong>📊 Статистика товаров:</strong><br>
                                            Всего товаров: ${result.total_products}<br>
                                            Активных товаров: ${result.active_products}<br><br>
                                            <strong>Последние товары:</strong><br>
                                        `;

                        if (result.recent_products.length > 0) {
                            result.recent_products.forEach(product => {
                                html += `
                                                    <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 5px;">
                                                        <strong>ID: ${product.id}</strong> - ${product.name}<br>
                                                        <small>Slug: ${product.slug || 'нет'} | Статус: ${product.status}</small><br>
                                                        <a href="${product.share_links.by_id}" target="_blank">Открыть по ID</a>
                                                        ${product.share_links.by_slug ? ` | <a href="${product.share_links.by_slug}" target="_blank">Открыть по Slug</a>` : ''}
                                                    </div>
                                                `;
                            });
                        } else {
                            html += '<p>Товары не найдены</p>';
                        }

                        resultDiv.innerHTML = html;
                    } catch (error) {
                        resultDiv.style.display = 'block';
                        resultDiv.innerHTML = `<strong>❌ Ошибка:</strong> ${error.message}`;
                    } finally {
                        button.disabled = false;
                        button.textContent = '📋 Проверить товары в БД';
                    }
                }
            </script>
        @endif
    </div>
</body>

</html>