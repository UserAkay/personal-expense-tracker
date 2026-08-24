<?php

declare(strict_types=1);

class Database
{
    /*
    |--------------------------------------------------------------------------
    | Singleton Connection
    |--------------------------------------------------------------------------
    */

    private static ?PDO $connection = null;


    /*
    |--------------------------------------------------------------------------
    | Prevent Direct Construction
    |--------------------------------------------------------------------------
    */

    private function __construct()
    {
    }


    /*
    |--------------------------------------------------------------------------
    | Get Database Connection
    |--------------------------------------------------------------------------
    */

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }


        /*
        |--------------------------------------------------------------------------
        | Database Configuration
        |--------------------------------------------------------------------------
        */

        $host =
            getenv('DB_HOST')
            ?: '127.0.0.1';

        $port =
            getenv('DB_PORT')
            ?: '3306';

        $database =
            getenv('DB_DATABASE')
            ?: 'expense_tracker';

        $username =
            getenv('DB_USERNAME')
            ?: 'root';

        $password =
            getenv('DB_PASSWORD')
            ?: '';


        /*
        |--------------------------------------------------------------------------
        | DSN
        |--------------------------------------------------------------------------
        */

        $dsn =
            "mysql:"
            . "host={$host};"
            . "port={$port};"
            . "dbname={$database};"
            . "charset=utf8mb4";


        /*
        |--------------------------------------------------------------------------
        | PDO Connection
        |--------------------------------------------------------------------------
        */

        self::$connection = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,

                PDO::ATTR_STRINGIFY_FETCHES =>
                    false,

                PDO::ATTR_PERSISTENT =>
                    false
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | MySQL Session Settings
        |--------------------------------------------------------------------------
        */

        self::$connection->exec(
            "SET NAMES utf8mb4"
        );


        return self::$connection;
    }
}