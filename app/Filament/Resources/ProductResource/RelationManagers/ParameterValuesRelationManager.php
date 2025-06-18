<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductParameter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParameterValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'parameterValues';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Значения параметров';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_parameter_id')
                    ->label('Параметр')
                    ->relationship('parameter', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                        if (!$state) {
                            return;
                        }
                        
                        $parameter = ProductParameter::find($state);
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parameter.name')
                    ->label('Параметр')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parameter.category.name')
                    ->label('Категория параметра')
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
                Tables\Filters\SelectFilter::make('product_parameter_id')
                    ->label('Параметр')
                    ->relationship('parameter', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
