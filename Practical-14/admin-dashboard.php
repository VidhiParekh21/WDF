
<?php
require_once __DIR__ . '/auth.php';

// Allow administrators only.
if (
    !isset($_SESSION['student_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    exit('Access denied. Only administrators can access this dashboard.');
}

$adminId = (int) $_SESSION['student_id'];
$adminName = $_SESSION['full_name'] ?? 'Administrator';
$adminName = htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8');

// Record dashboard access in the audit log.
$action = 'VIEW_DASHBOARD';
$details = 'Admin dashboard opened';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

$log = $conn->prepare(
    'INSERT INTO audit_logs
     (user_id, user_name, action, details, ip_address)
     VALUES (?, ?, ?, ?, ?)'
);

if ($log) {
    $log->bind_param(
        'issss',
        $adminId,
        $_SESSION['full_name'],
        $action,
        $details,
        $ipAddress
    );
    $log->execute();
    $log->close();
}

// Get dashboard statistics.
$studentCount = 0;
$eventCount = 0;
$todayCount = 0;
$latestStudents = [];
$auditLogs = [];

$result = $conn->query('SELECT COUNT(*) AS total FROM students');
if ($result) {
    $studentCount = (int) $result->fetch_assoc()['total'];
}

$result = $conn->query('SELECT COUNT(*) AS total FROM events');
if ($result) {
    $eventCount = (int) $result->fetch_assoc()['total'];
}

$result = $conn->query(
    'SELECT COUNT(*) AS total
     FROM students
     WHERE DATE(created_at) = CURDATE()'
);
if ($result) {
    $todayCount = (int) $result->fetch_assoc()['total'];
}

// Get the five latest student registrations.
$result = $conn->query(
    'SELECT student_id, full_name, email, course, created_at
     FROM students
     ORDER BY created_at DESC
     LIMIT 5'
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestStudents[] = $row;
    }
}

// Get the latest audit log entries.
$result = $conn->query(
    'SELECT user_name, action, details, created_at
     FROM audit_logs
     ORDER BY log_id DESC
     LIMIT 5'
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $auditLogs[] = $row;
    }
}

function h($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .navbar { background: #073c6b; }
        .heading { color: #073c6b; }
        .stat-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }
        .section-card { border: 0; border-radius: 12px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin-dashboard.php">
            StudentHub Admin
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainMenu"
                aria-controls="mainMenu" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_events.php">Manage Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="event-api-crud.php">Fetch API CRUD</a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <span class="nav-link">
                        <?= h($adminName) ?> (Admin)
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm ms-lg-2"
                       href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <h2 class="heading fw-bold">Admin Dashboard</h2>
    <p class="text-muted">
        Welcome, <?= $adminName ?>. View StudentHub statistics and recent activity.
    </p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Registered Students</p>
                    <h2 class="heading fw-bold"><?= $studentCount ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Events</p>
                    <h2 class="heading fw-bold"><?= $eventCount ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="text-muted mb-1">Registrations Today</p>
                    <h2 class="heading fw-bold"><?= $todayCount ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card section-card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="heading fw-bold">Latest Student Registrations</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($latestStudents) > 0): ?>
                        <?php foreach ($latestStudents as $student): ?>
                            <tr>
                                <td><?= h($student['student_id']) ?></td>
                                <td><?= h($student['full_name']) ?></td>
                                <td><?= h($student['email']) ?></td>
                                <td><?= h($student['course']) ?></td>
                                <td><?= h($student['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                No registrations found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card section-card shadow-sm">
        <div class="card-body">
            <h5 class="heading fw-bold">Recent Audit Log</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Date and Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($auditLogs) > 0): ?>
                        <?php foreach ($auditLogs as $entry): ?>
                            <tr>
                                <td><?= h($entry['user_name']) ?></td>
                                <td><?= h($entry['action']) ?></td>
                                <td><?= h($entry['details']) ?></td>
                                <td><?= h($entry['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                No audit records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
