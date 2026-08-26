<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title ?? 'AIU Club Store'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
  <?php if (is_logged_in()) { ?>
    <div class="welcome-banner">
      Welcome back, <span class="user-name"><?php echo htmlspecialchars(current_user()['name']); ?></span>!
      Role: <span class="user-role"><?php echo htmlspecialchars(current_user()['role']); ?></span>
    </div>
  <?php } ?>
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
  <?php if ($message = pull_message()) {
    $flashType = 'info';
    if (preg_match('/error|fail|cannot|denied|not found|invalid/i', $message)) {
      $flashType = 'error';
    } elseif (preg_match('/success|added|saved|updated|deleted|confirmed/i', $message)) {
      $flashType = 'success';
    }
    $flashClass = $flashType === 'error' ? 'flash error' : ($flashType === 'success' ? 'flash success' : 'flash');
    ?>
    <div class="container" style="margin-top: 16px;">
      <div class="<?php echo $flashClass; ?>">
        <i class="fas fa-<?php echo $flashType === 'error' ? 'exclamation-circle' : ($flashType === 'success' ? 'check-circle' : 'info-circle'); ?>"></i>
        <?php echo htmlspecialchars($message); ?>
      </div>
    </div>
  <?php } ?>

