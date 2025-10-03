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

    <!-- App Store Links -->
    <div class="bg-slate-50 py-8">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Скачайте приложение Korset</h3>
                <p class="text-slate-600 mb-6">Покупайте и продавайте товары с удобством мобильного приложения</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <!-- Google Play Store -->
                    <a href="https://play.google.com/store/apps/details?id=com.korset.app&hl=ru" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="inline-flex items-center bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition-colors duration-200 shadow-lg hover:shadow-xl w-full sm:w-auto min-w-[200px]">
                        <svg class="w-6 h-6 mr-3" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="_x36_" viewBox="0 0 512 512" xml:space="preserve">
                            <g>
                                <g>
                                    <path style="fill:#3A9BC8;" d="M225.656,256.052L14.016,485.451l-6.442,7.052c-4.005-5.919-6.704-12.972-7.313-20.806    C0.087,470.305,0,468.91,0,467.518V44.499c0-9.488,2.873-18.02,7.574-24.987L225.656,256.052z"/>
                                    <path style="fill:#9BCD83;" d="M320.811,152.8l-95.155,103.253L7.574,19.512C19.936,1.405,45.183-6.342,66.6,6.02L320.811,152.8z"/>
                                    <path style="fill:#EEB84C;" d="M455.056,257.27c-0.348,14.453-7.748,28.904-22.113,37.174l-112.132,64.771l-95.155-103.163    L320.811,152.8l70.518,40.745l41.614,24.026C448.178,226.366,455.579,241.861,455.056,257.27z"/>
                                    <path style="fill:#B43F70;" d="M7.591,492.492c12.368,18.116,37.599,25.838,58.976,13.496L320.775,359.22l-95.156-103.209    L7.591,492.492z"/>
                                </g>
                                <path style="opacity:0.2;fill:#FFFFFF;" d="M454.067,246.447c-2.453-11.518-9.483-22.156-21.124-28.876l-41.614-24.026   L320.811,152.8L66.6,6.02C45.183-6.342,19.936,1.405,7.574,19.512C2.873,26.479,0,35.011,0,44.499v243.72   c36.681,3.114,74.226,4.746,112.451,4.746c47.984,0,94.85-2.644,140.318-7.505l-27.027-29.314l27.038,29.314   C323.988,277.849,391.567,264.55,454.067,246.447z"/>
                            </g>
                        </svg>
                        <div class="text-left">
                            <div class="text-xs text-gray-300">Скачать в</div>
                            <div class="text-sm font-semibold">Google Play</div>
                        </div>
                    </a>

                    <!-- Apple App Store -->
                    <a href="https://apps.apple.com/app/korset/id123456789" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="inline-flex items-center bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition-colors duration-200 shadow-lg hover:shadow-xl w-full sm:w-auto min-w-[200px]">
                        <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71,19.5C17.88,20.74 17,21.95 15.66,21.97C14.32,22 13.89,21.18 12.37,21.18C10.84,21.18 10.37,21.95 9.1,22C7.79,22.05 6.8,20.68 5.96,19.47C4.25,17 2.94,12.45 4.7,9.39C5.57,7.87 7.13,6.91 8.82,6.88C10.1,6.86 11.32,7.75 12.11,7.75C12.89,7.75 14.37,6.68 15.92,6.84C16.57,6.87 18.39,7.1 19.56,8.82C19.47,8.88 17.39,10.1 17.41,12.63C17.44,15.65 20.06,16.66 20.09,16.67C20.06,16.74 19.67,18.11 18.71,19.5M13,3.5C13.73,2.67 14.94,2.04 15.94,2C16.07,3.17 15.6,4.35 14.9,5.19C14.21,6.04 13.07,6.7 11.95,6.61C11.8,5.46 12.36,4.26 13,3.5Z"/>
                        </svg>
                        <div class="text-left">
                            <div class="text-xs text-gray-300">Скачать в</div>
                            <div class="text-sm font-semibold">App Store</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

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