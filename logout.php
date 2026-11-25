<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the logout button to sign out.');
}

verifyCsrf();
$_SESSION = [];

$settings = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $settings['path'],
    'domain' => $settings['domain'],
    'secure' => $settings['secure'],
    'httponly' => $settings['httponly'],
    'samesite' => $settings['samesite'] ?? 'Lax',
]);

session_destroy();
header('Location: login.php', true, 303);
exit;

