
<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container py-5">
    <div class="card shadow mx-auto p-4" style="max-width:700px;">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
        <p>You have successfully logged in as an administrator.</p>
        <p><strong>Role:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
        <a href="logout.php" class="btn btn-primary">Logout</a>
    </div>
</div>
</body>
</html>
