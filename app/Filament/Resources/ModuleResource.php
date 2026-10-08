<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleResource\Pages;
use App\Filament\Resources\ModuleResource\RelationManagers;
use App\Models\Course;
use App\Models\Module;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Content';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('course_id')
                ->label('Stack')
                ->options(Course::pluck('title', 'id'))
                ->searchable()
                ->required(),

            TextInput::make('title')
                ->required()
                ->maxLength(255),

            TextInput::make('position')
                ->numeric()
                ->required()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Stack')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('position')
                    ->sortable(),

                TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->label('Lessons'),
            ])
            ->defaultSort('course_id')
            ->actions([EditAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'edit'   => Pages\EditModule::route('/{record}/edit'),
        ];
    }
}
