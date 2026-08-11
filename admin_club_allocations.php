<?php
/**
 * AIU Club Store - Club Allocations
 *
 * Allows a super administrator to allocate clubs to club admins.
 * Shows which admins are allocated, which clubs are unallocated,
 * and provides allocation management forms.
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
    exit('Only a super administrator can manage club allocations.');
}

$db = database();

// -----------------------------------------------------------------------------
// Helper: Check if a table exists
// -----------------------------------------------------------------------------

function table_exists(mysqli $db, string $tableName): bool
{
    $stmt = $db->prepare('SHOW TABLES LIKE ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}

// -----------------------------------------------------------------------------
// Handle Form Submissions
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);

    if ($action === 'allocate' && $userId && $clubId) {
        if (!table_exists($db, 'club_admins')) {
            set_message('The club_admins table does not exist. Please import the updated database.sql.');
        } else {
            // Verify the user is a club_admin.
            $user = $db->query('SELECT id, role FROM users WHERE id=' . (int) $userId)->fetch_assoc();
            if ($user && $user['role'] === 'club_admin') {
                $stmt = $db->prepare('INSERT IGNORE INTO club_admins (user_id, club_id) VALUES (?, ?)');
                if ($stmt) {
                    $stmt->bind_param('ii', $userId, $clubId);
                    $stmt->execute();
                    set_message('Club allocated to admin.');
                } else {
                    set_message('Failed to prepare allocation query. Please import the updated database.sql.');
                }
            } else {
                set_message('Selected user is not a club admin.');
            }
        }
    }

    if ($action === 'remove' && $userId && $clubId) {
        if (!table_exists($db, 'club_admins')) {
            set_message('The club_admins table does not exist. Please import the updated database.sql.');
        } else {
            $stmt = $db->prepare('DELETE FROM club_admins WHERE user_id = ? AND club_id = ?');
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $clubId);
                $stmt->execute();
                set_message('Allocation removed.');
            } else {
                set_message('Failed to prepare removal query. Please import the updated database.sql.');
            }
        }
    }

    header('Location: admin_club_allocations.php');
    exit;
}

// -----------------------------------------------------------------------------
// Prepare Data for Display
// -----------------------------------------------------------------------------

// All club admins.
$clubAdminsResult = $db->query(
    'SELECT users.id, users.name, users.email FROM users WHERE users.role = "club_admin" ORDER BY users.name'
);
$clubAdmins = $clubAdminsResult ?: new stdClass(); // fallback empty object

// All clubs.
$clubsResult = $db->query('SELECT id, name FROM clubs ORDER BY name');
$clubs = $clubsResult ?: new stdClass();

// Current allocations with club names.
$allocations = null;
if (table_exists($db, 'club_admins')) {
    $allocationsResult = $db->query(
        'SELECT club_admins.user_id, club_admins.club_id, users.name AS admin_name, clubs.name AS club_name
         FROM club_admins
         JOIN users ON users.id = club_admins.user_id
         JOIN clubs ON clubs.id = club_admins.club_id
         ORDER BY clubs.name, users.name'
    );
    if ($allocationsResult) {
        $allocations = $allocationsResult;
    }
}

// Clubs with no admin.
$unallocatedClubs = null;
if (table_exists($db, 'club_admins')) {
    $unallocatedResult = $db->query(
        'SELECT clubs.id, clubs.name FROM clubs
         LEFT JOIN club_admins ON club_admins.club_id = clubs.id
         WHERE club_admins.id IS NULL
         ORDER BY clubs.name'
    );
    if ($unallocatedResult) {
        $unallocatedClubs = $unallocatedResult;
    }
}

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Club Allocations | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Club Allocations</h2>
    <p>Assign club administrators to clubs. Each club admin manages their allocated club(s).</p>

    <?php if (!table_exists($db, 'club_admins')) { ?>
        <p class="flash" style="background:#e53e3e;">
            The <strong>club_admins</strong> table does not exist yet.
            <a class="button" href="setup_database.php" style="margin-left:12px; background:#fff; color:#e53e3e;">Run Database Setup</a>
        </p>
    <?php } ?>

    <!-- Allocate Club Admin -->
    <h3>Allocate Club to Admin</h3>
    <form method="post" class="form-card">
        <input type="hidden" name="action" value="allocate">
        <label>
            Club Admin
            <select name="user_id" required>
                <option value="">Select admin</option>
                <?php while ($admin = $clubAdmins->fetch_assoc()) { ?>
                    <option value="<?php echo $admin['id']; ?>">
                        <?php echo htmlspecialchars($admin['name']); ?> (<?php echo htmlspecialchars($admin['email']); ?>)
                    </option>
                <?php } ?>
            </select>
        </label>
        <label>
            Club
            <select name="club_id" required>
                <option value="">Select club</option>
                <?php while ($club = $clubs->fetch_assoc()) { ?>
                    <option value="<?php echo $club['id']; ?>">
                        <?php echo htmlspecialchars($club['name']); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        <button class="button">Allocate Club</button>
    </form>

    <!-- Current Allocations -->
    <h3>Current Allocations</h3>
    <?php if ($allocations && $allocations->num_rows) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Club</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($alloc = $allocations->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($alloc['admin_name']); ?></td>
                        <td><?php echo htmlspecialchars($alloc['club_name']); ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Remove this allocation?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="user_id" value="<?php echo (int) $alloc['user_id']; ?>">
                                <input type="hidden" name="club_id" value="<?php echo (int) $alloc['club_id']; ?>">
                                <button class="text-link">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No clubs have been allocated yet.</p>
    <?php } ?>

    <!-- Unallocated Clubs -->
    <h3>Unallocated Clubs</h3>
    <?php if ($unallocatedClubs && $unallocatedClubs->num_rows) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Club Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($club = $unallocatedClubs->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($club['name']); ?></td>
                        <td><span class="badge spots">No admin</span></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>All clubs have been allocated.</p>
    <?php } ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
