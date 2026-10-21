
<?php
require_once __DIR__ . '/auth.php';

if (
    !isset($_SESSION['student_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    exit('Access denied. Only administrators can manage events.');
}

if (empty($_SESSION['event_api_csrf'])) {
    $_SESSION['event_api_csrf'] = bin2hex(random_bytes(32));
}

$csrf = htmlspecialchars(
    $_SESSION['event_api_csrf'],
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Fetch API Event CRUD - StudentHub</title>

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
            border: none;
            border-radius: 12px;
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
    <h2 class="heading fw-bold mb-2">Event Management</h2>

    <p class="text-muted">
        Add, view, update, and delete events without refreshing the page.
    </p>

    <div id="message" class="alert d-none" role="status"
         aria-live="polite"></div>

    <div class="card panel shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 id="formHeading" class="mb-3">Add New Event</h5>

            <form id="eventForm">
                <input type="hidden" id="event_id" value="">

                <div class="mb-3">
                    <label for="event_name" class="form-label">
                        Event Name
                    </label>
                    <input type="text" id="event_name" maxlength="150"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        Description
                    </label>
                    <textarea id="description" class="form-control"
                              rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="event_date" class="form-label">
                            Event Date
                        </label>
                        <input type="date" id="event_date"
                               class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="location" class="form-label">
                            Location
                        </label>
                        <input type="text" id="location" maxlength="150"
                               class="form-control">
                    </div>
                </div>

                <button type="submit" id="saveButton"
                        class="btn btn-primary">
                    Add Event
                </button>

                <button type="button" id="cancelButton"
                        class="btn btn-secondary d-none">
                    Cancel Edit
                </button>
            </form>
        </div>
    </div>

    <div class="card panel shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between
                        align-items-center mb-3 gap-2">
                <h5 class="mb-0">All Events</h5>

                <button type="button" id="refreshButton"
                        class="btn btn-outline-primary btn-sm">
                    Refresh List
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="eventsTable">
                        <tr>
                            <td colspan="6" class="text-center">
                                Loading events...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = 'api/events.php';
const CSRF_TOKEN = <?= json_encode($csrf) ?>;

const eventForm = document.getElementById('eventForm');
const eventsTable = document.getElementById('eventsTable');
const messageBox = document.getElementById('message');
const formHeading = document.getElementById('formHeading');
const saveButton = document.getElementById('saveButton');
const cancelButton = document.getElementById('cancelButton');

let events = [];

function showMessage(message, type = 'success') {
    messageBox.textContent = message;
    messageBox.className = 'alert alert-' + type;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

async function apiRequest(action = null, data = null) {
    const options = {
        method: action ? 'POST' : 'GET',
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    };

    if (action) {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-Token'] = CSRF_TOKEN;
        options.body = JSON.stringify({
            ...data,
            action: action
        });
    }

    const response = await fetch(API_URL, options);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Request failed.');
    }

    return result;
}

async function loadEvents() {
    eventsTable.innerHTML =
        '<tr><td colspan="6" class="text-center">Loading events...</td></tr>';

    try {
        const result = await apiRequest();
        events = result.events;
        renderEvents();
    } catch (error) {
        eventsTable.innerHTML =
            '<tr><td colspan="6" class="text-center text-danger">' +
            escapeHtml(error.message) +
            '</td></tr>';

        showMessage(error.message, 'danger');
    }
}

function renderEvents() {
    if (events.length === 0) {
        eventsTable.innerHTML =
            '<tr><td colspan="6" class="text-center">No events available.</td></tr>';
        return;
    }

    eventsTable.innerHTML = events.map(event => `
        <tr>
            <td>${escapeHtml(event.event_id)}</td>
            <td>${escapeHtml(event.event_name)}</td>
            <td>${escapeHtml(event.description || '')}</td>
            <td>${escapeHtml(event.event_date || '')}</td>
            <td>${escapeHtml(event.location || '')}</td>
            <td>
                <button type="button"
                        class="btn btn-warning btn-sm mb-1"
                        data-action="edit"
                        data-id="${escapeHtml(event.event_id)}">
                    Edit
                </button>

                <button type="button"
                        class="btn btn-danger btn-sm mb-1"
                        data-action="delete"
                        data-id="${escapeHtml(event.event_id)}">
                    Delete
                </button>
            </td>
        </tr>
    `).join('');
}

function resetForm() {
    eventForm.reset();
    document.getElementById('event_id').value = '';
    formHeading.textContent = 'Add New Event';
    saveButton.textContent = 'Add Event';
    cancelButton.classList.add('d-none');
}

eventForm.addEventListener('submit', async function(event) {
    event.preventDefault();

    const eventId = document.getElementById('event_id').value;

    const data = {
        event_id: eventId ? Number(eventId) : null,
        event_name: document.getElementById('event_name').value.trim(),
        description: document.getElementById('description').value.trim(),
        event_date: document.getElementById('event_date').value,
        location: document.getElementById('location').value.trim()
    };

    const action = eventId ? 'update' : 'add';

    saveButton.disabled = true;

    try {
        const result = await apiRequest(action, data);

        showMessage(result.message, 'success');
        resetForm();
        await loadEvents();
    } catch (error) {
        showMessage(error.message, 'danger');
    } finally {
        saveButton.disabled = false;
    }
});

eventsTable.addEventListener('click', async function(event) {
    const button = event.target.closest('button[data-action]');

    if (!button) {
        return;
    }

    const eventId = Number(button.dataset.id);
    const action = button.dataset.action;

    if (action === 'edit') {
        const selectedEvent = events.find(
            item => Number(item.event_id) === eventId
        );

        if (!selectedEvent) {
            showMessage('Event not found. Refresh the list.', 'danger');
            return;
        }

        document.getElementById('event_id').value =
            selectedEvent.event_id;

        document.getElementById('event_name').value =
            selectedEvent.event_name || '';

        document.getElementById('description').value =
            selectedEvent.description || '';

        document.getElementById('event_date').value =
            selectedEvent.event_date || '';

        document.getElementById('location').value =
            selectedEvent.location || '';

        formHeading.textContent = 'Update Event';
        saveButton.textContent = 'Save Changes';
        cancelButton.classList.remove('d-none');

        eventForm.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        return;
    }

    if (action === 'delete') {
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }

        button.disabled = true;

        try {
            const result = await apiRequest('delete', {
                event_id: eventId
            });

            showMessage(result.message, 'success');
            resetForm();
            await loadEvents();
        } catch (error) {
            showMessage(error.message, 'danger');
            button.disabled = false;
        }
    }
});

cancelButton.addEventListener('click', resetForm);

document.getElementById('refreshButton').addEventListener(
    'click',
    loadEvents
);

loadEvents();
</script>

</body>
</html>
