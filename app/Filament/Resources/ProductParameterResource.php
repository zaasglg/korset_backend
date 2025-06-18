<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductParameterResource\Pages;
use App\Filament\Resources\ProductParameterResource\RelationManagers;
use App\Models\ProductParameter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductParameterResource extends Resource
{
    protected static ?string $model = ProductParameter::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Параметры товаров';

    protected static ?string $modelLabel = 'Параметр товара';

    protected static ?string $pluralModelLabel = 'Параметры товаров';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Тип')
                    ->options([
                        'text' => 'Текст',
                        'number' => 'Число',
                        'select' => 'Выбор из списка',
                        'checkbox' => 'Флажок',
                    ])
                    ->required(),
                Forms\Components\Section::make('Варианты для выбора')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->label('Варианты для выбора')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Отображаемый текст')
                                    ->required()
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                        $set('value', \Illuminate\Support\Str::slug($state));
                                    }),
                                Forms\Components\TextInput::make('value')
                                    ->label('Значение')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Автоматически формируется из отображаемого текста'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Добавить вариант')
                            ->reorderable()
                            ->defaultItems(0)
                    ])
                    ->visible(fn (Forms\Get $get) => $get('type') === 'select'),
                Forms\Components\Toggle::make('is_required')
                    ->label('Обязательный параметр')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Текст',
                        'number' => 'Число',
                        'select' => 'Выбор из списка',
                        'checkbox' => 'Флажок',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Обязательный')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'text' => 'Текст',
                        'number' => 'Число',
                        'select' => 'Выбор из списка',
                        'checkbox' => 'Флажок',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductParameters::route('/'),
            'create' => Pages\CreateProductParameter::route('/create'),
            'edit' => Pages\EditProductParameter::route('/{record}/edit'),
        ];
    }
}
