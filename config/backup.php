<?php

return [

    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),

        'source' => [
            'files' => [
                'include' => [],
                'exclude' => [],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            'databases' => [
                'mysql',
            ],
        ],

        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_STORE,
            'compression_level' => 9,
            'filename_prefix' => '',
            'disks' => [
                'r2-backup',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        'tries' => 1,

        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class          => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class  => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class         => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class      => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class    => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class     => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'admin@example.com',

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@leveragedcoach.com'),
                'name'    => env('MAIL_FROM_NAME', 'Leveraged Coach'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username'    => '',
            'avatar_url'  => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name'   => env('APP_NAME', 'laravel-backup'),
            'disks'  => ['r2-backup'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class          => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 2000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'                          => 7,
            'keep_daily_backups_for_days'                        => 0,
            'keep_weekly_backups_for_weeks'                      => 4,
            'keep_monthly_backups_for_months'                    => 1,
            'keep_yearly_backups_for_years'                      => 1,
            'delete_oldest_backups_when_using_more_megabytes_than' => 2000,
        ],

        'tries'       => 1,
        'retry_delay' => 0,
    ],

];
