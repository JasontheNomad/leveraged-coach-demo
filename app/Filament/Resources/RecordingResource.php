<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordingResource\Pages;
use App\Models\Recording;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecordingResource extends Resource
{
    protected static ?string $model = Recording::class;

    protected static ?string $navigationIcon = 'heroicon-o-film';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room.title')
                    ->label('Room')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready'      => 'success',
                        'processing' => 'warning',
                        'failed'     => 'danger',
                    }),

                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state
                        ? gmdate(($state >= 3600 ? 'G:i:s' : 'i:s'), $state)
                        : '--')
                    ->sortable(),

                TextColumn::make('recorded_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('daily_recording_id')
                    ->label('Daily ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'processing' => 'Processing',
                        'ready'      => 'Ready',
                        'failed'     => 'Failed',
                    ]),
            ])
            ->actions([DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordings::route('/'),
        ];
    }
}
