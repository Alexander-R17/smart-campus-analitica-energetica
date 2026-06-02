<?php

class Database
{
    public static function connect(): PDO
    {
        $config = require __DIR__ . '/ExternalServices.php';

        $dsn = "mysql:host={$config['MYSQL_HOST']};port={$config['MYSQL_PORT']};dbname={$config['MYSQL_DB']};charset=utf8mb4";

        return new PDO($dsn, $config['MYSQL_USER'], $config['MYSQL_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}
