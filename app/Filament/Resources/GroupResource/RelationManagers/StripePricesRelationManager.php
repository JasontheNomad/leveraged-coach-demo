<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StripePricesRelationManager extends RelationManager
{
    protected static string $relationship = 'stripePrices';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('stripe_price')
                ->label('Stripe Price ID')
                ->required()
                ->maxLength(255)
                ->helperText('Paste from Stripe dashboard (price_...)')
                ->columnSpanFull(),
            Select::make('mode')
                ->required()
                ->default('subscription')
                ->options([
                    'subscription' => 'subscription',
                    'one_time'     => 'one_time',
                    'payment_plan' => 'payment_plan',
                ]),
            TextInput::make('label')
                ->label('Display Label')
                ->maxLength(255),
            TextInput::make('amount')
                ->label('Amount')
                ->prefix('$')
                ->numeric()
                ->step(0.01)
                ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
            Select::make('interval')
                ->options([
                    'week'  => 'Weekly',
                    'month' => 'Monthly',
                    'year'  => 'Yearly',
                ])
                ->nullable()
                ->helperText('Leave blank for one-time'),
            Toggle::make('is_active')
                ->default(true),
            TextInput::make('sort')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('label')->searchable(),
                TextColumn::make('mode')->badge(),
                TextColumn::make('amount')->money('USD', divideBy: 100),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort')->sortable()->width('80px'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()]);
    }
}
