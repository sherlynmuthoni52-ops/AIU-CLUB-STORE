<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
$db = database();
$counts = [];
foreach (['Products' => 'products', 'Events' => 'events', 'Orders' => 'orders', 'Tickets' => 'tickets'] as $label => $table) {
    $counts[$label] = (int) $db->query("SELECT COUNT(*) AS total FROM $table")->fetch_assoc()['total'];
}
$page_title = 'Admin Dashboard | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section"><h2>Admin Dashboard</h2><p>Manage products, events, and entry verification.</p><div class="grid"><?php foreach ($counts as $label => $count) { ?><article class="card"><h3><?php echo $label; ?></h3><p class="price"><?php echo $count; ?></p></article><?php } ?></div><h3>Management</h3><p><a class="button" href="admin_products.php">Products &amp; Sizes</a> <a class="button" href="admin_product_image.php">Product Images</a> <a class="button" href="admin_events.php">Events</a> <a class="button" href="admin_orders.php">Orders &amp; Payments</a> <a class="button" href="admin_reports.php">Reports</a> <a class="button" href="ticket_checkin.php">Ticket Check-in</a></p></main>
<?php require __DIR__ . '/includes/footer.php'; ?>



