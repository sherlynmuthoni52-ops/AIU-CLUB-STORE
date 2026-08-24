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
    $removePoster = $_POST['remove_poster'] ?? '0';

    $posterError = null;
    $poster = $event['poster'] ?? null;

    if (!$clubId || !$title || !$venue || !$date || !$capacity || $capacity < 1 || $price === false || $price < 0) {
        set_message('Complete all required fields.');
    } elseif (!$isSuperAdmin && !in_array($clubId, $managedClubIds, true)) {
        set_message('You can only manage events for your allocated club.');
    } else {
        $stmt = $db->prepare('UPDATE events SET club_id=?, title=?, description=?, venue=?, `date`=?, capacity=?, ticket_price=? WHERE id=?');
        $stmt->bind_param('issssidi', $clubId, $title, $description, $venue, $date, $capacity, $price, $id);
        $stmt->execute();

        if ($removePoster === '1' && $poster) {
            $filePath = __DIR__ . '/uploads/' . $poster;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $db->query('UPDATE events SET poster=NULL WHERE id=' . (int) $id);
        } else {
            $file = $_FILES['poster'] ?? null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if ($file['size'] > 2 * 1024 * 1024) {
                    $posterError = 'Poster must be 2 MB or smaller.';
                } else {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    if (!isset($allowedTypes[$mime])) {
                        $posterError = 'Only JPG, PNG, and WebP image files are allowed.';
                    } else {
                        $folder = __DIR__ . '/uploads';
                        if (!is_dir($folder)) {
                            mkdir($folder, 0755, true);
                        }
                        $filename = 'event-' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mime];
                        $destinationPath = $folder . '/' . $filename;
                        if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
                            if ($poster) {
                                $oldPath = __DIR__ . '/uploads/' . $poster;
                                if (file_exists($oldPath)) {
                                    unlink($oldPath);
                                }
                            }
                            $stmt2 = $db->prepare('UPDATE events SET poster=? WHERE id=?');
                            $stmt2->bind_param('si', $filename, $id);
                            $stmt2->execute();
                        } else {
                            $posterError = 'The poster could not be saved.';
                        }
                    }
                }
            }
        }

        if ($posterError) {
            set_message($posterError);
        } else {
            set_message('Event updated.');
            header('Location: admin_events.php');
            exit;
        }
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
    <form method="post" enctype="multipart/form-data" class="form-card">
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
        <label>
            Event poster
            <?php if ($event['poster'] ?? null): ?>
                <div class="image-preview">
                    <img src="uploads/<?php echo htmlspecialchars($event['poster']); ?>" alt="Current poster">
                </div>
                <label style="display:block; margin-top:8px;">
                    <input type="checkbox" name="remove_poster" value="1"> Remove current poster
                </label>
            <?php endif; ?>
            <input name="poster" type="file" accept="image/jpeg,image/png,image/webp">
        </label>
        <button class="button">Save Changes</button>
    </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
