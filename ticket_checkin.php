<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin(); $db = database(); $ticket = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['ticket_code'] ?? ''));
    $stmt = $db->prepare('SELECT tickets.*, events.title, events.venue, events.`date`, users.name AS student_name FROM tickets JOIN events ON events.id=tickets.event_id JOIN users ON users.id=tickets.user_id WHERE tickets.ticket_code=? LIMIT 1');
    $stmt->bind_param('s', $code); $stmt->execute(); $ticket = $stmt->get_result()->fetch_assoc();
    if (!$ticket) set_message('No ticket was found with that code.');
    elseif ($ticket['checked_in']) set_message('This ticket was already checked in.');
    elseif ($ticket['payment_status'] !== 'paid') set_message('Ticket found, but payment is still pending.');
    else { $update = $db->prepare('UPDATE tickets SET checked_in=1 WHERE id=?'); $update->bind_param('i', $ticket['id']); $update->execute(); $ticket['checked_in'] = 1; set_message('Entry confirmed for ' . $ticket['student_name'] . '.'); }
}
$page_title = 'Ticket Check-in | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section"><p><a class="text-link" href="admin.php">&larr; Dashboard</a></p><h2>Ticket Check-in</h2><p>Enter the unique ticket code shown by the student.</p><form method="post" class="form-card"><label>Ticket code<input name="ticket_code" placeholder="AIU-2026-XXXXXXXX" required autofocus></label><button class="button">Verify Ticket</button></form><?php if ($ticket) { ?><article class="card"><h3><?php echo htmlspecialchars($ticket['title']); ?></h3><p>Student: <?php echo htmlspecialchars($ticket['student_name']); ?></p><p>Venue: <?php echo htmlspecialchars($ticket['venue']); ?></p><p>Date: <?php echo date('d M Y g:i A', strtotime($ticket['date'])); ?></p><p>Ticket: <strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong></p><p>Entry status: <strong><?php echo $ticket['checked_in'] ? 'Checked in' : 'Not checked in'; ?></strong></p></article><?php } ?></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
