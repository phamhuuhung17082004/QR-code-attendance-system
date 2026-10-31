<?php
/**
 * Autoloader for lbuchs/WebAuthn v2.2.0 (MIT License)
 * Source: https://github.com/lbuchs/WebAuthn
 */
spl_autoload_register(function ($class) {
    $prefix = 'lbuchs\\WebAuthn\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

