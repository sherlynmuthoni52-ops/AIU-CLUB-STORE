<?php
/**
 * AIU Club Store - Manage Clubs
 *
 * Allows a super administrator to add, edit, and delete clubs.
 * Regular club administrators cannot access this page.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Only logged-in users may proceed.
require_login();

// Restrict access to super administrators only.
if (current_user()['role'] !== 'super_admin') {
    http_response_code(403);
    exit('Only a super administrator can manage clubs.');
}

// -----------------------------------------------------------------------------
// Database Connection
// -----------------------------------------------------------------------------

$db = database();

// -----------------------------------------------------------------------------
// Handle Form Submissions
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Save a new or existing club.
    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$name) {
            set_message('Club name is required.');
        } elseif ($id) {
            // Update an existing club.
            $stmt = $db->prepare('UPDATE clubs SET name=?, description=? WHERE id=?');
            $stmt->bind_param('ssi', $name, $description, $id);
            $stmt->execute();
            set_message('Club updated.');
        } else {
            // Insert a new club.
            $stmt = $db->prepare('INSERT INTO clubs (name, description) VALUES (?,?)');
            $stmt->bind_param('ss', $name, $description);
            $stmt->execute();
            set_message('Club added.');
        }
    }

    // Delete a club.
    if ($action === 'delete' && $id) {
        try {
            $db->query('DELETE FROM clubs WHERE id=' . (int) $id);
            set_message('Club deleted.');
        } catch (Throwable $e) {
            set_message('A club with products or events cannot be deleted.');
        }
    }

    header('Location: admin_clubs.php');
    exit;
}

// -----------------------------------------------------------------------------
// Prepare Data for Display
// -----------------------------------------------------------------------------

// Check if we are editing an existing club.
$editing = null;
if ($id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT)) {
    $editing = $db->query('SELECT * FROM clubs WHERE id=' . (int) $id)->fetch_assoc();
}

// Fetch all clubs for the list table.
$clubs = $db->query('SELECT * FROM clubs ORDER BY name');

// Fetch club admin counts.
$adminCounts = [];
$result = $db->query('SELECT club_id, COUNT(*) AS count FROM club_admins GROUP BY club_id');
while ($row = $result->fetch_assoc()) {
    $adminCounts[$row['club_id']] = (int) $row['count'];
}

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Manage Clubs | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2><?php echo $editing ? 'Edit Club' : 'Add Club'; ?></h2>

    <!-- Add / Edit Club Form -->
    <form method="post" class="form-card">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">
        <label>
            Club name
            <input name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required>
        </label>
        <label>
            Description
            <textarea name="description"><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>
        </label>
        <button class="button">Save Club</button>
    </form>

    <!-- Clubs List -->
    <h2>Clubs</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Admins</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($club = $clubs->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($club['name']); ?></td>
                    <td><?php echo htmlspecialchars($club['description']); ?></td>
                    <td>
                        <?php echo $adminCounts[$club['id']] ?? 0; ?>
                        <a class="text-link" href="admin_club_allocations.php">Manage</a>
                    </td>
                    <td>
                        <a class="text-link" href="admin_clubs.php?edit=<?php echo $club['id']; ?>">Edit</a>
                        <form method="post">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $club['id']; ?>">
                            <button class="text-link" onclick="return confirm('Delete this club?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
