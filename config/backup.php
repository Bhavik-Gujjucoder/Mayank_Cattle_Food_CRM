<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Server Identity
    |--------------------------------------------------------------------------
    |
    | Generated once via `php artisan backup:init`. Used to bind backups to
    | this server installation so they cannot be restored elsewhere.
    |
    */

    'server_id' => env('BACKUP_SERVER_ID'),

    'server_secret' => env('BACKUP_SERVER_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Database Utilities (Windows/XAMPP defaults)
    |--------------------------------------------------------------------------
    */

    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'D:/xampp/mysql/bin/mysqldump.exe'),

    'mysql_path' => env('BACKUP_MYSQL_PATH', 'D:/xampp/mysql/bin/mysql.exe'),

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */

    'storage_paths' => [
        storage_path('app/public'),
    ],

    'output_path' => storage_path('app/private/backups'),

    'extension' => 'mcfbackup',

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'bind_to_server' => env('BACKUP_BIND_TO_SERVER', true),

    'hkdf_info' => 'mcf-backup-encryption-v1',

    'magic' => 'MCFBK1',

    'version' => 1,

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    */

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

];
