<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Registration
 *
 * Allows new users to create an account.
 * Validates input and redirects to login on success.
 */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = '';
    if (!empty($_POST['fName']) || !empty($_POST['lName'])) {
        $name = trim($_POST['fName'] ?? '') . ' ' . trim($_POST['lName'] ?? '');
    } else {
        $name = trim($_POST['name'] ?? '');
    }
    $name = trim($name);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $error = 'Enter your name, a valid email, and a password of at least 8 characters.';
    } else {
        $stmt = database()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($stmt->bind_param('sss', $name, $email, $hash) && $stmt->execute()) {
            set_message('Registration successful. Please log in.');
            header('Location: login.php');
            exit;
        }

        $error = 'This email may already be registered.';
    }
}

$page_title = 'Register | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section form-page">
  <h2>Create Account</h2>
  <?php if ($error) { ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
  <?php } ?>
  <form method="post" class="form-card">
    <label>Name<input name="name" required></label>
    <label>Email<input name="email" type="email" required></label>
    <label>Password<input name="password" type="password" minlength="8" required></label>
    <button class="button">Register</button>
    <p>Already registered? <a class="text-link" href="login.php">Log in</a>.</p>
  </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
