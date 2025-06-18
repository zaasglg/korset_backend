<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Подкатегории';

    protected static ?string $modelLabel = 'Подкатегория';

    protected static ?string $pluralModelLabel = 'Подкатегории';

    public function form(Form $form): Form
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
                Forms\Components\Hidden::make('parent_id')
                    ->default(fn ($livewire) => $livewire->ownerRecord->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('level_indicator')
                    ->label('Уровень')
                    ->getStateUsing(function ($record) {
                        return match($record->getLevel()) {
                            2 => 'Подкатегория',
                            3 => 'Подподкатегория',
                            default => 'Неизвестно'
                        };
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Подкатегория' => 'info',
                        'Подподкатегория' => 'warning',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('Подкатегорий')
                    ->counts('children')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Фото')
                    ->size(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_children')
                    ->label('С подкатегориями')
                    ->query(fn ($query) => $query->has('children')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(function ($livewire) {
                        $parentLevel = $livewire->ownerRecord->getLevel();
                        return match($parentLevel) {
                            1 => 'Добавить подкатегорию',
                            2 => 'Добавить подподкатегорию',
                            default => 'Добавить категорию'
                        };
                    })
                    ->modalHeading(function ($livewire) {
                        $parentLevel = $livewire->ownerRecord->getLevel();
                        return match($parentLevel) {
                            1 => 'Создать подкатегорию',
                            2 => 'Создать подподкатегорию',
                            default => 'Создать категорию'
                        };
                    })
                    ->successNotificationTitle(function ($livewire) {
                        $parentLevel = $livewire->ownerRecord->getLevel();
                        return match($parentLevel) {
                            1 => 'Подкатегория создана',
                            2 => 'Подподкатегория создана',
                            default => 'Категория создана'
                        };
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->getLevel() < 3)
                    ->before(function ($livewire) {
                        // Additional validation to prevent creating categories beyond level 3
                        if ($livewire->ownerRecord->getLevel() >= 3) {
                            throw new \Exception('Нельзя создавать категории ниже 3-го уровня.');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('manage_children')
                    ->label('Управлять подкатегориями')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn ($record) => route('filament.dashboard.resources.categories.edit', $record))
                    ->visible(fn ($record) => $record->getLevel() < 3 && ($record->children()->count() > 0 || $record->getLevel() < 3))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription(fn ($record) => 
                        $record->children()->count() > 0 
                            ? 'Внимание! У этой категории есть подкатегории. При удалении они также будут удалены.'
                            : 'Вы уверены, что хотите удалить эту категорию?'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
