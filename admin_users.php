<?php
/**
 * AIU Club Store - Manage Users and Roles
 *
 * Allows a super administrator to change user roles between:
 * student, club_admin, and super_admin.
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
    exit('Only a super administrator can manage roles.');
}

$db = database();

// -----------------------------------------------------------------------------
// Handle Form Submission
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $role = $_POST['role'] ?? '';

    if ($id && in_array($role, ['student', 'club_admin', 'super_admin'], true)) {
        $stmt = $db->prepare('UPDATE users SET role=? WHERE id=?');
        $stmt->bind_param('si', $role, $id);
        $stmt->execute();
        set_message('User role updated.');
    }

    header('Location: admin_users.php');
    exit;
}

// -----------------------------------------------------------------------------
// Fetch Users for Display
// -----------------------------------------------------------------------------

$users = $db->query('SELECT id, name, email, role FROM users ORDER BY name');

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Manage Users | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Users and Roles</h2>

    <!-- Users List -->
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Save</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $users->fetch_assoc()) { ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <select name="role">
                                <?php foreach (['student', 'club_admin', 'super_admin'] as $role) { ?>
                                    <option <?php echo $user['role'] === $role ? 'selected' : ''; ?>>
                                        <?php echo $role; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                        <td>
                            <button class="button">Save</button>
                        </td>
                    </form>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
