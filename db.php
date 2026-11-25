<?php
declare(strict_types=1);

session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
]);

try {
    $password = file_get_contents('/etc/fitness-tracker-db-password');

    if ($password === false) {
        throw new RuntimeException('Cannot read database credentials.');
    }

    $pdo = new PDO(
        'mysql:host=localhost;dbname=fitness_tracker;charset=utf8mb4',
        'fitness_app',
        rtrim($password, "\r\n"),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit('The database is temporarily unavailable.');
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token)
        || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Your form expired. Reload the page and try again.');
    }
}
