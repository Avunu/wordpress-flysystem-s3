<?php
/**
 * Plugin Name: Wordpress Flysystem S3
 * Plugin URI: https://bluc.ir/
 * Description: Upload wordpress media files to s3 media library
 * Version: 1.0.0
 * Author: Mehdi Abedi
 * Author URI: https://bluc.ir/
 **/

if (!defined('ABSPATH')) {
    exit(1);
}

if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require __DIR__ . "/vendor/autoload.php";
} else {
    spl_autoload_register(function ($class) {
        if (strpos($class, 'Abedi\\WPFlysystemS3\\') === 0) {
            $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 19)) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        }
    });
}

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;

function createDatabaseIfNeeded()
{
    global $wpdb;

    $tableName = $wpdb->base_prefix . 'fs_s3_files';
    $connection = Abedi\WPFlysystemS3\DatabaseConnection::getConnection();

    // Check if table exists
    $schemaManager = $connection->createSchemaManager();
    if ($schemaManager->tablesExist([$tableName])) {
        return;
    }

    // Create the table schema
    $schema = new Schema();
    $table = $schema->createTable($tableName);
    $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
    $table->addColumn('local_file', 'string', ['length' => 512]);
    $table->addColumn('remote_file', 'string', ['length' => 512]);
    $table->addColumn('md5', 'string', ['length' => 32, 'fixed' => true]);
    $table->addColumn('count', 'smallint', ['unsigned' => true]);
    $table->setPrimaryKey(['id']);
    $table->addUniqueIndex(['local_file', 'remote_file', 'md5'], 'unique_local_remote_md5');
    $table->addIndex(['md5'], 'md5_index');

    // Execute the schema creation
    $queries = $schema->toSql($connection->getDatabasePlatform());
    foreach ($queries as $query) {
        $connection->executeStatement($query);
    }
}

register_activation_hook(
    __FILE__,
    'createDatabaseIfNeeded'
);

$serviceProvider = Abedi\WPFlysystemS3\ServiceProvider::getInstance();
$serviceProvider->boot();
