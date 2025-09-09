<?php

namespace App\Filament\Widgets;

use App\Models\PublicationPrice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PublicationPricesStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPrices = PublicationPrice::count();
        $activePrices = PublicationPrice::active()->count();
        $storyPrices = PublicationPrice::stories()->active()->count();
        $announcementPrices = PublicationPrice::announcements()->active()->count();
        
        $avgStoryPrice = PublicationPrice::stories()->active()->avg('price') ?? 0;
        $avgAnnouncementPrice = PublicationPrice::announcements()->active()->avg('price') ?? 0;

        return [
            Stat::make('Всего тарифов', $totalPrices)
                ->description('Общее количество тарифов')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
                
            Stat::make('Активных тарифов', $activePrices)
                ->description('Доступны для покупки')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Тарифы сторис', $storyPrices)
                ->description('Средняя цена: ' . number_format($avgStoryPrice, 0) . ' KZT')
                ->descriptionIcon('heroicon-m-photo')
                ->color('primary'),
                
            Stat::make('Тарифы объявлений', $announcementPrices)
                ->description('Средняя цена: ' . number_format($avgAnnouncementPrice, 0) . ' KZT')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('warning'),
        ];
    }
}
