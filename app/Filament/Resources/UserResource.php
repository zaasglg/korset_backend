<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Services\WalletService;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Управление пользователями';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),

                Forms\Components\TextInput::make('balance')
                    ->label('Баланс (KZT)')
                    ->numeric()
                    ->default(0)
                    ->step(0.01)
                    ->minValue(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс')
                    ->money('KZT')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата регистрации')
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\Action::make('add_funds')
                    ->label('Пополнить баланс')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма пополнения (KZT)')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->minValue(1)
                            ->maxValue(1000000),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->placeholder('Ручное пополнение администратором')
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data) {
                        try {
                            $walletService = app(WalletService::class);
                            
                            $transaction = $walletService->deposit(
                                $record,
                                $data['amount'],
                                $data['description'] ?? 'Ручное пополнение администратором',
                                'ADMIN-' . auth()->id() . '-' . time()
                            );

                            Notification::make()
                                ->title('Баланс пополнен')
                                ->body("Пользователю {$record->name} добавлено {$data['amount']} KZT")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Ошибка пополнения')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('deduct_funds')
                    ->label('Списать средства')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма списания (KZT)')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->minValue(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->placeholder('Ручное списание администратором')
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data) {
                        try {
                            $walletService = app(WalletService::class);
                            
                            if (!$walletService->hasSufficientFunds($record, $data['amount'])) {
                                Notification::make()
                                    ->title('Недостаточно средств')
                                    ->body("У пользователя {$record->name} недостаточно средств для списания")
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $transaction = $walletService->withdraw(
                                $record,
                                $data['amount'],
                                $data['description'] ?? 'Ручное списание администратором',
                                'ADMIN-' . auth()->id() . '-' . time()
                            );

                            Notification::make()
                                ->title('Средства списаны')
                                ->body("У пользователя {$record->name} списано {$data['amount']} KZT")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Ошибка списания')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
