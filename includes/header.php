<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title ?? 'AIU Club Store'); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="navbar">
    <a class="brand" href="index.php">AIU CLUB STORE</a>
    <div class="nav-links">
      <a href="shop.php">Merchandise</a>
      <a href="events.php">Events</a>
      <a href="cart.php">Cart (<?php echo cart_count(); ?>)</a>
      <?php if (is_logged_in()) { ?>
        <a href="account.php">My Account</a>
        <?php if (in_array(current_user()['role'], ['club_admin', 'super_admin'], true)) { ?><a href="admin.php">Admin</a><?php } ?>
        <a href="logout.php">Log out</a>
      <?php } else { ?>
        <a href="login.php">Log in</a>
      <?php } ?>
    </div>
  </nav>
  <?php if ($message = pull_message()) { ?><p class="flash container"><?php echo htmlspecialchars($message); ?></p><?php } ?>

