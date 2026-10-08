<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

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

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('video_url')->url()->nullable()->columnSpanFull(),
            TextInput::make('duration_seconds')->numeric()->nullable()->label('Duration (seconds)'),
            TextInput::make('position')->numeric()->required()->default(0),
            Toggle::make('is_preview')->label('Free preview'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('position')->sortable()->width('80px'),
                TextColumn::make('title')->searchable(),
                TextColumn::make('duration_seconds')->label('Duration (s)'),
                IconColumn::make('is_preview')->boolean()->label('Preview'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()]);
    }
}
