<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Korset - Маркетплейс Казахстана</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 700;
            color: #002f34;
            text-decoration: none;
        }
        
        .logo span {
            color: #23e5db;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #23e5db;
        }
        
        .auth-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #23e5db;
            color: #002f34;
        }
        
        .btn-primary:hover {
            background: #1dd1c8;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            border: 2px solid #002f34;
            color: #002f34;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #002f34;
            color: white;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #002f34 0%, #23e5db 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background: white;
            color: #002f34;
            font-size: 18px;
            padding: 15px 30px;
        }
        
        .btn-white:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background: white;
        }
        
        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            color: #002f34;
            margin-bottom: 60px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            text-align: center;
            padding: 40px 20px;
            border-radius: 12px;
            background: #f8f9fa;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: #23e5db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        
        .feature-card h3 {
            font-size: 24px;
            font-weight: 600;
            color: #002f34;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Categories Section */
        .categories {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .category-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .category-card h3 {
            font-size: 20px;
            font-weight: 600;
            color: #002f34;
            margin-bottom: 10px;
        }
        
        .category-card p {
            color: #666;
            font-size: 14px;
        }
        
        /* Stats Section */
        .stats {
            padding: 80px 0;
            background: #002f34;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 48px;
            font-weight: 700;
            color: #23e5db;
            margin-bottom: 10px;
        }
        
        .stat-item p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 0;
            background: linear-gradient(135deg, #23e5db 0%, #002f34 100%);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: #002f34;
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #23e5db;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: #23e5db;
        }
        
        .footer-bottom {
            border-top: 1px solid #444;
            padding-top: 30px;
            text-align: center;
            color: #ccc;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .features-grid,
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    Kor<span>set</span>
                </a>
                
                <nav class="nav-links">
                    <a href="#marketplace">Маркетплейс</a>
                    <a href="#categories">Категории</a>
                    <a href="#about">О нас</a>
                </nav>
                
                <div class="auth-buttons">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Мой аккаунт</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline">Войти</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Регистрация</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Покупай и продавай легко</h1>
            <p>Korset - это современная платформа для торговли в Казахстане. Найди то, что ищешь, или продай то, что не нужно.</p>
            
            <div class="hero-buttons">
                <a href="#marketplace" class="btn btn-white">Начать покупки</a>
                <a href="#categories" class="btn btn-white">Посмотреть категории</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="about">
        <div class="container">
            <h2 class="section-title">Почему выбирают Korset?</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🛒</div>
                    <h3>Удобный маркетплейс</h3>
                    <p>Тысячи товаров от проверенных продавцов. Легкий поиск, удобные фильтры и безопасные сделки.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Выгодные цены</h3>
                    <p>Лучшие предложения от частных лиц и магазинов. Торгуйся и находи самые выгодные сделки.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Безопасность</h3>
                    <p>Защищенные транзакции, проверка продавцов и гарантия возврата средств при проблемах.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Мобильное приложение</h3>
                    <p>Торгуй на ходу прямо с телефона. Уведомления о новых сообщениях и сделках.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h3>Быстрые сделки</h3>
                    <p>Мгновенная публикация объявлений, быстрый поиск покупателей и автоматизированные процессы.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Чат с продавцами</h3>
                    <p>Встроенная система сообщений для общения с продавцами и покупателями в реальном времени.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories" id="categories">
        <div class="container">
            <h2 class="section-title">Популярные категории</h2>
            
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">🚗</div>
                    <h3>Транспорт</h3>
                    <p>Автомобили, мотоциклы, запчасти</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">🏠</div>
                    <h3>Недвижимость</h3>
                    <p>Квартиры, дома, коммерческая недвижимость</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">📱</div>
                    <h3>Электроника</h3>
                    <p>Телефоны, компьютеры, бытовая техника</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">👕</div>
                    <h3>Одежда и обувь</h3>
                    <p>Мужская, женская, детская одежда</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">🏡</div>
                    <h3>Дом и сад</h3>
                    <p>Мебель, декор, инструменты</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">⚽</div>
                    <h3>Спорт и отдых</h3>
                    <p>Спортивные товары, туризм</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>500K+</h3>
                    <p>Активных пользователей</p>
                </div>
                
                <div class="stat-item">
                    <h3>2M+</h3>
                    <p>Объявлений размещено</p>
                </div>
                
                <div class="stat-item">
                    <h3>50K+</h3>
                    <p>Успешных сделок в месяц</p>
                </div>
                
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Поддержка пользователей</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="marketplace">
        <div class="container">
            <h2>Начни продавать уже сегодня</h2>
            <p>Размести свое объявление бесплатно и найди покупателя среди тысяч пользователей Korset</p>
            
            <div class="hero-buttons">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-white">Разместить объявление</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-white">Зарегистрироваться</a>
                @endauth
                <a href="#categories" class="btn btn-outline" style="color: white; border-color: white;">Посмотреть категории</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Korset</h3>
                    <ul>
                        <li><a href="#about">О компании</a></li>
                        <li><a href="#careers">Карьера</a></li>
                        <li><a href="#press">Пресс-центр</a></li>
                        <li><a href="#blog">Блог</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Поддержка</h3>
                    <ul>
                        <li><a href="#help">Помощь</a></li>
                        <li><a href="#safety">Безопасность</a></li>
                        <li><a href="#rules">Правила</a></li>
                        <li><a href="#contact">Контакты</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Продавцам</h3>
                    <ul>
                        <li><a href="#sell-guide">Как продавать</a></li>
                        <li><a href="#pricing">Тарифы</a></li>
                        <li><a href="#promotion">Продвижение</a></li>
                        <li><a href="#seller-tools">Инструменты</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Мобильные приложения</h3>
                    <ul>
                        <li><a href="#ios">App Store</a></li>
                        <li><a href="#android">Google Play</a></li>
                        <li><a href="#huawei">AppGallery</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Korset. Все права защищены. | Маркетплейс Казахстана</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.feature-card, .category-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>