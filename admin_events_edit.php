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
<main class="container section admin-page">
    <p>
        <a class="text-link" href="admin_events.php"><i class="fas fa-arrow-left"></i> Events</a>
    </p>
    <h2>Edit Event</h2>

    <!-- Edit Event Form -->
    <form method="post" enctype="multipart/form-data" class="form-card">
        <div class="field">
            <select id="club_id" name="club_id" required <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                <?php while ($club = $clubs->fetch_assoc()) { ?>
                    <option value="<?php echo $club['id']; ?>" <?php echo $event['club_id'] == $club['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($club['name']); ?>
                    </option>
                <?php } ?>
            </select>
            <label for="club_id">Club</label>
            <?php if (!$isSuperAdmin) { ?>
                <input type="hidden" name="club_id" value="<?php echo (int) $event['club_id']; ?>">
            <?php } ?>
        </div>
        <div class="field">
            <input id="title" name="title" placeholder=" " value="<?php echo htmlspecialchars($event['title']); ?>" required>
            <label for="title">Title</label>
        </div>
        <div class="field">
            <textarea id="description" name="description" placeholder=" "><?php echo htmlspecialchars($event['description']); ?></textarea>
            <label for="description">Description</label>
        </div>
        <div class="field">
            <input id="venue" name="venue" placeholder=" " value="<?php echo htmlspecialchars($event['venue']); ?>" required>
            <label for="venue">Venue</label>
        </div>
        <div class="field">
            <input id="date" name="date" type="datetime-local" value="<?php echo date('Y-m-d\TH:i', strtotime($event['date'])); ?>" required>
            <label for="date">Date and time</label>
        </div>
        <div class="field">
            <input id="capacity" name="capacity" type="number" min="1" placeholder=" " value="<?php echo $event['capacity']; ?>" required>
            <label for="capacity">Capacity</label>
        </div>
        <div class="field">
            <input id="ticket_price" name="ticket_price" type="number" min="0" step="0.01" placeholder=" " value="<?php echo $event['ticket_price']; ?>" required>
            <label for="ticket_price">Ticket price</label>
        </div>
        <div class="field">
            <?php if ($event['poster'] ?? null): ?>
                <div class="image-preview">
                    <img src="uploads/<?php echo htmlspecialchars($event['poster']); ?>" alt="Current poster">
                </div>
                <div class="checkbox-field">
                    <input type="checkbox" name="remove_poster" id="remove_poster" value="1">
                    <label for="remove_poster" class="checkbox-label">Remove current poster</label>
                </div>
            <?php endif; ?>
            <input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp">
            <label for="poster">Change poster</label>
        </div>
        <div class="form-actions">
            <a class="button" href="admin_events.php"><i class="fas fa-times"></i> Cancel</a>
            <button class="button button-primary" data-loading="Saving changes...">Save Changes</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
