<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Referrals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('referrer_id')
                    ->relationship('referrer', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('referred_id')
                    ->relationship('referred', 'name')
                    ->searchable(),
                Forms\Components\TextInput::make('referral_code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(10),
                Forms\Components\TextInput::make('reward_amount')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01),
                Forms\Components\Toggle::make('is_paid')
                    ->label('Is Paid'),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Paid At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referred.name')
                    ->label('Referred User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not used yet'),
                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Referral Code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('reward_amount')
                    ->label('Reward Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not paid'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payment Status'),
                Tables\Filters\Filter::make('has_referred')
                    ->label('Used Referrals')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('referred_id')),
                Tables\Filters\Filter::make('unused_referrals')
                    ->label('Unused Referrals')
                    ->query(fn (Builder $query): Builder => $query->whereNull('referred_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Referral $record): bool => !$record->is_paid && $record->referred_id)
                    ->action(function (Referral $record) {
                        $record->markAsPaid();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_selected_as_paid')
                        ->label('Mark Selected as Paid')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->is_paid && $record->referred_id) {
                                    $record->markAsPaid();
                                }
                            }
                        })
                        ->requiresConfirmation(),
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
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNotNull('referred_id')->where('is_paid', false)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
