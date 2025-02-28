<?php

namespace Abedi\WPFlysystemS3;

use Doctrine\DBAL\Connection;

class DBManager
{
    private static ?self $instance = null;
    private Connection $connection;
    private string $table;

    public static function getInstance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->base_prefix . 'fs_s3_files';
        $this->connection = DatabaseConnection::getConnection();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
