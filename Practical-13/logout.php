
<?php
require_once __DIR__ . '/auth.php';

if (!empty($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me']);

    if (count($parts) === 2 &&
        preg_match('/^[a-f0-9]{32}$/', $parts[0])) {
        $selector = $parts[0];

        $stmt = $conn->prepare(
            "DELETE FROM remember_tokens WHERE selector = ?"
        );
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $stmt->close();
    }
}

setcookie('remember_me', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
]);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax'
    ]);
}

session_destroy();

header('Location: login.php');
exit;
?>
