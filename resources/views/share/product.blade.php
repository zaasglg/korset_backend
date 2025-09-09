<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if($product)
        <title>{{ $product->name }} - Поделиться объявлением</title>

        <!-- Open Graph Meta Tags -->
        <meta property="og:title" content="{{ $product->name }}">
        <meta property="og:description" content="{{ Str::limit($product->description, 150) }}">
        <meta property="og:price:amount" content="{{ $product->price }}">
        <meta property="og:price:currency" content="KZT">
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ url()->current() }}">

        @if($product->video)
            <meta property="og:video" content="{{ asset('storage/' . $product->video) }}">
            <meta property="og:video:type" content="video/mp4">
            <meta property="og:video:width" content="400">
            <meta property="og:video:height" content="300">
            @if($product->video_thumbnail)
                <meta property="og:image" content="{{ asset('storage/' . $product->video_thumbnail) }}">
            @endif
        @elseif($product->main_photo)
            <meta property="og:image" content="{{ asset('storage/' . $product->main_photo) }}">
        @endif

        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="player">
        <meta name="twitter:title" content="{{ $product->name }}">
        <meta name="twitter:description" content="{{ Str::limit($product->description, 150) }}">

        @if($product->video)
            <meta name="twitter:player" content="{{ asset('storage/' . $product->video) }}">
            <meta name="twitter:player:width" content="400">
            <meta name="twitter:player:height" content="300">
            @if($product->video_thumbnail)
                <meta name="twitter:image" content="{{ asset('storage/' . $product->video_thumbnail) }}">
            @endif
        @elseif($product->main_photo)
            <meta name="twitter:image" content="{{ asset('storage/' . $product->main_photo) }}">
        @endif
    @else
        <title>Поделиться объявлением</title>
    @endif

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'korset': {
                            50: '#f0fdfc',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        'dark': {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .share-btn svg {
            width: 20px;
            height: 20px;
        }

        .whatsapp svg {
            color: #25D366;
        }

        .telegram svg {
            color: #0088cc;
        }

        .facebook svg {
            color: #1877f2;
        }

        .copy-link svg {
            color: #64748b;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">
    <!-- Korset Header -->
    <header class="bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="#"
                        class="text-2xl uppercase font-bold text-blue-800">
                        Korset
                    </a>
                </div>
            </div>
        </div>
    </header>

    @if($error)
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-2xl p-12 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-slate-900 mb-2">Объявление не найдено</h2>
                <p class="text-slate-600 mb-4">{{ $error }}</p>
                <p class="text-sm text-slate-500">Проверьте правильность ссылки</p>

                @if(config('app.debug') && isset($debug))
                    <div class="mt-6 p-4 bg-slate-50 rounded-xl text-left">
                        <strong class="text-slate-700">Отладочная информация:</strong>
                        <pre
                            class="mt-2 text-xs text-slate-600 whitespace-pre-wrap font-mono">{{ json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @elseif($product)
        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <main class="lg:col-span-2 space-y-6">
                    <!-- Product Header -->
                    <div class="bg-white rounded-2xl border p-8">
                        <h1 class="text-3xl lg:text-4xl font-light text-slate-900 mb-4 leading-tight">{{ $product->name }}
                        </h1>
                        <div class="text-3xl lg:text-4xl font-bold text-slate-900 mb-6">
                            {{ number_format($product->price, 0, ' ', ' ') }} ₸
                        </div>

                        @if($product->city)
                            <div class="flex items-center text-slate-500 mb-4">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                </svg>
                                {{ $product->city->name }}
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-4 text-xs text-slate-400">
                            <span class="bg-slate-100 px-3 py-1 rounded-full">ID: {{ $product->id }}</span>
                            <span
                                class="bg-slate-100 px-3 py-1 rounded-full">{{ $product->created_at->format('d.m.Y') }}</span>
                            @if($product->category)
                                <span class="bg-slate-100 px-3 py-1 rounded-full">{{ $product->category->name }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Product Gallery -->
                    <div class="bg-white rounded-2xl border p-8">
                        @if($product->video)
                            <div class="relative inline-block w-full max-w-md mx-auto">
                                <video class="w-full h-72 object-cover rounded-xl bg-black shadow-lg" controls
                                    preload="metadata"
                                    poster="{{ $product->video_thumbnail ? asset('storage/' . $product->video_thumbnail) : '' }}">
                                    <source src="{{ asset('storage/' . $product->video) }}" type="video/mp4">
                                    <source src="{{ asset('storage/' . $product->video) }}" type="video/webm">
                                    <source src="{{ asset('storage/' . $product->video) }}" type="video/ogg">
                                    Ваш браузер не поддерживает воспроизведение видео.
                                </video>
                                @if($product->video_duration)
                                    <div
                                        class="absolute bottom-2 right-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs">
                                        {{ gmdate('i:s', $product->video_duration) }}
                                    </div>
                                @endif
                            </div>
                            @if($product->video_duration || $product->optimized_video_size)
                                <div class="mt-3 text-center text-xs text-slate-500">
                                    @if($product->video_duration)
                                        Длительность: {{ gmdate('i:s', $product->video_duration) }}
                                    @endif
                                    @if($product->optimized_video_size)
                                        @if($product->video_duration) • @endif
                                        Размер: {{ number_format($product->optimized_video_size / 1024 / 1024, 1) }} МБ
                                    @endif
                                </div>
                            @endif
                        @elseif($product->main_photo)
                            <img class="w-full max-w-md h-72 object-cover rounded-xl mx-auto border"
                                src="{{ asset('storage/' . $product->main_photo) }}" alt="{{ $product->name }}">
                        @else
                            <div
                                class="w-full max-w-md h-72 bg-slate-100 rounded-xl mx-auto flex items-center justify-center border-2 border-dashed border-slate-300">
                                <div class="text-center text-slate-400">
                                    <div class="text-4xl mb-2">🎥</div>
                                    <div class="text-sm">Видео не загружено</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Product Description -->
                    @if($product->description)
                        <div class="bg-white rounded-2xl border p-8">
                            <h2 class="text-2xl font-light text-slate-900 mb-4">Описание</h2>
                            <div class="text-slate-700 leading-relaxed">{{ $product->description }}</div>
                        </div>
                    @endif

                    <!-- Product Parameters -->
                    @if($product->parameterValues && $product->parameterValues->count() > 0)
                        <div class="bg-white rounded-2xl border p-8">
                            <h2 class="text-2xl font-light text-slate-900 mb-6">Характеристики</h2>
                            <div class="space-y-4">
                                @foreach($product->parameterValues as $paramValue)
                                    @if($paramValue->parameter)
                                        <div class="flex justify-between items-center py-3 border-b border-slate-100 last:border-b-0">
                                            <span class="text-slate-500">{{ $paramValue->parameter->name }}</span>
                                            <span class="text-slate-900 font-medium text-right">{{ $paramValue->value }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </main>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    @if($product->user)
                        <!-- Seller Card -->
                        <div class="bg-white rounded-2xl border p-6">
                            <div class="flex items-center space-x-4 mb-6">
                                @if($product->user->avatar)
                                    <img class="w-12 h-12 rounded-full object-cover"
                                        src="{{ asset('storage/' . $product->user->avatar) }}" alt="{{ $product->user->name }}">
                                @else
                                    <div
                                        class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center text-slate-500 text-xl">
                                        👤
                                    </div>
                                @endif

                                <div>
                                    <h3 class="font-medium text-slate-900">{{ $product->user->name }}</h3>
                                    <p class="text-sm text-slate-500">Частное лицо</p>
                                </div>
                            </div>

                            @if($product->whatsapp_number || $product->phone_number)
                                <div class="space-y-3">
                                    @if($product->whatsapp_number)
                                        <a href="{{ $product->whatsapp_link }}"
                                            class="block w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white text-center rounded-xl font-medium transition-colors duration-200"
                                            target="_blank">
                                            Написать в WhatsApp
                                        </a>
                                    @endif

                                    @if($product->phone_number)
                                        <a href="tel:{{ $product->phone_number }}"
                                            class="block w-full py-3 px-4 border-2 border-slate-200 hover:border-korset-400 hover:bg-korset-50 text-slate-700 hover:text-korset-600 text-center rounded-xl font-medium transition-all duration-200">
                                            Показать телефон
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Product Details Card -->
                    @if($product->address || $product->is_video_call_available || $product->ready_for_video_demo)
                        <div class="bg-white rounded-2xl border p-6">
                            <h3 class="text-lg font-medium text-slate-900 mb-4">Дополнительно</h3>
                            <div class="space-y-3">
                                @if($product->address)
                                    <div class="flex justify-between items-start">
                                        <span class="text-slate-500 text-sm">Адрес</span>
                                        <span
                                            class="text-slate-900 text-sm font-medium text-right max-w-[60%]">{{ $product->address }}</span>
                                    </div>
                                @endif

                                @if($product->is_video_call_available)
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500 text-sm">Видеозвонок</span>
                                        <span class="text-green-600 text-sm font-medium">✓ Доступен</span>
                                    </div>
                                @endif

                                @if($product->ready_for_video_demo)
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500 text-sm">Видеодемонстрация</span>
                                        <span class="text-green-600 text-sm font-medium">✓ Готов показать</span>
                                    </div>
                                @endif

                                @if($product->expires_at)
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500 text-sm">Действует до</span>
                                        <span
                                            class="text-slate-900 text-sm font-medium">{{ $product->expires_at->format('d.m.Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Stats Card -->
                    <div class="bg-white rounded-2xl border p-6">
                        <h3 class="text-lg font-medium text-slate-900 mb-4">Статистика</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-slate-50 rounded-xl">
                                <div id="views-count" class="text-2xl font-bold text-slate-900 mb-1">
                                    {{ number_format($product->views_count ?? 0) }}
                                </div>
                                <div class="text-xs text-slate-500 uppercase tracking-wide">Просмотры</div>
                            </div>
                            <div class="text-center p-4 bg-slate-50 rounded-xl">
                                <div id="shares-count" class="text-2xl font-bold text-slate-900 mb-1">
                                    {{ number_format($product->shares_count ?? 0) }}
                                </div>
                                <div class="text-xs text-slate-500 uppercase tracking-wide">Поделились</div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    @else
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-slate-900 mb-2">Объявление не найдено</h2>
                <p class="text-slate-600">Проверьте правильность ссылки</p>
            </div>
        </div>
    @endif

    @if($product)
        <script>
            // Копирование в буфер обмена
            async function copyToClipboard() {
                const shareLink = document.getElementById('share-link');
                const copyBtn = document.getElementById('copy-link-btn');

                try {
                    await navigator.clipboard.writeText(shareLink.value);

                    // Визуальная обратная связь
                    const originalText = copyBtn.textContent;
                    copyBtn.textContent = 'Скопировано!';
                    copyBtn.classList.add('bg-green-500', 'hover:bg-green-600');
                    copyBtn.classList.remove('from-korset-500', 'to-korset-600', 'hover:from-korset-600', 'hover:to-korset-700');

                    setTimeout(() => {
                        copyBtn.textContent = originalText;
                        copyBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
                        copyBtn.classList.add('from-korset-500', 'to-korset-600', 'hover:from-korset-600', 'hover:to-korset-700');
                    }, 2000);

                    // Увеличиваем счетчик
                    incrementShare();
                } catch (err) {
                    console.error('Failed to copy:', err);

                    // Fallback для старых браузеров
                    shareLink.select();
                    shareLink.setSelectionRange(0, 99999);
                    document.execCommand('copy');

                    const originalText = copyBtn.textContent;
                    copyBtn.textContent = 'Скопировано!';
                    copyBtn.classList.add('bg-green-500', 'hover:bg-green-600');
                    copyBtn.classList.remove('from-korset-500', 'to-korset-600', 'hover:from-korset-600', 'hover:to-korset-700');

                    setTimeout(() => {
                        copyBtn.textContent = originalText;
                        copyBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
                        copyBtn.classList.add('from-korset-500', 'to-korset-600', 'hover:from-korset-600', 'hover:to-korset-700');
                    }, 2000);

                    incrementShare();
                }
            }

            // Увеличение счетчика поделиться
            async function incrementShare() {
                try {
                    const response = await fetch('{{ route("share.increment", $product->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    if (response.ok) {
                        const result = await response.json();
                        if (result.shares_count) {
                            document.getElementById('shares-count').textContent = result.shares_count.toLocaleString();
                        }
                    }
                } catch (err) {
                    console.error('Failed to increment share count:', err);
                }
            }

            // Увеличиваем просмотры при загрузке страницы
            fetch('{{ route("api.products.increment-views", $product->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            }).then(response => {
                if (response.ok) {
                    return response.json();
                }
            }).then(result => {
                if (result && result.data && result.data.views_count) {
                    document.getElementById('views-count').textContent = result.data.views_count.toLocaleString();
                }
            }).catch(err => {
                console.error('Failed to increment views:', err);
            });
        </script>
    @endif
</body>

</html>