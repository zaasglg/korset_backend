<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductParameterValueResource\Pages;
use App\Filament\Resources\ProductParameterValueResource\RelationManagers;
use App\Models\ProductParameterValue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductParameterValueResource extends Resource
{
    protected static ?string $model = ProductParameterValue::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Значения параметров';

    protected static ?string $modelLabel = 'Значение параметра';

    protected static ?string $pluralModelLabel = 'Значения параметров';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Товар')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('product_parameter_id')
                    ->label('Параметр')
                    ->relationship('parameter', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                        // Если параметр имеет тип "select", загружаем его опции
                        if (!$state) {
                            return;
                        }
                        
                        $parameter = \App\Models\ProductParameter::find($state);
                        if ($parameter && $parameter->type === 'select') {
                            $set('parameter_type', 'select');
                            $set('parameter_options', $parameter->options);
                        } else {
                            $set('parameter_type', $parameter?->type ?? null);
                            $set('parameter_options', null);
                        }
                    }),
                Forms\Components\Hidden::make('parameter_type'),
                Forms\Components\Hidden::make('parameter_options'),
                Forms\Components\TextInput::make('value')
                    ->label('Значение')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (Forms\Get $get) => $get('parameter_type') !== 'select' && $get('parameter_type') !== 'checkbox'),
                Forms\Components\Select::make('value')
                    ->label('Значение')
                    ->options(function (Forms\Get $get) {
                        $options = $get('parameter_options') ?? [];
                        $formattedOptions = [];
                        
                        foreach ($options as $option) {
                            if (isset($option['value']) && isset($option['label'])) {
                                $formattedOptions[$option['value']] = $option['label'];
                            }
                        }
                        
                        return $formattedOptions;
                    })
                    ->visible(fn (Forms\Get $get) => $get('parameter_type') === 'select'),
                Forms\Components\Checkbox::make('value')
                    ->label('Значение')
                    ->visible(fn (Forms\Get $get) => $get('parameter_type') === 'checkbox')
                    ->afterStateHydrated(function ($state, Forms\Set $set) {
                        $set('value', (bool) $state);
                    })
                    ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parameter.name')
                    ->label('Параметр')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parameter.category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Значение')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Товар')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('product_parameter_id')
                    ->label('Параметр')
                    ->relationship('parameter', 'name')
                    ->searchable()
                    ->preload(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductParameterValues::route('/'),
            'create' => Pages\CreateProductParameterValue::route('/create'),
            'edit' => Pages\EditProductParameterValue::route('/{record}/edit'),
        ];
    }
}
