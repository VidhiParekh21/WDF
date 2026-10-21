
<?php
require_once __DIR__ . '/auth.php';

if (
    !isset($_SESSION['student_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    exit('Access denied. Only administrators can manage events.');
}

if (empty($_SESSION['event_csrf'])) {
    $_SESSION['event_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

$uploadDir = __DIR__ . '/uploads/event_posters/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        exit('Unable to create poster upload directory.');
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function uploadEventPoster($file, string $uploadDir): ?string {
    if (!$file || !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Poster upload failed.');
    }

    if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Poster size must not exceed 2 MB.');
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
        throw new RuntimeException(
            'Only valid JPG, PNG and WEBP images are allowed.'
        );
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        throw new RuntimeException('Unable to save the poster.');
    }

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPoster = null;

    try {
        if (
            !isset($_POST['csrf_token'], $_SESSION['event_csrf']) ||
            !hash_equals($_SESSION['event_csrf'], $_POST['csrf_token'])
        ) {
            throw new RuntimeException(
                'Invalid request. Refresh the page and try again.'
            );
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $venue = trim($_POST['venue'] ?? '');

        if (
            $title === '' || $description === '' ||
            $eventDate === '' || $venue === ''
        ) {
            throw new RuntimeException(
                'Please fill in all required fields.'
            );
        }

        if (mb_strlen($title) > 150 || mb_strlen($venue) > 150) {
            throw new RuntimeException(
                'Event name must be at most 150 characters and location at most 150 characters.'
            );
        }

        $date = DateTime::createFromFormat('!Y-m-d', $eventDate);

        if (!$date || $date->format('Y-m-d') !== $eventDate) {
            throw new RuntimeException('Please enter a valid event date.');
        }

        $newPoster = uploadEventPoster(
            $_FILES['poster'] ?? null,
            $uploadDir
        );

        try {
            $stmt = $conn->prepare(
                'INSERT INTO events
                (event_name, description, event_date, location, poster)
                VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'sssss',
                $title,
                $description,
                $eventDate,
                $venue,
                $newPoster
            );

            $stmt->execute();
            $stmt->close();

        } catch (Throwable $ex) {
            if (
                $newPoster !== null &&
                is_file($uploadDir . $newPoster)
            ) {
                unlink($uploadDir . $newPoster);
            }

            throw $ex;
        }

        header('Location: admin_events.php?success=added');
        exit;

    } catch (Throwable $ex) {
        $error = $ex instanceof RuntimeException
            ? $ex->getMessage()
            : 'Unable to save the event. Check that the events table contains the poster column.';
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = 'Event added successfully.';
    } elseif ($_GET['success'] === 'updated') {
        $message = 'Event updated successfully.';
    } elseif ($_GET['success'] === 'deleted') {
        $message = 'Event deleted successfully.';
    }
}

try {
    $result = $conn->query(
        'SELECT event_id, event_name, description, event_date, location, poster
         FROM events
         ORDER BY event_date DESC, event_id DESC'
    );
} catch (Throwable $ex) {
    exit(
        'Unable to load events. Check that the events table has a poster column.'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Management - StudentHub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4f7fb;
        }

        .navbar {
            background: #073c6b;
        }

        .heading {
            color: #073c6b;
        }

        .panel {
            border: 0;
            border-radius: 12px;
        }

        .poster {
            width: 85px;
            height: 65px;
            object-fit: cover;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">StudentHub</a>

        <a class="btn btn-light btn-sm" href="admin-dashboard.php">
            Admin Dashboard
        </a>
    </div>
</nav>

<div class="container py-4">
    <h2 class="heading fw-bold mb-4">Event Management</h2>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card panel shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Add New Event</h5>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['event_csrf']) ?>">

                <div class="mb-3">
                    <label class="form-label">Event Title</label>
                    <input type="text" name="title" maxlength="150"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"
                              rows="3" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Event Date</label>
                        <input type="date" name="event_date"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location / Venue</label>
                        <input type="text" name="venue" maxlength="150"
                               class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Event Poster (optional)</label>
                    <input type="file" name="poster" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <small class="text-muted">
                        JPG, PNG or WEBP. Maximum size: 2 MB.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    Add Event
                </button>
            </form>
        </div>
    </div>

    <div class="card panel shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">All Events</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Poster</th>
                            <th>Event Details</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                No events available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($event = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$event['event_id'] ?></td>

                                <td>
                                    <?php
                                    $poster = basename($event['poster'] ?? '');
                                    $posterPath = $uploadDir . $poster;
                                    ?>

                                    <?php if (
                                        $poster !== '' &&
                                        is_file($posterPath)
                                    ): ?>
                                        <img
                                            class="poster rounded"
                                            src="uploads/event_posters/<?= rawurlencode($poster) ?>"
                                            alt="Event poster">
                                    <?php else: ?>
                                        <span class="text-muted">No poster</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= e($event['event_name']) ?>
                                    </strong>

                                    <div class="small text-muted">
                                        <?= e($event['description']) ?>
                                    </div>
                                </td>

                                <td><?= e($event['event_date']) ?></td>
                                <td><?= e($event['location']) ?></td>

                                <td>
                                    <a class="btn btn-sm btn-warning mb-1"
                                       href="edit_event.php?id=<?= (int)$event['event_id'] ?>">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="delete_event.php"
                                          onsubmit="return confirm('Delete this event?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION['event_csrf']) ?>">

                                        <input type="hidden" name="id"
                                               value="<?= (int)$event['event_id'] ?>">

                                        <button type="submit"
                                                class="btn btn-sm btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
