<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class MemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $pluralModelLabel = 'Members';

    protected static ?string $navigationGroup = 'Members';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Placeholder::make('active_status')
                ->label('Status')
                ->content(function (?User $record): HtmlString {
                    if (! $record) {
                        return new HtmlString('');
                    }
                    $active = $record->last_seen_at
                        && $record->last_seen_at->gt(now()->subDays(30));
                    return $active
                        ? new HtmlString('<span style="color:#16a34a;font-weight:600;">● Active</span>')
                        : new HtmlString('<span style="color:#6b7280;font-weight:600;">● Inactive</span>');
                }),

            Tabs::make('Member')
                ->tabs([

                    // ── Tab 1: Profile ──────────────────────────────────
                    Tabs\Tab::make('Profile')
                        ->icon('heroicon-o-user')
                        ->schema([

                            // Avatar preview
                            Placeholder::make('avatar_preview')
                                ->label('Avatar')
                                ->content(function (?User $record): HtmlString {
                                    if (! $record) {
                                        return new HtmlString('');
                                    }
                                    if ($record->avatar_url) {
                                        return new HtmlString(
                                            '<img src="' . e($record->avatar_url) . '" '
                                            . 'style="width:64px;height:64px;border-radius:50%;object-fit:cover;">'
                                        );
                                    }
                                    $initial = strtoupper(substr($record->full_name, 0, 1));
                                    return new HtmlString(
                                        '<div style="width:64px;height:64px;border-radius:50%;background:#f5a623;'
                                        . 'color:#111111;display:flex;align-items:center;justify-content:center;'
                                        . 'font-size:1.5rem;font-weight:700;">' . e($initial) . '</div>'
                                    );
                                }),

                            TextInput::make('full_name')
                                ->label('Full name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('password')
                                ->password()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->minLength(8)
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                ->dehydrated(fn ($state) => filled($state))
                                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep current password.' : null),

                            Select::make('role')
                                ->options(['member' => 'Member', 'admin' => 'Admin'])
                                ->required()
                                ->default('member'),

                            Placeholder::make('last_seen_at')
                                ->label('Last seen')
                                ->content(fn (?User $record) => $record?->last_seen_at
                                    ? $record->last_seen_at->diffForHumans()
                                    : 'Never'),

                            Placeholder::make('created_at')
                                ->label('Member since')
                                ->content(fn (?User $record) => $record?->created_at?->format('M j, Y')),

                        ]),

                    // ── Tab 2: Group Access ─────────────────────────────
                    Tabs\Tab::make('Group Access')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            CheckboxList::make('groups')
                                ->relationship('groups', 'name')
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2)
                                ->label('')
                                ->helperText('Select which groups this member can access. Groups control access to restricted stacks, live sessions and recordings.'),
                        ]),

                    // ── Tab 3: Course Access ────────────────────────────
                    Tabs\Tab::make('Stack Access')
                        ->icon('heroicon-o-academic-cap')
                        ->schema([
                            CheckboxList::make('courses')
                                ->relationship('courses', 'title')
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(1)
                                ->label('')
                                ->helperText('Grant this member direct access to specific stacks, regardless of their group membership or plan.')
                                ->getOptionLabelFromRecordUsing(fn (Course $record) => $record->title),
                        ]),

                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Avatar
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (User $record) => self::initialsAvatar($record))
                    ->width(36)
                    ->height(36),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin'  => 'warning',
                        default  => 'gray',
                    }),

                TextColumn::make('groups.name')
                    ->label('Groups')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->color('gray'),

                TextColumn::make('last_seen_at')
                    ->label('Last seen')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->diffForHumans() : 'Never')
                    ->color(fn ($state) => $state && $state->gt(now()->subDays(30)) ? 'success' : 'gray'),

                TextColumn::make('active')
                    ->label('Status')
                    ->getStateUsing(fn (User $record): string =>
                        $record->last_seen_at && $record->last_seen_at->gt(now()->subDays(30))
                            ? 'Active'
                            : 'Inactive'
                    )
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->searchPlaceholder('Search by name or email…')
            ->filters([
                SelectFilter::make('role')
                    ->options(['member' => 'Member', 'admin' => 'Admin']),

                SelectFilter::make('group')
                    ->label('Group')
                    ->relationship('groups', 'name'),

                SelectFilter::make('active_status')
                    ->label('Activity')
                    ->options(['active' => 'Active (last 30 days)', 'inactive' => 'Inactive'])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active'   => $query->where('last_seen_at', '>=', now()->subDays(30)),
                            'inactive' => $query->where(function ($q) {
                                $q->whereNull('last_seen_at')
                                  ->orWhere('last_seen_at', '<', now()->subDays(30));
                            }),
                            default => $query,
                        };
                    }),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit'   => Pages\EditMember::route('/{record}/edit'),
        ];
    }

    private static function initialsAvatar(User $record): string
    {
        $initial = urlencode(strtoupper(substr($record->full_name, 0, 1)));
        return "https://ui-avatars.com/api/?name={$initial}&background=f5a623&color=111111&size=64";
    }
}
