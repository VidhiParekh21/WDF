
<?php
require_once __DIR__ . '/auth.php';

if (
    !isset($_SESSION['student_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['event_csrf'])) {
    $_SESSION['event_csrf'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function uploadEventPoster($file, $dir) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Poster upload failed.');
    }

    if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Poster must be 2 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (
        !isset($allowed[$mime]) ||
        @getimagesize($file['tmp_name']) === false
    ) {
        throw new RuntimeException('Only valid JPG, PNG and WEBP images are allowed.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new RuntimeException('Unable to save poster.');
    }

    return $filename;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$stmt = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

$dir = __DIR__ . '/uploads/event_posters/';

if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    exit('Unable to create upload directory.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPoster = null;

    try {
        if (
            !isset($_POST['csrf_token'], $_SESSION['event_csrf']) ||
            !hash_equals($_SESSION['event_csrf'], $_POST['csrf_token'])
        ) {
            throw new RuntimeException('Invalid request. Refresh the page.');
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $venue = trim($_POST['venue'] ?? '');

        if (
            $title === '' || $description === '' ||
            $eventDate === '' || $venue === ''
        ) {
            throw new RuntimeException('All fields are required.');
        }

        if (mb_strlen($title) > 150 || mb_strlen($venue) > 200) {
            throw new RuntimeException('Title or venue is too long.');
        }

        $date = DateTime::createFromFormat('!Y-m-d', $eventDate);

        if (!$date || $date->format('Y-m-d') !== $eventDate) {
            throw new RuntimeException('Enter a valid event date.');
        }

        $newPoster = uploadEventPoster($_FILES['poster'] ?? null, $dir);
        $poster = $newPoster ?? ($event['poster'] ?? null);

        try {
            $stmt = $conn->prepare(
                'UPDATE events
                 SET title = ?, description = ?, event_date = ?,
                     venue = ?, poster = ?
                 WHERE id = ?'
            );

            $stmt->bind_param(
                'sssssi',
                $title,
                $description,
                $eventDate,
                $venue,
                $poster,
                $id
            );

            $stmt->execute();
            $stmt->close();
        } catch (Throwable $ex) {
            if ($newPoster !== null && is_file($dir . $newPoster)) {
                unlink($dir . $newPoster);
            }
            throw $ex;
        }

        if (
            $newPoster !== null &&
            !empty($event['poster']) &&
            basename($event['poster']) !== $newPoster
        ) {
            $oldPath = $dir . basename($event['poster']);

            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        header('Location: admin_events.php?success=updated');
        exit;

    } catch (Throwable $ex) {
        $error = $ex instanceof RuntimeException
            ? $ex->getMessage()
            : 'Unable to update event. Check your database.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Event - StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:750px">
        <div class="card-body p-4">
            <h3 class="mb-4">Edit Event</h3>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['event_csrf']) ?>">

                <div class="mb-3">
                    <label class="form-label">Event Title</label>
                    <input class="form-control" name="title" maxlength="150"
                           required value="<?= e($_POST['title'] ?? $event['title']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description"
                              rows="4" required><?= e($_POST['description'] ?? $event['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Event Date</label>
                    <input class="form-control" type="date" name="event_date"
                           required value="<?= e($_POST['event_date'] ?? $event['event_date']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Venue</label>
                    <input class="form-control" name="venue" maxlength="200"
                           required value="<?= e($_POST['venue'] ?? $event['venue']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Current Poster</label><br>
                    <?php
                    $current = basename($event['poster'] ?? '');
                    ?>
                    <?php if ($current !== '' && is_file($dir . $current)): ?>
                        <img src="uploads/event_posters/<?= rawurlencode($current) ?>"
                             alt="Current poster"
                             style="max-width:180px;max-height:150px">
                    <?php else: ?>
                        <p class="text-muted">No poster uploaded.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Replace Poster (optional)</label>
                    <input type="file" name="poster" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <small class="text-muted">JPG, PNG or WEBP; maximum 2 MB.</small>
                </div>

                <button class="btn btn-primary" type="submit">Update Event</button>
                <a href="admin_events.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
