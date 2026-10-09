<?php
require_once __DIR__ . '/config.php';

try {
    $port    = getenv('DB_PORT') !== false ? getenv('DB_PORT') : 3306;
    $useSSL  = getenv('DB_SSL') === 'true';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($useSSL) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        $options
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Please check config/config.php and ensure MySQL is running.');
}
