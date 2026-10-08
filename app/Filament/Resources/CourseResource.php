<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Models\Group;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Stacks';

    protected static ?string $modelLabel = 'Stack';

    protected static ?string $pluralModelLabel = 'Stacks';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Course')
                ->columnSpanFull()
                ->tabs([

                    Tabs\Tab::make('Stack Details')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                                ->columnSpanFull(),

                            TextInput::make('slug')
                                ->required()
                                ->unique(Course::class, 'slug', ignoreRecord: true)
                                ->maxLength(255)
                                ->columnSpanFull(),

                            RichEditor::make('description')
                                ->columnSpanFull(),

                            FileUpload::make('thumbnail')
                                ->label('Thumbnail')
                                ->image()
                                ->disk('r2')
                                ->directory('thumbnails')
                                ->visibility('public')
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('16:9')
                                ->imageResizeTargetWidth('1280')
                                ->imageResizeTargetHeight('720')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(5120)
                                ->helperText('Recommended: 1280×720px, max 5MB. JPG, PNG or WebP.')
                                ->columnSpanFull(),

                            Select::make('price_type')
                                ->options([
                                    'free'            => 'Free',
                                    'collective'      => 'Collective',
                                    'leveraged_coach' => 'Leveraged Coach',
                                ])
                                ->required()
                                ->default('free'),

                            CheckboxList::make('group_ids')
                                ->label('Group access')
                                ->options(Group::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                                ->columns(2)
                                ->helperText('Select one or more groups to restrict access. Leave empty to make visible to everyone.'),

                            Toggle::make('published')
                                ->default(false),
                        ]),

                    Tabs\Tab::make('Modules & Lessons')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Repeater::make('modules')
                                ->relationship('modules')
                                ->label('')
                                ->orderColumn('position')
                                ->collapsible()
                                ->collapsed()
                                ->cloneable()
                                ->itemLabel(fn (array $state): string => $state['title'] ?? 'New Module')
                                ->schema([
                                    TextInput::make('title')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->columnSpanFull(),

                                    TextInput::make('position')
                                        ->numeric()
                                        ->required()
                                        ->default(0)
                                        ->label('Order'),

                                    Repeater::make('lessons')
                                        ->relationship('lessons')
                                        ->label('Lessons')
                                        ->orderColumn('position')
                                        ->collapsible()
                                        ->collapsed()
                                        ->cloneable()
                                        ->itemLabel(fn (array $state): string => $state['title'] ?? 'New Lesson')
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('title')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),

                                            TextInput::make('video_url')
                                                ->url()
                                                ->nullable()
                                                ->helperText('Supports YouTube, Vimeo, or direct MP4 URLs')
                                                ->columnSpanFull(),

                                            RichEditor::make('body')
                                                ->label('Lesson content')
                                                ->helperText('Add text, headings, and links below the video')
                                                ->toolbarButtons([
                                                    'bold',
                                                    'italic',
                                                    'underline',
                                                    'strike',
                                                    'link',
                                                    'heading',
                                                    'bulletList',
                                                    'orderedList',
                                                    'blockquote',
                                                    'codeBlock',
                                                ])
                                                ->nullable()
                                                ->columnSpanFull(),

                                            TextInput::make('position')
                                                ->numeric()
                                                ->required()
                                                ->default(0)
                                                ->label('Order'),

                                            Toggle::make('is_preview')
                                                ->label('Free preview'),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'collective'      => 'Collective',
                        'leveraged_coach' => 'Leveraged Coach',
                        default           => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'free'            => 'success',
                        'collective'      => 'info',
                        'leveraged_coach' => 'warning',
                        default           => 'gray',
                    }),

                TextColumn::make('modules_count')
                    ->counts('modules')
                    ->label('Modules'),

                ToggleColumn::make('published'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit'   => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
