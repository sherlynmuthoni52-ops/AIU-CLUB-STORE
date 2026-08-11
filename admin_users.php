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
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $role, $id);
            $stmt->execute();
            $stmt->close();
            set_message('User role updated.');
            header('Location: admin_users.php?saved=1');
            exit;
        } else {
            set_message('Failed to prepare update query.');
        }
    } else {
        if (!$id) {
            set_message('Invalid user ID.');
        } elseif (!$role) {
            set_message('No role selected.');
        }
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
            <?php while ($user = $users->fetch_assoc()) {
                $formId = 'user-form-' . (int) $user['id'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <!-- The form attribute associates this select with the form below,
                             even though the form lives in the next cell. This is valid HTML5
                             and ensures the select's value is submitted with the form. -->
                        <select name="role" form="<?php echo $formId; ?>">
                            <?php foreach (['student', 'club_admin', 'super_admin'] as $role) { ?>
                                <option value="<?php echo $role; ?>" <?php echo $user['role'] === $role ? 'selected' : ''; ?>>
                                    <?php echo $role; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <form id="<?php echo $formId; ?>" method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="button">Save</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<script>
    // Show a pop-up confirming the save when redirected after a successful update.
    if (new URLSearchParams(window.location.search).has('saved')) {
        alert('Changes saved successfully!');
        // Clean up the URL parameter without reloading the page.
        const url = new URL(window.location);
        url.searchParams.delete('saved');
        window.history.replaceState({}, '', url);
    }
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
