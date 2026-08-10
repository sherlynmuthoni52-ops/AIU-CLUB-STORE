<?php
// Administrators update operational and payment statuses from one screen.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_admin(); $db = database();
$allowedPayments = ['pending', 'paid', 'failed', 'refunded'];
$allowedOrders = ['pending', 'processing', 'ready', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? ''; $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $paymentStatus = $_POST['payment_status'] ?? ''; $orderStatus = $_POST['order_status'] ?? '';

    // Update both the parent record and its payment record to keep statuses aligned.
    if ($type === 'order' && $id && in_array($paymentStatus, $allowedPayments, true) && in_array($orderStatus, $allowedOrders, true)) {
        $order = $db->prepare('UPDATE orders SET payment_status=?, order_status=? WHERE id=?');
        $order->bind_param('ssi', $paymentStatus, $orderStatus, $id); $order->execute();
        $payment = $db->prepare('UPDATE payments SET status=? WHERE order_id=?'); $payment->bind_param('si', $paymentStatus, $id); $payment->execute(); set_message('Order status updated.');
    } elseif ($type === 'ticket' && $id && in_array($paymentStatus, $allowedPayments, true)) {
        $ticket = $db->prepare('UPDATE tickets SET payment_status=? WHERE id=?'); $ticket->bind_param('si', $paymentStatus, $id); $ticket->execute();
        $payment = $db->prepare('UPDATE payments SET status=? WHERE ticket_id=?'); $payment->bind_param('si', $paymentStatus, $id); $payment->execute(); set_message('Ticket payment status updated.');
    }
    header('Location: admin_orders.php'); exit;
}

$orders = $db->query('SELECT orders.*, users.name AS student_name FROM orders JOIN users ON users.id = orders.user_id ORDER BY orders.created_at DESC');
$tickets = $db->query('SELECT tickets.*, users.name AS student_name, events.title AS event_title FROM tickets JOIN users ON users.id = tickets.user_id JOIN events ON events.id = tickets.event_id ORDER BY events.`date` DESC');
$page_title = 'Orders and Payments | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section"><p><a class="text-link" href="admin.php">&larr; Dashboard</a></p><h2>Merchandise Orders</h2><table class="table"><thead><tr><th>Order</th><th>Student</th><th>Total</th><th>Payment</th><th>Order status</th><th>Save</th></tr></thead><tbody><?php while ($order = $orders->fetch_assoc()) { ?><tr><td colspan="6"><form method="post"><input type="hidden" name="type" value="order"><input type="hidden" name="id" value="<?php echo (int) $order['id']; ?>">#<?php echo (int) $order['id']; ?> · <?php echo htmlspecialchars($order['student_name']); ?> · KES <?php echo number_format((float) $order['total_amount'], 2); ?> <select name="payment_status"><?php foreach ($allowedPayments as $status) { ?><option <?php echo $order['payment_status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option><?php } ?></select> <select name="order_status"><?php foreach ($allowedOrders as $status) { ?><option <?php echo $order['order_status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option><?php } ?></select> <button class="button">Save</button></form></td></tr><?php } ?></tbody></table><h2>Event Tickets</h2><table class="table"><thead><tr><th>Event</th><th>Student</th><th>Ticket code</th><th>Payment</th><th>Entry</th><th>Save</th></tr></thead><tbody><?php while ($ticket = $tickets->fetch_assoc()) { ?><tr><td colspan="6"><form method="post"><input type="hidden" name="type" value="ticket"><input type="hidden" name="id" value="<?php echo (int) $ticket['id']; ?>"><?php echo htmlspecialchars($ticket['event_title']); ?> · <?php echo htmlspecialchars($ticket['student_name']); ?> · <?php echo htmlspecialchars($ticket['ticket_code']); ?> <select name="payment_status"><?php foreach ($allowedPayments as $status) { ?><option <?php echo $ticket['payment_status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option><?php } ?></select> · <?php echo $ticket['checked_in'] ? 'Checked in' : 'Not checked in'; ?> <button class="button">Save</button></form></td></tr><?php } ?></tbody></table></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
