<?php
/**
 * AIU Club Store - Edit Event
 *
 * Allows administrators to update an existing event's details.
 * Redirects back to the events list if the event is not found.
 * Club administrators can only edit events for their allocated club.
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

// -----------------------------------------------------------------------------
// Load Event Data
// -----------------------------------------------------------------------------

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$event = $id ? $db->query('SELECT * FROM events WHERE id=' . (int) $id)->fetch_assoc() : null;

if (!$event) {
    set_message('Event not found.');
    header('Location: admin_events.php');
    exit;
}

if (!$isSuperAdmin && !in_array((int) $event['club_id'], $managedClubIds, true)) {
    set_message('You can only edit events for your allocated club.');
    header('Location: admin_events.php');
    exit;
}

// -----------------------------------------------------------------------------
// Handle Form Submission
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'ticket_price', FILTER_VALIDATE_FLOAT);

    if (!$clubId || !$title || !$venue || !$date || !$capacity || $capacity < 1 || $price === false || $price < 0) {
        set_message('Complete all required fields.');
    } elseif (!$isSuperAdmin && !in_array($clubId, $managedClubIds, true)) {
        set_message('You can only manage events for your allocated club.');
    } else {
        $stmt = $db->prepare('UPDATE events SET club_id=?, title=?, description=?, venue=?, `date`=?, capacity=?, ticket_price=? WHERE id=?');
        $stmt->bind_param('issssidi', $clubId, $title, $description, $venue, $date, $capacity, $price, $id);
        $stmt->execute();
        set_message('Event updated.');
        header('Location: admin_events.php');
        exit;
    }
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

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Edit Event | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin_events.php">&larr; Events</a>
    </p>
    <h2>Edit Event</h2>

    <!-- Edit Event Form -->
    <form method="post" class="form-card">
        <label>
            Club
            <select name="club_id" required <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                <?php while ($club = $clubs->fetch_assoc()) { ?>
                    <option value="<?php echo $club['id']; ?>" <?php echo $event['club_id'] == $club['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($club['name']); ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (!$isSuperAdmin) { ?>
                <input type="hidden" name="club_id" value="<?php echo (int) $event['club_id']; ?>">
            <?php } ?>
        </label>
        <label>
            Title
            <input name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
        </label>
        <label>
            Description
            <textarea name="description"><?php echo htmlspecialchars($event['description']); ?></textarea>
        </label>
        <label>
            Venue
            <input name="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required>
        </label>
        <label>
            Date and time
            <input name="date" type="datetime-local" value="<?php echo date('Y-m-d\TH:i', strtotime($event['date'])); ?>" required>
        </label>
        <label>
            Capacity
            <input name="capacity" type="number" min="1" value="<?php echo $event['capacity']; ?>" required>
        </label>
        <label>
            Ticket price
            <input name="ticket_price" type="number" min="0" step="0.01" value="<?php echo $event['ticket_price']; ?>" required>
        </label>
        <button class="button">Save Changes</button>
    </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
