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
if (current_user()['role'] === 'super_admin' && table_exists($db, 'club_admins')) {
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
<main class="container section admin-page">
    <p>
        <a class="button dashboard-btn" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Admin Dashboard</h2>
    <p>Manage products, events, and entry verification.</p>

    <!-- Summary Cards -->
    <div class="grid summary-grid">
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
        <a class="button allocations-btn" href="admin_club_allocations.php">Manage Allocations</a>
    </p>
        <div class="grid summary-grid">
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
     <nav class="admin-management" aria-label="Admin sections">
         <?php if (current_user()['role'] === 'super_admin') { ?>
             <a class="button manage-btn" href="admin_clubs.php"><i class="fas fa-shield-alt"></i> Clubs</a>
             <a class="button manage-btn" href="admin_users.php"><i class="fas fa-users"></i> Users &amp; Roles</a>
         <?php } ?>
         <a class="button manage-btn" href="admin_products.php"><i class="fas fa-box"></i> Products &amp; Sizes</a>
         <a class="button manage-btn" href="admin_product_image.php"><i class="fas fa-image"></i> Product Images</a>
         <a class="button manage-btn" href="admin_events.php"><i class="fas fa-calendar-alt"></i> Events</a>
         <a class="button manage-btn" href="admin_orders.php"><i class="fas fa-receipt"></i> Orders &amp; Payments</a>
         <a class="button manage-btn" href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
         <a class="button manage-btn" href="ticket_checkin.php"><i class="fas fa-qrcode"></i> Ticket Check-in</a>
     </nav>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
