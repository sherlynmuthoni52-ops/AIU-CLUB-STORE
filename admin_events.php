<?php
/**
 * AIU Club Store - Manage Events
 *
 * Allows administrators to add and delete club events.
 * Event editing is handled by admin_events_edit.php.
 * Club administrators can only manage events for their allocated club.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$db = database();
$user = current_user();
$managedClubIds = managed_club_ids();
$isSuperAdmin = $user['role'] === 'super_admin';
$managedCondition = managed_club_condition();

// -----------------------------------------------------------------------------
// Handle Form Submissions
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save a new or existing event.
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
        $price = filter_input(INPUT_POST, 'ticket_price', FILTER_VALIDATE_FLOAT);

        if (!$clubId || !$title || !$venue || !$date || !$capacity || $capacity < 1 || $price === false || $price < 0) {
            set_message('Complete all required event fields.');
        } elseif (!$isSuperAdmin && !in_array($clubId, $managedClubIds, true)) {
            set_message('You can only manage events for your allocated club.');
        } elseif ($id) {
            // Update an existing event.
            $stmt = $db->prepare('UPDATE events SET club_id=?, title=?, description=?, venue=?, `date`=?, capacity=?, ticket_price=? WHERE id=?');
            $stmt->bind_param('issssidi', $clubId, $title, $description, $venue, $date, $capacity, $price, $id);
            $stmt->execute();
            set_message('Event updated.');
        } else {
            // Insert a new event.
            $stmt = $db->prepare('INSERT INTO events (club_id,title,description,venue,`date`,capacity,ticket_price) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('issssid', $clubId, $title, $description, $venue, $date, $capacity, $price);
            $stmt->execute();
            set_message('Event saved.');
        }
    }

    // Delete an event.
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $event = $db->query('SELECT club_id FROM events WHERE id=' . (int) $id)->fetch_assoc();
            if ($event && ($isSuperAdmin || in_array((int) $event['club_id'], $managedClubIds, true))) {
                try {
                    $db->query('DELETE FROM events WHERE id=' . (int) $id);
                    set_message('Event deleted.');
                } catch (Throwable $e) {
                    set_message('This event has tickets and cannot be deleted.');
                }
            } else {
                set_message('You can only delete events from your allocated club.');
            }
        }
    }

    header('Location: admin_events.php');
    exit;
}

// -----------------------------------------------------------------------------
// Prepare Data for Display
// -----------------------------------------------------------------------------

$clubsQuery = 'SELECT id, name FROM clubs';
if (!$isSuperAdmin) {
    $clubsQuery .= ' WHERE id IN (' . implode(',', array_map('intval', $managedClubIds)) . ')';
}
$clubsQuery .= ' ORDER BY name';
$clubs = $db->query($clubsQuery);

$eventsQuery = 'SELECT events.*, clubs.name AS club_name FROM events JOIN clubs ON clubs.id = events.club_id WHERE ' . $managedCondition . ' ORDER BY `date` DESC';
$events = $db->query($eventsQuery);

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Manage Events | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="button dashboard-btn" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Add Event</h2>

    <!-- Add Event Form -->
    <form method="post" class="form-card">
        <input type="hidden" name="action" value="save">
        <label>
            Club
            <select name="club_id" required <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                <?php while ($club = $clubs->fetch_assoc()) { ?>
                    <option value="<?php echo $club['id']; ?>">
                        <?php echo htmlspecialchars($club['name']); ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (!$isSuperAdmin) { ?>
                <input type="hidden" name="club_id" value="<?php echo (int) ($managedClubIds[0] ?? 0); ?>">
            <?php } ?>
        </label>
        <label>
            Title
            <input name="title" required>
        </label>
        <label>
            Description
            <textarea name="description"></textarea>
        </label>
        <label>
            Venue
            <input name="venue" required>
        </label>
        <label>
            Date and time
            <input name="date" type="datetime-local" required>
        </label>
        <label>
            Capacity
            <input name="capacity" type="number" min="1" required>
        </label>
        <label>
            Ticket price
            <input name="ticket_price" type="number" min="0" step="0.01" value="0" required>
        </label>
        <button class="button">Add Event</button>
    </form>

    <!-- Events List -->
    <h2>Events</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Capacity</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($event = $events->fetch_assoc()) { ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($event['title']); ?>
                        <br>
                        <small><?php echo htmlspecialchars($event['club_name']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($event['date']); ?></td>
                    <td><?php echo htmlspecialchars($event['venue']); ?></td>
                    <td><?php echo $event['capacity']; ?></td>
                    <td>
                        <a class="text-link" href="admin_events_edit.php?id=<?php echo $event['id']; ?>">Edit</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this event?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                            <button class="text-link">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
