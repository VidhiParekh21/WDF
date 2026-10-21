
<?php
require_once __DIR__ . '/auth.php';

if (isset($_SESSION['student_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT student_id, full_name, email, password, role
             FROM students WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            if (isset($_POST['remember'])) {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $validatorHash = hash('sha256', $validator);
                $expires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
                $studentId = (int)$user['student_id'];

                $stmt = $conn->prepare(
                    "INSERT INTO remember_tokens
                     (student_id, selector, validator_hash, expires_at)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "isss", $studentId, $selector, $validatorHash, $expires
                );
                $stmt->execute();
                $stmt->close();

                setcookie('remember_me', $selector . ':' . $validator, [
                    'expires' => time() + 30 * 24 * 60 * 60,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure' => !empty($_SERVER['HTTPS']) &&
                                $_SERVER['HTTPS'] !== 'off'
                ]);
            }

            if ($user['role'] === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container py-5">
    <div class="card shadow mx-auto p-4" style="max-width:450px;">
        <div class="text-center mb-3">
            <img src="studenthub_logo.png" alt="StudentHub Logo" style="max-width:150px;">
            <h2 class="mt-3">StudentHub Login</h2>
        </div>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning">Your session expired. Please log in again.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember Me for 30 days</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <p class="text-center mt-3 mb-0">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</div>
</body>
</html>
