<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [

    'backup' => [

        /*
        |--------------------------------------------------------------------------
        | ERP Backup Name
        |--------------------------------------------------------------------------
        */

        'name' => env(

            'APP_NAME',

            'motorcycle-erp'

        ),

        /*
        |--------------------------------------------------------------------------
        | Backup Sources
        |--------------------------------------------------------------------------
        */

        'source' => [

            'files' => [

                'include' => [

                    base_path(),

                ],

                'exclude' => [

                    base_path('vendor'),

                    base_path('node_modules'),

                    storage_path('framework'),

                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                'relative_path' => null,

            ],

            'databases' => [

                env(

                    'DB_CONNECTION',

                    'mysql'

                ),

            ],

        ],

        /*
        |--------------------------------------------------------------------------
        | Database Dump
        |--------------------------------------------------------------------------
        */

        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        /*
        |--------------------------------------------------------------------------
        | Backup Destination
        |--------------------------------------------------------------------------
        */

        'destination' => [

            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => '',

            'disks' => [

                'backups',

            ],

            'continue_on_failure' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | Temporary Directory
        |--------------------------------------------------------------------------
        */

        'temporary_directory' => storage_path(

            'app/backup-temp'

        ),

        /*
        |--------------------------------------------------------------------------
        | Encryption
        |--------------------------------------------------------------------------
        */

        'password' => env(

            'BACKUP_ARCHIVE_PASSWORD'

        ),

        'encryption' => 'default',

        /*
        |--------------------------------------------------------------------------
        | Backup Verification
        |--------------------------------------------------------------------------
        */

        'verify_backup' => false,

        /*
        |--------------------------------------------------------------------------
        | Retry System
        |--------------------------------------------------------------------------
        */

        'tries' => 1,

        'retry_delay' => 0,

    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [

        'notifications' => [

            BackupHasFailedNotification::class => [

                'mail'

            ],

            UnhealthyBackupWasFoundNotification::class => [

                'mail'

            ],

            CleanupHasFailedNotification::class => [

                'mail'

            ],

            BackupWasSuccessfulNotification::class => [

                'mail'

            ],

            HealthyBackupWasFoundNotification::class => [

                'mail'

            ],

            CleanupWasSuccessfulNotification::class => [

                'mail'

            ],

        ],

        'notifiable' => Notifiable::class,

        'mail' => [

            'to' => env(

                'MAIL_FROM_ADDRESS',

                'admin@test.com'

            ),

            'from' => [

                'address' => env(

                    'MAIL_FROM_ADDRESS',

                    'admin@test.com'

                ),

                'name' => env(

                    'MAIL_FROM_NAME',

                    'Motorcycle ERP'

                ),

            ],

        ],

        'slack' => [

            'webhook_url' => '',

            'channel' => null,

            'username' => null,

            'icon' => null,

        ],

        'discord' => [

            'webhook_url' => '',

            'username' => '',

            'avatar_url' => '',

        ],

        'webhook' => [

            'url' => '',

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'log_channel' => null,

    /*
    |--------------------------------------------------------------------------
    | Backup Monitoring
    |--------------------------------------------------------------------------
    */

    'monitor_backups' => [

        [

            'name' => env(

                'APP_NAME',

                'motorcycle-erp'

            ),

            'disks' => [

                'backups',

            ],

            'health_checks' => [

                MaximumAgeInDays::class => 1,

                MaximumStorageInMegabytes::class => 5000,

            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Strategy
    |--------------------------------------------------------------------------
    */

    'cleanup' => [

        'strategy' => DefaultStrategy::class,

        'default_strategy' => [

            /*
            |--------------------------------------------------------------------------
            | Retention Rules
            |--------------------------------------------------------------------------
            */

            'keep_all_backups_for_days' => 14,

            'keep_daily_backups_for_days' => 30,

            'keep_weekly_backups_for_weeks' => 12,

            'keep_monthly_backups_for_months' => 12,

            'keep_yearly_backups_for_years' => 5,

            'delete_oldest_backups_when_using_more_megabytes_than' => 10000,

        ],

        /*
        |--------------------------------------------------------------------------
        | Cleanup Retry
        |--------------------------------------------------------------------------
        */

        'tries' => 1,

        'retry_delay' => 0,

    ],

];