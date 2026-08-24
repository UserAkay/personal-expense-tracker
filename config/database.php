<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config.php';


/*
|--------------------------------------------------------------------------
| Database Class
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../classes/Database.php';


/*
|--------------------------------------------------------------------------
| Create PDO Connection
|--------------------------------------------------------------------------
*/

try {

    $pdo = Database::getConnection();

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Log Database Error
    |--------------------------------------------------------------------------
    |
    | Do not expose database credentials or internal SQL errors to users.
    |
    */

    error_log(
        'Database connection error: '
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | User-Friendly Error
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    exit(
        'Unable to connect to the database. Please try again later.'
    );
}