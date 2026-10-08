<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Models\Lesson;
use App\Models\Module;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'Content';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('module_id')
                ->label('Module')
                ->options(
                    Module::with('course')
                        ->get()
                        ->mapWithKeys(fn ($m) => [$m->id => "{$m->course->title} — {$m->title}"])
                )
                ->searchable()
                ->required(),

            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('video_url')
                ->url()
                ->nullable()
                ->helperText('Supports YouTube, Vimeo, or direct MP4 URLs')
                ->columnSpanFull(),

            TextInput::make('duration_seconds')
                ->numeric()
                ->nullable()
                ->label('Duration (seconds)'),

            TextInput::make('position')
                ->numeric()
                ->required()
                ->default(0),

            Toggle::make('is_preview')
                ->label('Free preview'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module.course.title')
                    ->label('Stack')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module.title')
                    ->label('Module')
                    ->searchable(),

                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('position')
                    ->sortable(),

                TextColumn::make('duration_seconds')
                    ->label('Duration (s)')
                    ->sortable(),

                IconColumn::make('is_preview')
                    ->boolean()
                    ->label('Preview'),
            ])
            ->defaultSort('module_id')
            ->actions([EditAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit'   => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
