<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Models\Group;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Groups';

    protected static ?string $modelLabel = 'Group';

    protected static ?string $pluralModelLabel = 'Groups';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Group')
                ->tabs([

                    // ── Tab 1: Details ──────────────────────────────────
                    Tabs\Tab::make('Group Details')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) =>
                                    $set('slug', Str::slug($state ?? ''))
                                ),

                            TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->helperText('Auto-filled from name — edit if needed'),

                            Textarea::make('description')
                                ->nullable()
                                ->rows(3)
                                ->columnSpanFull(),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->helperText('Inactive groups still gate content but members lose access'),
                        ]),

                    // ── Tab 2: Members ──────────────────────────────────
                    Tabs\Tab::make('Members')
                        ->icon('heroicon-o-users')
                        ->schema([
                            CheckboxList::make('members')
                                ->relationship('members', 'full_name')
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2)
                                ->label('')
                                ->helperText('Search and select members to add to this group'),
                        ]),

                    // ── Tab 3: Content (read-only) ──────────────────────
                    Tabs\Tab::make('Content')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([

                            Section::make('Stacks')
                                ->schema([
                                    Placeholder::make('courses_list')
                                        ->label('')
                                        ->content(function (?Group $record): HtmlString {
                                            if (! $record?->id) {
                                                return new HtmlString('<p style="color:#6b7280;font-size:0.875rem;">Save the group first.</p>');
                                            }
                                            $items = $record->courses()->orderBy('title')->get();
                                            if ($items->isEmpty()) {
                                                return new HtmlString('<p style="color:#6b7280;font-size:0.875rem;">No stacks assigned to this group yet. Edit a stack and set its Group Access field.</p>');
                                            }
                                            $links = $items->map(fn ($c) =>
                                                '<a href="' . route('filament.admin.resources.courses.edit', $c) . '" '
                                                . 'style="color:#f5a623;text-decoration:underline;">'
                                                . e($c->title) . '</a>'
                                            )->join('<br>');
                                            return new HtmlString($links);
                                        }),
                                ]),

                            Section::make('Live Sessions')
                                ->schema([
                                    Placeholder::make('rooms_list')
                                        ->label('')
                                        ->content(function (?Group $record): HtmlString {
                                            if (! $record?->id) {
                                                return new HtmlString('<p style="color:#6b7280;font-size:0.875rem;">Save the group first.</p>');
                                            }
                                            $items = $record->rooms()->orderBy('title')->get();
                                            if ($items->isEmpty()) {
                                                return new HtmlString('<p style="color:#6b7280;font-size:0.875rem;">No live sessions assigned to this group yet. Edit a live session and set its Group Access field.</p>');
                                            }
                                            $links = $items->map(fn ($r) =>
                                                '<a href="' . route('filament.admin.resources.live-sessions.edit', $r) . '" '
                                                . 'style="color:#f5a623;text-decoration:underline;">'
                                                . e($r->title) . '</a>'
                                            )->join('<br>');
                                            return new HtmlString($links);
                                        }),
                                ]),

                            Section::make('Recordings')
                                ->schema([
                                    Placeholder::make('recordings_list')
                                        ->label('')
                                        ->content(function (?Group $record): HtmlString {
                                            if (! $record?->id) {
                                                return new HtmlString('<p style="color:#6b7280;font-size:0.875rem;">Save the group first.</p>');
                                            }
                                            $items = $record->recordings()->orderBy('title')->get();
                                            if ($items->isEmpty()) {
                                                return new HtmlString('<p style="color:#6b7280;font-size:0.875rem;">No recordings assigned to this group yet. Recordings automatically inherit group assignments from their live session.</p>');
                                            }
                                            $links = $items->map(fn ($r) =>
                                                '<span style="color:#d1d5db;font-size:0.875rem;">' . e($r->title) . '</span>'
                                            )->join('<br>');
                                            return new HtmlString($links);
                                        }),
                                ]),
                        ]),

                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            GroupResource\RelationManagers\StripePricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit'   => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
