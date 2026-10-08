<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Group;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Live Sessions';

    protected static ?string $modelLabel = 'Live Session';

    protected static ?string $pluralModelLabel = 'Live Sessions';

    protected static ?string $slug = 'live-sessions';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) =>
                    $set('daily_room_name', \Illuminate\Support\Str::slug($state))
                )
                ->columnSpanFull(),

            Textarea::make('description')
                ->nullable()
                ->rows(3)
                ->columnSpanFull(),

            FileUpload::make('thumbnail')
                ->label('Thumbnail')
                ->image()
                ->disk('r2')
                ->directory('room-thumbnails')
                ->visibility('public')
                ->previewable(false)
                ->columnSpanFull(),

            TextInput::make('daily_room_name')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('URL-safe slug used as the Daily.co room name (auto-filled on create)')
                ->columnSpanFull(),

            // ── Hidden stores — actual DB columns ──────────────────────
            Hidden::make('scheduled_at'),
            Hidden::make('scheduled_end_at'),

            // ── Date & time ────────────────────────────────────────────
            Section::make('Date & time')
                ->schema([
                    Grid::make(9)
                        ->schema([
                            DatePicker::make('scheduled_date')
                                ->label('Start date')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    $time = $get('scheduled_time') ?: '00:00';
                                    $set('scheduled_at', $state ? ($state . ' ' . $time) : null);
                                })
                                ->columnSpan(2),

                            Select::make('scheduled_time')
                                ->label('Start time')
                                ->options(self::timeOptions())
                                ->searchable()
                                ->optionsLimit(96)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    $date = $get('scheduled_date') ?: now()->format('Y-m-d');
                                    $set('scheduled_at', $state ? ($date . ' ' . $state) : null);
                                })
                                ->columnSpan(2),

                            Placeholder::make('to_label')
                                ->label('')
                                ->content('to')
                                ->extraAttributes(['class' => 'flex items-center justify-center pt-6'])
                                ->columnSpan(1),

                            DatePicker::make('end_date')
                                ->label('End date')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    $time = $get('end_time') ?: '00:00';
                                    $set('scheduled_end_at', $state ? ($state . ' ' . $time) : null);
                                })
                                ->columnSpan(2),

                            Select::make('end_time')
                                ->label('End time')
                                ->options(self::timeOptions())
                                ->searchable()
                                ->optionsLimit(96)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    $date = $get('end_date') ?: now()->format('Y-m-d');
                                    $set('scheduled_end_at', $state ? ($date . ' ' . $state) : null);
                                })
                                ->rule(static function (Get $get): \Closure {
                                    return static function (string $attribute, $value, \Closure $fail) use ($get): void {
                                        $start = $get('scheduled_at');
                                        $end   = $get('scheduled_end_at');
                                        if (empty($start) || empty($end)) {
                                            return; // no end set → nothing to compare
                                        }
                                        $startUtc = Carbon::parse($start, 'America/Phoenix')->utc();
                                        $endUtc   = Carbon::parse($end, 'America/Phoenix')->utc();
                                        if ($endUtc->lessThanOrEqualTo($startUtc)) {
                                            $fail('End time must be after start time.');
                                        }
                                    };
                                })
                                ->columnSpan(2),
                        ]),
                ])
                ->columnSpanFull(),

            // ── Recurrence — reacts to scheduled_date ──────────────────
            Select::make('recurrence')
                ->label('Repeat')
                ->options(function (Get $get): array {
                    $scheduledDate = $get('scheduled_date');
                    $dayName       = $scheduledDate
                        ? Carbon::parse($scheduledDate)->format('l')
                        : 'selected day';

                    return [
                        'none'     => 'Does not repeat',
                        'daily'    => 'Daily',
                        'weekdays' => 'Every Weekday (Monday to Friday)',
                        'weekly'   => "Every {$dayName}",
                        'biweekly' => "Bi-weekly on {$dayName}",
                    ];
                })
                ->live()
                ->default('none')
                ->required(),

            DatePicker::make('recurs_until')
                ->label('Event Ends')
                ->helperText('The series stops repeating after this date.')
                ->hidden(fn (Get $get) => $get('recurrence') === 'none' || $get('recurrence') === null)
                ->nullable(),

            Select::make('host_id')
                ->label('Host')
                ->options(User::pluck('full_name', 'id'))
                ->searchable()
                ->required()
                ->default(fn () => auth()->id()),

            Select::make('required_plan')
                ->options(['free' => 'Free', 'collective' => 'Collective', 'leveraged_coach' => 'Leveraged Coach'])
                ->default('free')
                ->required(),

            Toggle::make('enable_recording')
                ->label('Enable cloud recording')
                ->helperText('Automatically record this session to the cloud')
                ->default(true)
                ->columnSpanFull(),

            CheckboxList::make('group_ids')
                ->label('Group access')
                ->options(Group::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                ->columns(2)
                ->helperText('Select one or more groups to restrict access. Recordings will automatically inherit these group assignments.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('daily_room_name')->searchable(),
                TextColumn::make('recordings_count')
                    ->counts('recordings')
                    ->label('Recordings')
                    ->badge(),
                TextColumn::make('host.full_name')->label('Host'),
                TextColumn::make('required_plan')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'collective'      => 'Collective',
                        'leveraged_coach' => 'Leveraged Coach',
                        default           => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'free'            => 'success',
                        'collective'      => 'info',
                        'leveraged_coach' => 'warning',
                        default           => 'gray',
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Scheduled for')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('America/Phoenix')
                    ->sortable(),
                TextColumn::make('recurrence')
                    ->badge()
                    ->formatStateUsing(function ($state, Room $record): ?string {
                        $until = $record->recurs_until
                            ? ' until ' . $record->recurs_until->format('M j')
                            : '';
                        return match ($state) {
                            'daily'    => 'Daily' . $until,
                            'weekdays' => 'Weekdays' . $until,
                            'weekly'   => 'Weekly ' . ($record->scheduled_at?->copy()->setTimezone('America/Phoenix')->format('D') ?? '') . $until,
                            'biweekly' => 'Bi-weekly ' . ($record->scheduled_at?->copy()->setTimezone('America/Phoenix')->format('D') ?? '') . $until,
                            default    => null,
                        };
                    })
                    ->color('info')
                    ->placeholder('—'),
            ])
            ->defaultSort('scheduled_at')
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    private static function timeOptions(): array
    {
        $times = [];
        for ($hour = 0; $hour < 24; $hour++) {
            foreach ([0, 15, 30, 45] as $minute) {
                $value          = sprintf('%02d:%02d', $hour, $minute);
                $times[$value]  = Carbon::createFromFormat('H:i', $value)->format('g:i A');
            }
        }
        return $times;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit'   => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
