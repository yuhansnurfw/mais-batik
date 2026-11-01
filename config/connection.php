<?php

declare(strict_types=1);

$databaseConfig = require __DIR__ . '/database.php';

if (!is_array($databaseConfig)) {
    throw new RuntimeException('Database configuration must return an array.');
}

$dsn = sprintf(
    '%s:host=%s;port=%d;dbname=%s;charset=%s',
    $databaseConfig['driver'],
    $databaseConfig['host'],
    $databaseConfig['port'],
    $databaseConfig['database'],
    $databaseConfig['charset']
);

try {
    return new PDO(
        $dsn,
        $databaseConfig['username'],
        $databaseConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('Mais Batik database connection failed (SQLSTATE ' . $exception->getCode() . ').');
    throw new RuntimeException('Database connection failed.', 0, $exception);
}
