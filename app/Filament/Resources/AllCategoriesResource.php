<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllCategoriesResource\Pages;
use App\Models\Category;
use App\Rules\MaxCategoryLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AllCategoriesResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Все категории';

    protected static ?string $modelLabel = 'Категория';

    protected static ?string $pluralModelLabel = 'Все категории';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('photo')
                    ->label('Фото')
                    ->image()
                    ->directory('categories')
                    ->columnSpanFull(),
                Forms\Components\Select::make('parent_id')
                    ->label('Родительская категория')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Выберите родительскую категорию (необязательно)')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $level = $record->getLevel();
                        $prefix = str_repeat('— ', $level - 1);
                        return $prefix . $record->name . " (Уровень {$level})";
                    })
                    ->helperText('Максимум 3 уровня вложенности. Оставьте пустым для создания основной категории.')
                    ->rules([new MaxCategoryLevel(3)]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $level = $record->getLevel();
                        $prefix = str_repeat('　　', $level - 1); // Japanese space for indentation
                        $levelIcon = match($level) {
                            1 => '📁',
                            2 => '📂', 
                            3 => '📄',
                            default => '❓'
                        };
                        return $prefix . $levelIcon . ' ' . $state;
                    }),
                Tables\Columns\TextColumn::make('level')
                    ->label('Уровень')
                    ->getStateUsing(fn ($record) => $record->getLevel())
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        1 => 'success',
                        2 => 'info', 
                        3 => 'warning',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('hierarchy_path')
                    ->label('Путь')
                    ->getStateUsing(function ($record) {
                        $path = [];
                        $current = $record;
                        
                        while ($current) {
                            array_unshift($path, $current->name);
                            $current = $current->parent;
                        }
                        
                        return implode(' → ', $path);
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('Подкатегорий')
                    ->counts('children')
                    ->badge()
                    ->color('success'),
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Фото')
                    ->size(40),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Товаров')
                    ->counts('products')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Уровень категории')
                    ->options([
                        1 => 'Основные категории',
                        2 => 'Подкатегории',
                        3 => 'Подподкатегории',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return $query;
                        
                        return match($data['value']) {
                            1 => $query->whereNull('parent_id'),
                            2 => $query->whereHas('parent', fn($q) => $q->whereNull('parent_id')),
                            3 => $query->whereHas('parent.parent', fn($q) => $q->whereNull('parent_id')),
                            default => $query
                        };
                    }),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Родительская категория')
                    ->relationship('parent', 'name')
                    ->placeholder('Все категории'),
                Tables\Filters\Filter::make('has_children')
                    ->label('С подкатегориями')
                    ->query(fn (Builder $query): Builder => $query->has('children')),
                Tables\Filters\Filter::make('main_categories')
                    ->label('Только основные категории')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListAllCategories::route('/'),
            'create' => Pages\CreateAllCategories::route('/create'),
            'edit' => Pages\EditAllCategories::route('/{record}/edit'),
        ];
    }
}
