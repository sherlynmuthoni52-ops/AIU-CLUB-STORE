<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
$db = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? ''; $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $paymentStatus = $_POST['payment_status'] ?? ''; $orderStatus = $_POST['order_status'] ?? '';
    $allowedPayments = ['pending', 'paid', 'failed', 'refunded']; $allowedOrders = ['pending', 'processing', 'ready', 'completed', 'cancelled'];
    if ($type === 'order' && $id && in_array($paymentStatus, $allowedPayments, true) && in_array($orderStatus, $allowedOrders, true)) {
        $stmt = $db->prepare('UPDATE orders SET payment_status=?, order_status=? WHERE id=?'); $stmt->bind_param('ssi', $paymentStatus, $orderStatus, $id); $stmt->execute();
        $payment = $db->prepare('UPDATE payments SET status=? WHERE order_id=?'); $payment->bind_param('si', $paymentStatus, $id); $payment->execute(); set_message('Order status updated.');
    }
    if ($type === 'ticket' && $id && in_array($paymentStatus, $allowedPayments, true)) {
        $stmt = $db->prepare('UPDATE tickets SET payment_status=? WHERE id=?'); $stmt->bind_param('si', $paymentStatus, $id); $stmt->execute();
        $payment = $db->prepare('UPDATE payments SET status=? WHERE ticket_id=?'); $payment->bind_param('si', $paymentStatus, $id); $payment->execute(); set_message('Ticket payment status updated.');
    }
    header('Location: admin_orders.php'); exit;
}
$orders = $db->query('SELECT orders.*, users.name AS student_name FROM orders JOIN users ON users.id=orders.user_id ORDER BY orders.created_at DESC');
$tickets = $db->query('SELECT tickets.*, users.name AS student_name, events.title AS event_title FROM tickets JOIN users ON users.id=tickets.user_id JOIN events ON events.id=tickets.event_id ORDER BY events.`date` DESC');
$page_title = 'Orders and Payments | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section"><p><a class="text-link" href="admin.php">&larr; Dashboard</a></p><h2>Merchandise Orders</h2><table class="table"><thead><tr><th>Order</th><th>Student</th><th>Total</th><th>Payment</th><th>Order status</th><th>Save</th></tr></thead><tbody><?php while ($order = $orders->fetch_assoc()) { ?><tr><form method="post"><input type="hidden" name="type" value="order"><input type="hidden" name="id" value="<?php echo $order['id']; ?>"><td>#<?php echo $order['id']; ?></td><td><?php echo htmlspecialchars($order['student_name']); ?></td><td>KES <?php echo number_format((float) $order['total_amount'], 2); ?></td><td><select name="payment_status"><?php foreach (['pending','paid','failed','refunded'] as $status) { ?><option <?php echo $order['payment_status']===$status?'selected':''; ?>><?php echo $status; ?></option><?php } ?></select></td><td><select name="order_status"><?php foreach (['pending','processing','ready','completed','cancelled'] as $status) { ?><option <?php echo $order['order_status']===$status?'selected':''; ?>><?php echo $status; ?></option><?php } ?></select></td><td><button class="button">Save</button></td></form></tr><?php } ?></tbody></table><h2>Event Tickets</h2><table class="table"><thead><tr><th>Event</th><th>Student</th><th>Ticket code</th><th>Payment</th><th>Entry</th><th>Save</th></tr></thead><tbody><?php while ($ticket = $tickets->fetch_assoc()) { ?><tr><form method="post"><input type="hidden" name="type" value="ticket"><input type="hidden" name="id" value="<?php echo $ticket['id']; ?>"><td><?php echo htmlspecialchars($ticket['event_title']); ?></td><td><?php echo htmlspecialchars($ticket['student_name']); ?></td><td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td><td><select name="payment_status"><?php foreach (['pending','paid','failed','refunded'] as $status) { ?><option <?php echo $ticket['payment_status']===$status?'selected':''; ?>><?php echo $status; ?></option><?php } ?></select></td><td><?php echo $ticket['checked_in'] ? 'Checked in' : 'Not checked in'; ?></td><td><button class="button">Save</button></td></form></tr><?php } ?></tbody></table></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
