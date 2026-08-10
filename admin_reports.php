<?php
// Read-only reporting dashboard for administrators.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$db = database();

// Count only paid merchandise orders as realised sales.
$sales = $db->query("SELECT COALESCE(SUM(total_amount), 0) AS total, COUNT(*) AS orders FROM orders WHERE payment_status = 'paid'")->fetch_assoc();
$eventStats = $db->query('SELECT events.title, events.capacity, COUNT(tickets.id) AS reserved, COALESCE(SUM(tickets.checked_in), 0) AS attended FROM events LEFT JOIN tickets ON tickets.event_id = events.id GROUP BY events.id ORDER BY events.`date` DESC');
$bestProducts = $db->query('SELECT products.name, COALESCE(SUM(order_items.quantity), 0) AS sold FROM products LEFT JOIN order_items ON order_items.product_id = products.id GROUP BY products.id ORDER BY sold DESC LIMIT 5');

$page_title = 'Reports | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
  <p><a class="text-link" href="admin.php">&larr; Dashboard</a></p><h2>Sales and Attendance Reports</h2>
  <div class="grid"><article class="card"><h3>Paid Merchandise Sales</h3><p class="price">KES <?php echo number_format((float) $sales['total'], 2); ?></p></article><article class="card"><h3>Paid Orders</h3><p class="price"><?php echo (int) $sales['orders']; ?></p></article></div>
  <h3>Event Attendance</h3><table class="table"><thead><tr><th>Event</th><th>Reserved</th><th>Checked in</th><th>Capacity</th></tr></thead><tbody><?php while ($event = $eventStats->fetch_assoc()) { ?><tr><td><?php echo htmlspecialchars($event['title']); ?></td><td><?php echo (int) $event['reserved']; ?></td><td><?php echo (int) $event['attended']; ?></td><td><?php echo (int) $event['capacity']; ?></td></tr><?php } ?></tbody></table>
  <h3>Best-selling Products</h3><table class="table"><thead><tr><th>Product</th><th>Quantity sold</th></tr></thead><tbody><?php while ($product = $bestProducts->fetch_assoc()) { ?><tr><td><?php echo htmlspecialchars($product['name']); ?></td><td><?php echo (int) $product['sold']; ?></td></tr><?php } ?></tbody></table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
