<?php
/**
 * AIU Club Store - My Account
 *
 * Shows the logged-in user's merchandise orders and event tickets.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$db = database();
$userId = (int) current_user()['id'];

// -----------------------------------------------------------------------------
// Fetch User Orders
// -----------------------------------------------------------------------------

$orders = $db->query(
    "SELECT id, total_amount, payment_status, order_status, created_at
     FROM orders
     WHERE user_id = $userId
     ORDER BY created_at DESC"
);

// -----------------------------------------------------------------------------
// Fetch User Event Tickets
// -----------------------------------------------------------------------------

$tickets = $db->query(
    "SELECT tickets.*, events.title, events.venue, events.`date`
     FROM tickets
     JOIN events ON events.id = tickets.event_id
     WHERE tickets.user_id = $userId
     ORDER BY events.`date` DESC"
);

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'My Account | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <h2>My Account</h2>
    <p>Signed in as <strong><?php echo htmlspecialchars(current_user()['name']); ?>.</strong></p>

    <!-- Merchandise Orders -->
    <h3>My Merchandise Orders</h3>
    <?php if ($orders->num_rows) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                        <td>KES <?php echo number_format((float) $order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No merchandise orders yet.</p>
    <?php } ?>

    <!-- Event Tickets -->
    <h3>My Event Tickets</h3>
    <?php if ($tickets->num_rows) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Ticket Code</th>
                    <th>Entry</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ticket = $tickets->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($ticket['title']); ?>
                            <br>
                            <small><?php echo htmlspecialchars($ticket['venue']); ?></small>
                        </td>
                        <td><?php echo date('d M Y g:i A', strtotime($ticket['date'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong></td>
                        <td><?php echo $ticket['checked_in'] ? 'Checked in' : 'Not checked in'; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No event tickets yet.</p>
    <?php } ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
