<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env(

        'FILESYSTEM_DISK',

        'local'

    ),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        /*
        |--------------------------------------------------------------------------
        | Local Private Storage
        |--------------------------------------------------------------------------
        */

        'local' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/private'
            ),

            'serve' => true,

            'throw' => false,

            'report' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | Public Storage
        |--------------------------------------------------------------------------
        */

        'public' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/public'
            ),

            'url' => env(
                'FILESYSTEM_PUBLIC_URL',
                '/storage'
            ),

            'visibility' => 'public',

            'throw' => false,

            'report' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | ERP Documents
        |--------------------------------------------------------------------------
        */

        'documents' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/public/documents'
            ),

            'url' => env('APP_URL').'/storage/documents',

            'visibility' => 'public',

            'throw' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | ERP Templates
        |--------------------------------------------------------------------------
        */

        'templates' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/public/templates'
            ),

            'url' => env('APP_URL').'/storage/templates',

            'visibility' => 'public',

            'throw' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | ERP Logos
        |--------------------------------------------------------------------------
        */

        'logos' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/public/logos'
            ),

            'url' => env('APP_URL').'/storage/logos',

            'visibility' => 'public',

            'throw' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | ERP Stamps
        |--------------------------------------------------------------------------
        */

        'stamps' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/public/stamps'
            ),

            'url' => env('APP_URL').'/storage/stamps',

            'visibility' => 'public',

            'throw' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | ERP Signatures
        |--------------------------------------------------------------------------
        */

        'signatures' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/public/signatures'
            ),

            'url' => env('APP_URL').'/storage/signatures',

            'visibility' => 'public',

            'throw' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | ERP Backups
        |--------------------------------------------------------------------------
        */

        'backups' => [

            'driver' => 'local',

            'root' => storage_path(
                'app/backups'
            ),

            'throw' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | Amazon S3
        |--------------------------------------------------------------------------
        */

        's3' => [

            'driver' => 's3',

            'key' => env(
                'AWS_ACCESS_KEY_ID'
            ),

            'secret' => env(
                'AWS_SECRET_ACCESS_KEY'
            ),

            'region' => env(
                'AWS_DEFAULT_REGION'
            ),

            'bucket' => env(
                'AWS_BUCKET'
            ),

            'url' => env(
                'AWS_URL'
            ),

            'endpoint' => env(
                'AWS_ENDPOINT'
            ),

            'use_path_style_endpoint' => env(
                'AWS_USE_PATH_STYLE_ENDPOINT',
                false
            ),

            'throw' => false,

            'report' => false,

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [

        public_path('storage') =>
            storage_path('app/public'),

    ],

];
