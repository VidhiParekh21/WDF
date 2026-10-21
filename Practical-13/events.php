
<?php
require_once dirname(__DIR__) . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function sendJson(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (
    !isset($_SESSION['student_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    sendJson(403, [
        'success' => false,
        'message' => 'Only administrators can manage events.'
    ]);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $result = $conn->query(
            'SELECT event_id, event_name, description,
                    event_date, location
             FROM events
             ORDER BY event_id DESC'
        );

        $events = [];

        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        sendJson(200, [
            'success' => true,
            'events' => $events
        ]);
    } catch (Throwable $e) {
        sendJson(500, [
            'success' => false,
            'message' => 'Unable to load events.'
        ]);
    }
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    sendJson(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    empty($_SESSION['event_api_csrf']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['event_api_csrf'], $csrfToken)
) {
    sendJson(403, [
        'success' => false,
        'message' => 'Invalid security token. Refresh the page and try again.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    sendJson(400, [
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);
}

$action = $input['action'] ?? '';

try {
    if ($action === 'add' || $action === 'update') {
        $eventName = trim($input['event_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $eventDate = trim($input['event_date'] ?? '');
        $location = trim($input['location'] ?? '');

        if ($eventName === '') {
            sendJson(422, [
                'success' => false,
                'message' => 'Event name is required.'
            ]);
        }

        if (mb_strlen($eventName) > 150 ||
            mb_strlen($location) > 150) {
            sendJson(422, [
                'success' => false,
                'message' => 'Event name or location is too long.'
            ]);
        }

        if ($eventDate !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $eventDate);

            if (!$date || $date->format('Y-m-d') !== $eventDate) {
                sendJson(422, [
                    'success' => false,
                    'message' => 'Enter a valid event date.'
                ]);
            }
        } else {
            $eventDate = null;
        }

        $description = $description === '' ? null : $description;
        $location = $location === '' ? null : $location;

        if ($action === 'add') {
            $stmt = $conn->prepare(
                'INSERT INTO events
                 (event_name, description, event_date, location)
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'ssss',
                $eventName,
                $description,
                $eventDate,
                $location
            );

            $stmt->execute();
            $newId = $conn->insert_id;
            $stmt->close();

            sendJson(201, [
                'success' => true,
                'message' => 'Event added successfully.',
                'event_id' => $newId
            ]);
        }

        $eventId = filter_var(
            $input['event_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$eventId || $eventId < 1) {
            sendJson(422, [
                'success' => false,
                'message' => 'A valid event ID is required.'
            ]);
        }

        $stmt = $conn->prepare(
            'UPDATE events
             SET event_name = ?, description = ?,
                 event_date = ?, location = ?
             WHERE event_id = ?'
        );

        $stmt->bind_param(
            'ssssi',
            $eventName,
            $description,
            $eventDate,
            $location,
            $eventId
        );

        $stmt->execute();
        $stmt->close();

        $check = $conn->prepare(
            'SELECT event_id FROM events WHERE event_id = ?'
        );
        $check->bind_param('i', $eventId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            sendJson(404, [
                'success' => false,
                'message' => 'Event not found.'
            ]);
        }

        sendJson(200, [
            'success' => true,
            'message' => 'Event updated successfully.'
        ]);
    }

    if ($action === 'delete') {
        $eventId = filter_var(
            $input['event_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$eventId || $eventId < 1) {
            sendJson(422, [
                'success' => false,
                'message' => 'A valid event ID is required.'
            ]);
        }

        $stmt = $conn->prepare(
            'DELETE FROM events WHERE event_id = ?'
        );
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            sendJson(404, [
                'success' => false,
                'message' => 'Event not found.'
            ]);
        }

        sendJson(200, [
            'success' => true,
            'message' => 'Event deleted successfully.'
        ]);
    }

    sendJson(400, [
        'success' => false,
        'message' => 'Unknown action.'
    ]);

} catch (Throwable $e) {
    sendJson(500, [
        'success' => false,
        'message' => 'A database error occurred. Please check the events table.'
    ]);
}
