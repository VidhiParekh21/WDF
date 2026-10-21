
<?php
require_once __DIR__ . '/auth.php';

if (
    !isset($_SESSION['student_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    exit('Access denied.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (
    !isset($_POST['csrf_token'], $_SESSION['event_csrf']) ||
    !hash_equals($_SESSION['event_csrf'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Invalid request. Refresh the page and try again.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$stmt = $conn->prepare('SELECT poster FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header('Location: admin_events.php');
    exit;
}

$stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

if (!empty($event['poster'])) {
    $filename = basename($event['poster']);
    $path = __DIR__ . '/uploads/event_posters/' . $filename;

    if (is_file($path)) {
        unlink($path);
    }
}

header('Location: admin_events.php?success=deleted');
exit;
