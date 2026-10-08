<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\ModuleResource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('position')->numeric()->required()->default(0),
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
                TextColumn::make('lessons_count')->counts('lessons')->label('Lessons'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('lessons')
                    ->label('Lessons')
                    ->icon('heroicon-o-play-circle')
                    ->url(fn ($record) => ModuleResource::getUrl('edit', ['record' => $record]))
                    ->color('info'),
                DeleteAction::make(),
            ])
            ->headerActions([CreateAction::make()]);
    }
}
