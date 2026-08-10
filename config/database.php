<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'aiu_club_store';
const DB_USER = 'root';
const DB_PASS = '';

function database(): mysqli
{
    static $connection = null;

    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($connection->connect_error) {
            exit('Database connection failed. Check XAMPP MySQL and import database.sql.');
        }
        $connection->set_charset('utf8mb4');
    }

    return $connection;
}
