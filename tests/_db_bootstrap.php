<?php
// tests/_db_bootstrap.php

if (getenv('QRCAS_RUN_DB_TESTS') !== '1') {
    echo "[SKIP] QRCAS_RUN_DB_TESTS is not set to '1'\n";
    exit(0);
}

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "[SKIP] .env file not found\n";
    exit(0);
}

$envContent = @file_get_contents($envFile);
if ($envContent === false) {
    echo "[SKIP] Unable to read .env file\n";
    exit(0);
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = '';

foreach (preg_split('/\R/', $envContent) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) {
        continue;
    }
    $sep = strpos($line, '=');
    if ($sep === false) {
        continue;
    }
    $k = trim(substr($line, 0, $sep));
    $v = trim(substr($line, $sep + 1));
    if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[0] === $v[strlen($v) - 1]) {
        $v = substr($v, 1, -1);
    }
    if ($k === 'DB_HOST') {
        $dbHost = $v;
    } elseif ($k === 'DB_USER') {
        $dbUser = $v;
    } elseif ($k === 'DB_PASS') {
        $dbPass = $v;
    } elseif ($k === 'DB_NAME') {
        $dbName = $v;
    }
}

$testConn = null;
try {
    $testConn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
} catch (Throwable $e) {
    echo "[SKIP] Database connection failed: " . $e->getMessage() . "\n";
    exit(0);
}

if (!$testConn) {
    echo "[SKIP] Database connection failed: " . mysqli_connect_error() . "\n";
    exit(0);
}
mysqli_close($testConn);

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/attendance.php';
