<?php
require_once __DIR__ . '/config/database.php'; require_once __DIR__ . '/includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? ''; $stmt = database()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1'); $stmt->bind_param('s', $email); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); if ($user && password_verify($password, $user['password'])) { unset($user['password']); $_SESSION['user'] = $user; set_message('Welcome back, ' . $user['name'] . '!'); header('Location: index.php'); exit; } $error = 'Incorrect email or password.'; }
$page_title = 'Log in | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section form-page"><h2>Log In</h2><?php if ($error) { ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php } ?><form method="post" class="form-card"><label>Email<input name="email" type="email" required></label><label>Password<input name="password" type="password" required></label><button class="button">Log In</button><p>New student? <a class="text-link" href="register.php">Create an account</a>.</p></form></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
