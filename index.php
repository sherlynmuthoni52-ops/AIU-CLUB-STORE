<?php
require_once __DIR__ . '/config/database.php';

/**
 * AIU Club Store - Homepage
 *
 * Displays featured clubs and a hero call-to-action.
 */

$page_title = 'AIU Club Store';
$clubs = database()->query('SELECT id, name, description FROM clubs ORDER BY name LIMIT 3');

require __DIR__ . '/includes/header.php';
?>
<header class="hero">
  <?php if (is_logged_in()) { ?>
    <h1>Welcome back, <?php echo htmlspecialchars(current_user()['name']); ?>!</h1>
    <p>Ready to explore the latest clubs, merchandise, and events?</p>
  <?php } else { ?>
    <h1>Wear Your Club. Join the Moment.</h1>
    <p>Official student-club merchandise and tickets for exciting AIU events.</p>
  <?php } ?>
  <a class="button" href="shop.php">Shop Merchandise</a>
  <a class="button outline" href="events.php">Explore Events</a>
</header>
<main class="container section">
  <h2>Featured Clubs</h2>
  <div class="grid">
    <?php if ($clubs && $clubs->num_rows) { while ($club = $clubs->fetch_assoc()) { ?>
      <article class="card">
        <div class="card-image">AIU</div>
        <h3><?php echo htmlspecialchars($club['name']); ?></h3>
        <p><?php echo htmlspecialchars($club['description'] ?: 'Discover merchandise and events from this club.'); ?></p>
        <a class="text-link" href="shop.php?club=<?php echo (int) $club['id']; ?>">View merchandise</a>
      </article>
    <?php }} else { ?>
      <p>Add clubs in phpMyAdmin to display them here.</p>
    <?php } ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
