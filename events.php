<?php
require_once __DIR__ . '/config/database.php';

/**
 * AIU Club Store - Events
 *
 * Displays upcoming events with booking status and ticket prices.
 */

$events = database()->query(
    'SELECT events.*, clubs.name AS club_name,
            (SELECT COUNT(*) FROM tickets WHERE tickets.event_id = events.id) AS booked
     FROM events
     JOIN clubs ON clubs.id = events.club_id
     WHERE `date` >= NOW()
     ORDER BY `date`'
);

$page_title = 'Events | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
  <h2>Upcoming Events</h2>
  <div class="grid">
    <?php if ($events && $events->num_rows) { while ($event = $events->fetch_assoc()) { ?>
      <article class="card">
        <div class="card-image">EVENT</div>
        <p class="event-date"><?php echo date('d F Y · g:i A', strtotime($event['date'])); ?></p>
        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
        <p><?php echo htmlspecialchars($event['venue']); ?> · <?php echo htmlspecialchars($event['club_name']); ?></p>
        <p><?php echo htmlspecialchars($event['description']); ?></p>
        <p class="price">
          <?php echo $event['ticket_price'] > 0 ? 'KES ' . number_format((float) $event['ticket_price'], 2) : 'Free'; ?>
        </p>
        <p><?php echo (int) $event['capacity'] - (int) $event['booked']; ?> places remaining</p>
        <a class="button" href="book_ticket.php?event_id=<?php echo (int) $event['id']; ?>">Book Ticket</a>
      </article>
    <?php }} else { ?>
      <p>No upcoming events have been added yet.</p>
    <?php } ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
