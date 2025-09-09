<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Кошелек';

    protected static ?string $navigationLabel = 'Транзакции';

    protected static ?string $modelLabel = 'Транзакция';

    protected static ?string $pluralModelLabel = 'Транзакции';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Тип транзакции')
                    ->options([
                        'deposit' => 'Пополнение',
                        'withdrawal' => 'Списание',
                        'purchase' => 'Покупка',
                        'refund' => 'Возврат',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Сумма')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->minValue(0.01),
                Forms\Components\TextInput::make('description')
                    ->label('Описание')
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference_id')
                    ->label('Референс ID')
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'completed' => 'Завершена',
                        'failed' => 'Неуспешна',
                        'cancelled' => 'Отменена',
                    ])
                    ->default('completed')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'success' => 'deposit',
                        'danger' => 'withdrawal',
                        'warning' => 'purchase',
                        'info' => 'refund',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => 'Пополнение',
                        'withdrawal' => 'Списание',
                        'purchase' => 'Покупка',
                        'refund' => 'Возврат',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('KZT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Баланс после')
                    ->money('KZT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В ожидании',
                        'completed' => 'Завершена',
                        'failed' => 'Неуспешна',
                        'cancelled' => 'Отменена',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип транзакции')
                    ->options([
                        'deposit' => 'Пополнение',
                        'withdrawal' => 'Списание',
                        'purchase' => 'Покупка',
                        'refund' => 'Возврат',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'completed' => 'Завершена',
                        'failed' => 'Неуспешна',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (WalletTransaction $record) => $record->status === 'pending'),
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
            'index' => Pages\ListWalletTransactions::route('/'),
            'create' => Pages\CreateWalletTransaction::route('/create'),
            'edit' => Pages\EditWalletTransaction::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Транзакции создаются только через API
    }
}
