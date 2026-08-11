<?php
/**
 * AIU Club Store - Manage Orders and Payments
 *
 * Allows administrators to view and update payment and order status
 * for both merchandise orders and event tickets.
 * Club administrators can only manage orders for their allocated club.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$db = database();
$user = current_user();
$managedClubIds = managed_club_ids();
$isSuperAdmin = $user['role'] === 'super_admin';
$managedCondition = managed_club_condition();

// -----------------------------------------------------------------------------
// Helper functions for club-admin order/ticket access
// -----------------------------------------------------------------------------

/**
 * Check if an order contains products from managed clubs.
 */
function order_belongs_to_managed_club(mysqli $db, int $orderId, array $managedClubIds): bool
{
    if (empty($managedClubIds)) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($managedClubIds), '?'));
    $types = str_repeat('i', count($managedClubIds));
    $stmt = $db->prepare(
        "SELECT 1 FROM order_items
         JOIN products ON products.id = order_items.product_id
         WHERE order_items.order_id = ? AND products.club_id IN ($placeholders)
         LIMIT 1"
    );

    $params = array_merge([$orderId], $managedClubIds);
    $stmt->bind_param('i' . $types, ...$params);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

/**
 * Check if a ticket belongs to an event from a managed club.
 */
function ticket_belongs_to_managed_club(mysqli $db, int $ticketId, array $managedClubIds): bool
{
    if (empty($managedClubIds)) {
        return false;
    }

    $placeholders = implode(',', array_fill(0, count($managedClubIds), '?'));
    $types = str_repeat('i', count($managedClubIds));
    $stmt = $db->prepare(
        "SELECT 1 FROM tickets
         JOIN events ON events.id = tickets.event_id
         WHERE tickets.id = ? AND events.club_id IN ($placeholders)
         LIMIT 1"
    );

    $params = array_merge([$ticketId], $managedClubIds);
    $stmt->bind_param('i' . $types, ...$params);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

// -----------------------------------------------------------------------------
// Handle Status Updates
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $paymentStatus = $_POST['payment_status'] ?? '';
    $orderStatus = $_POST['order_status'] ?? '';

    $allowedPayments = ['pending', 'paid', 'failed', 'refunded'];
    $allowedOrders = ['pending', 'processing', 'ready', 'completed', 'cancelled'];

    // Update a merchandise order.
    if ($type === 'order' && $id && in_array($paymentStatus, $allowedPayments, true) && in_array($orderStatus, $allowedOrders, true)) {
        if ($isSuperAdmin || order_belongs_to_managed_club($db, $id, $managedClubIds)) {
            $stmt = $db->prepare('UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('ssi', $paymentStatus, $orderStatus, $id);
                $stmt->execute();
                $stmt->close();

                // Keep the payments table in sync.
                $payment = $db->prepare('UPDATE payments SET status = ? WHERE order_id = ?');
                if ($payment) {
                    $payment->bind_param('si', $paymentStatus, $id);
                    $payment->execute();
                    $payment->close();
                }

                set_message('Order status updated.');
                header('Location: admin_orders.php?saved=1');
                exit;
            } else {
                set_message('Failed to prepare order update query.');
            }
        } else {
            set_message('You can only update orders for your allocated club.');
        }
    }

    // Update a ticket payment.
    if ($type === 'ticket' && $id && in_array($paymentStatus, $allowedPayments, true)) {
        if ($isSuperAdmin || ticket_belongs_to_managed_club($db, $id, $managedClubIds)) {
            $stmt = $db->prepare('UPDATE tickets SET payment_status = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $paymentStatus, $id);
                $stmt->execute();
                $stmt->close();

                // Keep the payments table in sync.
                $payment = $db->prepare('UPDATE payments SET status = ? WHERE ticket_id = ?');
                if ($payment) {
                    $payment->bind_param('si', $paymentStatus, $id);
                    $payment->execute();
                    $payment->close();
                }

                set_message('Ticket payment status updated.');
                header('Location: admin_orders.php?saved=1');
                exit;
            } else {
                set_message('Failed to prepare ticket update query.');
            }
        } else {
            set_message('You can only update tickets for your allocated club.');
        }
    }

    header('Location: admin_orders.php');
    exit;
}

// -----------------------------------------------------------------------------
// Fetch Data for Display
// -----------------------------------------------------------------------------

// All merchandise orders with student names, filtered by managed clubs for club_admins.
$ordersQuery = '
    SELECT orders.*, users.name AS student_name
    FROM orders
    JOIN users ON users.id = orders.user_id
    WHERE ' . ($isSuperAdmin ? '1=1' : 'EXISTS (SELECT 1 FROM order_items JOIN products ON products.id = order_items.product_id WHERE order_items.order_id = orders.id AND ' . $managedCondition . ')');
$ordersQuery .= ' ORDER BY orders.created_at DESC';
$orders = $db->query($ordersQuery);

// All event tickets with student names and event titles, filtered by managed clubs for club_admins.
$ticketsQuery = '
    SELECT tickets.*, users.name AS student_name, events.title AS event_title
    FROM tickets
    JOIN users ON users.id = tickets.user_id
    JOIN events ON events.id = tickets.event_id
    WHERE ' . ($isSuperAdmin ? '1=1' : $managedCondition);
$ticketsQuery .= ' ORDER BY events.`date` DESC';
$tickets = $db->query($ticketsQuery);

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Orders and Payments | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>

    <!-- Merchandise Orders -->
    <h2>Merchandise Orders</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Student</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Order status</th>
                <th>Save</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()) {
                $formId = 'order-form-' . (int) $order['id'];
            ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['student_name']); ?></td>
                    <td>KES <?php echo number_format((float) $order['total_amount'], 2); ?></td>
                    <td>
                        <select name="payment_status" form="<?php echo $formId; ?>">
                            <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $order['payment_status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <select name="order_status" form="<?php echo $formId; ?>">
                            <?php foreach (['pending', 'processing', 'ready', 'completed', 'cancelled'] as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $order['order_status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <form id="<?php echo $formId; ?>" method="post" style="display:inline;">
                            <input type="hidden" name="type" value="order">
                            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                            <button type="submit" class="button">Save</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Event Tickets -->
    <h2>Event Tickets</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Student</th>
                <th>Ticket code</th>
                <th>Payment</th>
                <th>Entry</th>
                <th>Save</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($ticket = $tickets->fetch_assoc()) {
                $formId = 'ticket-form-' . (int) $ticket['id'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($ticket['event_title']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td>
                    <td>
                        <select name="payment_status" form="<?php echo $formId; ?>">
                            <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $ticket['payment_status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <?php echo $ticket['checked_in'] ? 'Yes' : 'No'; ?>
                    </td>
                    <td>
                        <form id="<?php echo $formId; ?>" method="post" style="display:inline;">
                            <input type="hidden" name="type" value="ticket">
                            <input type="hidden" name="id" value="<?php echo $ticket['id']; ?>">
                            <button type="submit" class="button">Save</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<script>
    // Pop-up confirmation when redirected after a successful save.
    if (new URLSearchParams(window.location.search).has('saved')) {
        alert('Changes saved successfully!');
        var url = new URL(window.location);
        url.searchParams.delete('saved');
        window.history.replaceState({}, '', url);
    }
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
