
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_role('admin');

$uploadDir = __DIR__ . '/uploads/events/';
$uploadUrl = 'uploads/events/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$error = '';
$maxSize = 2 * 1024 * 1024;
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

function removePoster($filename, $uploadDir) {
    if ($filename && basename($filename) === $filename) {
        $path = $uploadDir . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $category = trim($_POST['category'] ?? '');

        if ($title === '' || $description === '' ||
            $eventDate === '' || $category === '') {
            $error = 'Please fill in all fields.';
        } else {
            $dateObject = DateTime::createFromFormat('!Y-m-d', $eventDate);
            if (!$dateObject || $dateObject->format('Y-m-d') !== $eventDate) {
                $error = 'Please enter a valid event date.';
            }
        }

        $oldPoster = '';
        if (!$error && $id > 0) {
            $stmt = $conn->prepare(
                'SELECT poster FROM events WHERE event_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$existing) {
                $error = 'Event not found.';
            } else {
                $oldPoster = $existing['poster'] ?? '';
            }
        }

        $newPoster = '';
        $hasNewPoster = isset($_FILES['poster']) &&
            $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE;

        if (!$error && $hasNewPoster) {
            $file = $_FILES['poster'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Poster upload failed.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Poster must be 2 MB or smaller.';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $error = 'Invalid uploaded file.';
            } else {
                $imageInfo = @getimagesize($file['tmp_name']);
                $mime = '';

                if (class_exists('finfo')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                }

                if (!$imageInfo || !isset($allowedTypes[$mime]) ||
                    $imageInfo['mime'] !== $mime) {
                    $error = 'Only JPG, PNG, and WEBP image posters are allowed.';
                } else {
                    $newPoster = bin2hex(random_bytes(16)) .
                        '.' . $allowedTypes[$mime];

                    if (!move_uploaded_file(
                        $file['tmp_name'],
                        $uploadDir . $newPoster
                    )) {
                        $error = 'Could not save the uploaded poster.';
                    }
                }
            }
        }

        if (!$error) {
            $poster = $hasNewPoster ? $newPoster : $oldPoster;

            if ($id > 0) {
                $stmt = $conn->prepare(
                    'UPDATE events SET title=?, description=?, event_date=?, category=?, poster=? WHERE event_id=?'
                );
                $stmt->bind_param(
                    'sssssi',
                    $title, $description, $eventDate, $category, $poster, $id
                );
                $success = $stmt->execute();
                $stmt->close();

                if ($success) {
                    if ($hasNewPoster) {
                        removePoster($oldPoster, $uploadDir);
                    }
                    header('Location: events.php?status=updated');
                    exit;
                }
                if ($hasNewPoster) {
                    removePoster($newPoster, $uploadDir);
                }
                $error = 'Could not update the event.';
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO events (title, description, event_date, category, poster) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'sssss',
                    $title, $description, $eventDate, $category, $poster
                );
                $success = $stmt->execute();
                $stmt->close();

                if ($success) {
                    header('Location: events.php?status=added');
                    exit;
                }
                if ($hasNewPoster) {
                    removePoster($newPoster, $uploadDir);
                }
                $error = 'Could not add the event.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['event_id'] ?? 0);

        $stmt = $conn->prepare('SELECT poster FROM events WHERE event_id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($event) {
            $stmt = $conn->prepare('DELETE FROM events WHERE event_id=?');
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                removePoster($event['poster'] ?? '', $uploadDir);
                header('Location: events.php?status=deleted');
                exit;
            }
        }
        $error = 'Could not delete the event.';
    }
}

if (isset($_GET['status'])) {
    $messages = [
        'added' => 'Event added successfully.',
        'updated' => 'Event updated successfully.',
        'deleted' => 'Event deleted successfully.'
    ];
    $message = $messages[$_GET['status']] ?? '';
}

$editEvent = [
    'event_id' => 0,
    'title' => '',
    'description' => '',
    'event_date' => '',
    'category' => '',
    'poster' => ''
];

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM events WHERE event_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = $result->fetch_assoc();
    $stmt->close();

    if ($found) {
        $editEvent = $found;
    } else {
        $error = 'Event not found.';
    }
}

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$sql = 'SELECT * FROM events WHERE 1=1';
$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (title LIKE ? OR description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($categoryFilter !== '') {
    $sql .= ' AND category = ?';
    $params[] = $categoryFilter;
    $types .= 's';
}

$sql .= ' ORDER BY event_date DESC, event_id DESC';
$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$events = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>StudentHub - Event Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f2f6fa;color:#073c6b}
.container{max-width:1100px}
.card{border:0;border-radius:12px}
h2,h3{color:#073c6b}
.poster{width:100px;height:70px;object-fit:cover;border-radius:6px}
</style>
</head>
<body>
<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2>Event Management</h2>
<a href="admin-dashboard.php" class="btn btn-outline-primary">Dashboard</a>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm p-4 mb-4">
<h3><?= $editEvent['event_id'] ? 'Update Event' : 'Add Event' ?></h3>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="save">
<input type="hidden" name="event_id" value="<?= (int)$editEvent['event_id'] ?>">

<div class="mb-3">
<label class="form-label">Event Title</label>
<input type="text" name="title" class="form-control" maxlength="150" required value="<?= htmlspecialchars($editEvent['title']) ?>">
</div>

<div class="mb-3">
<label class="form-label">Description</label>
<textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($editEvent['description']) ?></textarea>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Event Date</label>
<input type="date" name="event_date" class="form-control" required value="<?= htmlspecialchars($editEvent['event_date']) ?>">
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Category</label>
<select name="category" class="form-select" required>
<option value="">Select category</option>
<?php foreach (['Academic','Event','General','Workshop','Technical'] as $cat): ?>
<option value="<?= $cat ?>" <?= $editEvent['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="mb-3">
<label class="form-label">Event Poster (JPG, PNG, WEBP; maximum 2 MB)</label>
<input type="file" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
</div>

<?php if ($editEvent['poster'] && basename($editEvent['poster']) === $editEvent['poster']): ?>
<p>Current poster:</p>
<img class="poster mb-3" src="<?= $uploadUrl . rawurlencode($editEvent['poster']) ?>" alt="Current event poster">
<?php endif; ?>

<button type="submit" class="btn btn-primary"><?= $editEvent['event_id'] ? 'Update Event' : 'Add Event' ?></button>
<a href="events.php" class="btn btn-secondary">Clear</a>
</form>
</div>

<div class="card shadow-sm p-4">
<h3 class="mb-3">View Events</h3>
<form method="GET" class="row g-2 mb-4">
<div class="col-md-6">
<input type="text" name="search" class="form-control" placeholder="Search title or description" value="<?= htmlspecialchars($search) ?>">
</div>
<div class="col-md-4">
<select name="category" class="form-select">
<option value="">All Categories</option>
<?php foreach (['Academic','Event','General','Workshop','Technical'] as $cat): ?>
<option value="<?= $cat ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-2">
<button class="btn btn-primary w-100">Search</button>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
<thead class="table-primary">
<tr><th>ID</th><th>Poster</th><th>Title</th><th>Date</th><th>Category</th><th>Description</th><th>Actions</th></tr>
</thead>
<tbody>
<?php if ($events->num_rows === 0): ?>
<tr><td colspan="7" class="text-center">No events found.</td></tr>
<?php else: ?>
<?php while ($event = $events->fetch_assoc()): ?>
<tr>
<td><?= (int)$event['event_id'] ?></td>
<td>
<?php if ($event['poster'] && basename($event['poster']) === $event['poster']): ?>
<img class="poster" src="<?= $uploadUrl . rawurlencode($event['poster']) ?>" alt="Event poster">
<?php else: ?>No poster<?php endif; ?>
</td>
<td><?= htmlspecialchars($event['title']) ?></td>
<td><?= htmlspecialchars($event['event_date']) ?></td>
<td><?= htmlspecialchars($event['category']) ?></td>
<td><?= htmlspecialchars($event['description']) ?></td>
<td>
<a class="btn btn-sm btn-warning mb-1" href="events.php?edit=<?= (int)$event['event_id'] ?>">Edit</a>
<form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>">
<button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
</body>
</html>
<?php $stmt->close(); ?>
