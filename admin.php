<?php
/**
 * AIU Club Store - Admin Dashboard
 *
 * Displays summary counts for key database tables and provides
 * quick links to all admin management sections.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure only administrators can access this page.
require_admin();

// -----------------------------------------------------------------------------
// Data Retrieval
// -----------------------------------------------------------------------------

$db = database();

// Count records in each major table for the dashboard summary.
$counts = [];
foreach (['Products' => 'products', 'Events' => 'events', 'Orders' => 'orders', 'Tickets' => 'tickets'] as $label => $table) {
    $counts[$label] = (int) $db->query("SELECT COUNT(*) AS total FROM $table")->fetch_assoc()['total'];
}

// Club allocation overview for super admin.
$clubAllocations = null;
$unallocatedClubsCount = 0;
if (current_user()['role'] === 'super_admin') {
    $allocationsResult = $db->query(
        'SELECT club_admins.user_id, club_admins.club_id, users.name AS admin_name, clubs.name AS club_name
         FROM club_admins
         JOIN users ON users.id = club_admins.user_id
         JOIN clubs ON clubs.id = club_admins.club_id
         ORDER BY clubs.name, users.name'
    );
    if ($allocationsResult && $allocationsResult->num_rows) {
        $clubAllocations = $allocationsResult;
    }

    $unallocatedResult = $db->query(
        'SELECT COUNT(*) AS total FROM clubs LEFT JOIN club_admins ON club_admins.club_id = clubs.id WHERE club_admins.id IS NULL'
    );
    if ($unallocatedResult) {
        $unallocatedClubsCount = (int) $unallocatedResult->fetch_assoc()['total'];
    }
}

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Admin Dashboard | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Admin Dashboard</h2>
    <p>Manage products, events, and entry verification.</p>

    <!-- Summary Cards -->
    <div class="grid">
        <?php foreach ($counts as $label => $count) { ?>
            <article class="card">
                <h3><?php echo $label; ?></h3>
                <p class="price"><?php echo $count; ?></p>
            </article>
        <?php } ?>
    </div>

    <!-- Club Allocation Overview (Super Admin Only) -->
    <?php if (current_user()['role'] === 'super_admin') { ?>
        <h3>Club Allocations</h3>
        <p>
            <a class="button" href="admin_club_allocations.php">Manage Allocations</a>
        </p>
        <div class="grid">
            <article class="card">
                <h3>Allocated Clubs</h3>
                <p class="price"><?php echo $clubAllocations ? $clubAllocations->num_rows : 0; ?></p>
            </article>
            <article class="card">
                <h3>Unallocated Clubs</h3>
                <p class="price"><?php echo $unallocatedClubsCount; ?></p>
            </article>
        </div>
        <?php if ($clubAllocations && $clubAllocations->num_rows) { ?>
            <h4>Current Allocations</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Club</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alloc = $clubAllocations->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alloc['admin_name']); ?></td>
                            <td><?php echo htmlspecialchars($alloc['club_name']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    <?php } ?>

    <!-- Admin Management Links -->
    <h3>Management</h3>
    <p>
        <?php if (current_user()['role'] === 'super_admin') { ?>
            <a class="button" href="admin_clubs.php">Clubs</a>
            <a class="button" href="admin_users.php">Users &amp; Roles</a>
        <?php } ?>
        <a class="button" href="admin_products.php">Products &amp; Sizes</a>
        <a class="button" href="admin_product_image.php">Product Images</a>
        <a class="button" href="admin_events.php">Events</a>
        <a class="button" href="admin_orders.php">Orders &amp; Payments</a>
        <a class="button" href="admin_reports.php">Reports</a>
        <a class="button" href="ticket_checkin.php">Ticket Check-in</a>
    </p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
