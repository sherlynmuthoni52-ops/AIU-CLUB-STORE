<?php
/**
 * AIU Club Store - Reports
 *
 * Displays merchandise sales totals, order counts, event attendance
 * statistics, and best-selling products.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$db = database();

// -----------------------------------------------------------------------------
// Fetch Report Data
// -----------------------------------------------------------------------------

// Total paid merchandise sales and order count.
$sales = $db->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total, COUNT(*) AS orders
     FROM orders
     WHERE payment_status = 'paid'"
)->fetch_assoc();

// Event attendance breakdown: capacity vs. reserved vs. checked-in.
$eventStats = $db->query(
    'SELECT events.title, events.capacity, COUNT(tickets.id) AS reserved, COALESCE(SUM(tickets.checked_in), 0) AS attended
     FROM events
     LEFT JOIN tickets ON tickets.event_id = events.id
     GROUP BY events.id
     ORDER BY events.`date` DESC'
);

// Top 5 best-selling products by quantity sold.
$bestProducts = $db->query(
    'SELECT products.name, COALESCE(SUM(order_items.quantity), 0) AS sold
     FROM products
     LEFT JOIN order_items ON order_items.product_id = products.id
     GROUP BY products.id
     ORDER BY sold DESC
     LIMIT 5'
);

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Reports | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Sales and Attendance Reports</h2>

    <!-- Sales Summary Cards -->
    <div class="grid">
        <article class="card">
            <h3>Paid Merchandise Sales</h3>
            <p class="price">KES <?php echo number_format((float) $sales['total'], 2); ?></p>
        </article>
        <article class="card">
            <h3>Paid Orders</h3>
            <p class="price"><?php echo $sales['orders']; ?></p>
        </article>
    </div>

    <!-- Event Attendance -->
    <h3>Event Attendance</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Reserved</th>
                <th>Checked in</th>
                <th>Capacity</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($event = $eventStats->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td><?php echo $event['reserved']; ?></td>
                    <td><?php echo $event['attended']; ?></td>
                    <td><?php echo $event['capacity']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Best-selling Products -->
    <h3>Best-selling Products</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity sold</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $bestProducts->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo $product['sold']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
