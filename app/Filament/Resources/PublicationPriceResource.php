<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublicationPriceResource\Pages;
use App\Models\PublicationPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicationPriceResource extends Resource
{
    protected static ?string $model = PublicationPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Публикации';

    protected static ?string $navigationLabel = 'Цены на публикации';

    protected static ?string $modelLabel = 'Цена публикации';

    protected static ?string $pluralModelLabel = 'Цены публикаций';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Тип публикации')
                            ->options(PublicationPrice::getTypes())
                            ->required()
                            ->native(false),
                            
                        Forms\Components\TextInput::make('name')
                            ->label('Название тарифа')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Базовый тариф'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->placeholder('Описание возможностей тарифа'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->helperText('Неактивные тарифы не отображаются пользователям'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Стоимость и длительность')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Цена (KZT)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->prefix('₸')
                            ->placeholder('0.00'),
                            
                        Forms\Components\TextInput::make('duration_hours')
                            ->label('Длительность (часы)')
                            ->numeric()
                            ->minValue(1)
                            ->default(24)
                            ->required()
                            ->helperText('Сколько часов будет активна публикация'),
                            
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0)
                            ->helperText('Чем меньше число, тем выше в списке'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Дополнительные возможности')
                    ->schema([
                        Forms\Components\KeyValue::make('features')
                            ->label('Особенности тарифа')
                            ->keyLabel('Название')
                            ->valueLabel('Описание')
                            ->helperText('Дополнительные возможности, которые включает тариф'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PublicationPrice::getTypes()[$state] ?? $state)
                    ->colors([
                        'primary' => PublicationPrice::TYPE_STORY,
                        'success' => PublicationPrice::TYPE_ANNOUNCEMENT,
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('KZT')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('duration_hours')
                    ->label('Длительность')
                    ->formatStateUsing(function ($state) {
                        if ($state < 24) {
                            return $state . ' ч.';
                        }
                        return ($state / 24) . ' дн.';
                    })
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип публикации')
                    ->options(PublicationPrice::getTypes()),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (PublicationPrice $record) => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (PublicationPrice $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (PublicationPrice $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (PublicationPrice $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicationPrices::route('/'),
            'create' => Pages\CreatePublicationPrice::route('/create'),
            'edit' => Pages\EditPublicationPrice::route('/{record}/edit'),
        ];
    }
}
