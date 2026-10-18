
<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

$timeout = 900; // 15 minutes

if (isset($_SESSION['student_id'])) {
    if (isset($_SESSION['last_activity']) &&
        time() - $_SESSION['last_activity'] > $timeout) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
} elseif (!empty($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me']);

    if (count($parts) === 2) {
        [$selector, $validator] = $parts;

        if (preg_match('/^[a-f0-9]{32}$/', $selector) &&
            preg_match('/^[a-f0-9]{64}$/', $validator)) {

            $stmt = $conn->prepare(
                "SELECT rt.student_id, rt.validator_hash, s.full_name,
                        s.email, s.role
                 FROM remember_tokens rt
                 JOIN students s ON s.student_id = rt.student_id
                 WHERE rt.selector = ? AND rt.expires_at > NOW()"
            );
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user &&
                hash_equals($user['validator_hash'], hash('sha256', $validator))) {
                session_regenerate_id(true);
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM remember_tokens WHERE selector = ?"
                );
                $stmt->bind_param("s", $selector);
                $stmt->execute();
                $stmt->close();

                setcookie('remember_me', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure' => !empty($_SERVER['HTTPS']) &&
                                $_SERVER['HTTPS'] !== 'off'
                ]);
            }
        }
    }
}

function require_login(): void {
    if (!isset($_SESSION['student_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();

    if (($_SESSION['role'] ?? '') !== $role) {
        if (($_SESSION['role'] ?? '') === 'admin') {
            header('Location: admin-dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
}
?>
