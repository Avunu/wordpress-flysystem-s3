<?php

namespace Abedi\WPFlysystemS3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DatabaseConnection
{
    private static ?Connection $connection = null;

    private static function getConnectionParams(): array
    {
        if (defined('DB_ENGINE') && DB_ENGINE === 'sqlite') {
            return [
                'driver' => 'sqlite3',
                'path' => WP_CONTENT_DIR . '/database/.sqlite',
            ];
        }

        return [
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASSWORD,
            'host' => DB_HOST,
            'driver' => 'mysqli',
        ];
    }

    public static function getConnection(): Connection
    {
        if (self::$connection === null) {
            self::$connection = DriverManager::getConnection(self::getConnectionParams());
        }

        return self::$connection;
    }
}
