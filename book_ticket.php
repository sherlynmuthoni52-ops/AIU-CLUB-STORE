<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$eventId) { header('Location: events.php'); exit; }
$db = database();
try {
    $db->begin_transaction();
    $event = $db->query('SELECT id, title, capacity, ticket_price FROM events WHERE id = ' . (int) $eventId . ' FOR UPDATE')->fetch_assoc();
    $booked = $event ? (int) $db->query('SELECT COUNT(*) AS total FROM tickets WHERE event_id = ' . (int) $eventId)->fetch_assoc()['total'] : 0;
    if (!$event || $booked >= (int) $event['capacity']) throw new Exception('This event is sold out or unavailable.');
    $userId = (int) current_user()['id']; $code = 'AIU-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4))); $status = $event['ticket_price'] > 0 ? 'pending' : 'paid';
    $ticket = $db->prepare('INSERT INTO tickets (event_id, user_id, ticket_code, qr_code, payment_status) VALUES (?, ?, ?, ?, ?)');
    $ticket->bind_param('iisss', $eventId, $userId, $code, $code, $status); $ticket->execute(); $ticketId = $db->insert_id;
    $reference = 'TICKET-' . $ticketId . '-' . strtoupper(bin2hex(random_bytes(3))); $method = $event['ticket_price'] > 0 ? 'Pay on entry' : 'Free reservation'; $amount = (float) $event['ticket_price'];
    $payment = $db->prepare('INSERT INTO payments (user_id, ticket_id, amount, method, status, reference) VALUES (?, ?, ?, ?, ?, ?)');
    $payment->bind_param('iidsss', $userId, $ticketId, $amount, $method, $status, $reference); $payment->execute();
    $db->commit(); set_message('Ticket booked. Your code is: ' . $code); header('Location: account.php'); exit;
} catch (Throwable $e) { $db->rollback(); set_message($e->getMessage()); header('Location: events.php'); exit; }
