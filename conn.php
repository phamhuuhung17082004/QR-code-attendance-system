<?php

// Function to parse .env file and return an associative array
function parseEnv(string $filePath): array
{
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        throw new RuntimeException('Unable to read .env file.');
    }

    $lines = preg_split('/\R/', $contents);
    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2 && $value[0] === $value[strlen($value) - 1]
            && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }
        $env[$key] = $value;
    }

    return $env;
}

// Load .env file
$envFilePath = __DIR__ . '/.env';
if (!file_exists($envFilePath)) {
    die('.env file not found');
}
$env = parseEnv($envFilePath);

// Example usage
// define('DB_HOST', $env['DB_HOST'] ?? null);
// define('DB_USER', $env['DB_USER'] ?? null);
// define('DB_PASS', $env['DB_PASS'] ?? null);
// define('DB_NAME', $env['DB_PASS'] ?? null);

// Or you can access values directly from the $env array
date_default_timezone_set('Asia/Ho_Chi_Minh');

try {
    $conn = mysqli_connect(
        $env['DB_HOST'] ?? '',
        $env['DB_USER'] ?? '',
        $env['DB_PASS'] ?? '',
        $env['DB_NAME'] ?? ''
    );
    if ($conn === false) {
        throw new RuntimeException(mysqli_connect_error());
    }
    if (!mysqli_set_charset($conn, 'utf8mb4')) {
        throw new RuntimeException(mysqli_error($conn));
    }
    $tz = date('P');
    if (!$conn->query("SET time_zone = '$tz'")) {
        throw new RuntimeException($conn->error);
    }
} catch (Throwable $e) {
    error_log('[QRCAS] Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
}
