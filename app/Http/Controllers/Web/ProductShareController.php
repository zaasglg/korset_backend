<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductShareController extends Controller
{
    /**
     * Show product share page
     */
    public function show(Request $request, $identifier = null): View
    {
        $product = null;
        $error = null;
        $debug = [];

        try {
            // Добавляем отладочную информацию
            $debug['identifier'] = $identifier;
            $debug['request_id'] = $request->get('id');
            $debug['request_slug'] = $request->get('slug');

            if ($identifier) {
                // Сначала ищем без ограничений для отладки
                $allProducts = Product::where('slug', $identifier)
                    ->orWhere('id', $identifier)
                    ->get(['id', 'name', 'slug', 'status', 'expires_at']);
                
                $debug['found_products'] = $allProducts->toArray();

                // Теперь ищем с ограничениями (в debug режиме - без ограничений)
                if (config('app.debug')) {
                    // В режиме разработки показываем любые товары
                    $product = Product::where(function($query) use ($identifier) {
                            $query->where('slug', $identifier)
                                  ->orWhere('id', $identifier);
                        })
                        ->with(['category', 'city', 'user:id,name,avatar'])
                        ->first();
                } else {
                    // В продакшене только активные и не просроченные
                    $product = Product::where(function($query) use ($identifier) {
                            $query->where('slug', $identifier)
                                  ->orWhere('id', $identifier);
                        })
                        ->where('status', 'active')
                        ->where(function($query) {
                            $query->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', now());
                        })
                        ->with(['category', 'city', 'user:id,name,avatar'])
                        ->first();
                }

            } elseif ($request->has('id')) {
                $productId = $request->get('id');
                
                // Отладочная информация
                $allProducts = Product::where('id', $productId)->get(['id', 'name', 'slug', 'status', 'expires_at']);
                $debug['found_products'] = $allProducts->toArray();

                // Поиск по ID из параметра запроса
                if (config('app.debug')) {
                    $product = Product::where('id', $productId)
                        ->with(['category', 'city', 'user:id,name,avatar'])
                        ->first();
                } else {
                    $product = Product::where('id', $productId)
                        ->where('status', 'active')
                        ->where(function($query) {
                            $query->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', now());
                        })
                        ->with(['category', 'city', 'user:id,name,avatar'])
                        ->first();
                }

            } elseif ($request->has('slug')) {
                $productSlug = $request->get('slug');
                
                // Отладочная информация
                $allProducts = Product::where('slug', $productSlug)->get(['id', 'name', 'slug', 'status', 'expires_at']);
                $debug['found_products'] = $allProducts->toArray();

                // Поиск по slug из параметра запроса
                if (config('app.debug')) {
                    $product = Product::where('slug', $productSlug)
                        ->with(['category', 'city', 'user:id,name,avatar'])
                        ->first();
                } else {
                    $product = Product::where('slug', $productSlug)
                        ->where('status', 'active')
                        ->where(function($query) {
                            $query->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', now());
                        })
                        ->with(['category', 'city', 'user:id,name,avatar'])
                        ->first();
                }
            } else {
                $error = 'Не указан ID или slug товара';
            }

            if (!$product && empty($error)) {
                // Проверяем, есть ли товар вообще
                if (!empty($debug['found_products'])) {
                    $foundProduct = $debug['found_products'][0];
                    if ($foundProduct['status'] !== 'active') {
                        $error = "Объявление неактивно (статус: {$foundProduct['status']})";
                    } elseif ($foundProduct['expires_at'] && $foundProduct['expires_at'] < now()) {
                        $error = "Объявление просрочено (истекло: {$foundProduct['expires_at']})";
                    } else {
                        $error = 'Объявление найдено, но не прошло проверки';
                    }
                } else {
                    $error = 'Объявление не найдено в базе данных';
                }
            }

        } catch (\Exception $e) {
            $error = 'Ошибка загрузки объявления: ' . $e->getMessage();
            $debug['exception'] = $e->getMessage();
        }

        // В режиме разработки показываем отладочную информацию
        if (config('app.debug') && !$product) {
            $debug['current_time'] = now()->toDateTimeString();
            $debug['total_products'] = Product::count();
            $debug['active_products'] = Product::where('status', 'active')->count();
        }

        return view('share.product', compact('product', 'error', 'debug'));
    }

    /**
     * Increment share count via web request
     */
    public function incrementShare(Product $product)
    {
        $product->increment('shares_count');

        return response()->json([
            'status' => 'success',
            'shares_count' => $product->shares_count
        ]);
    }
}