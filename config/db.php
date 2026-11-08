<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $firstRun = !file_exists(DB_PATH);
    if (!is_dir(STORAGE_DIR)) @mkdir(STORAGE_DIR, 0775, true);

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($firstRun) {
        $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
        require_once __DIR__ . '/seed.php';
        seed_database($pdo);
    }
    return $pdo;
}