<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$showSignup = false;
$showLogoutPopup = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signUp'])) {
        $showSignup = true;
        $name = trim($_POST['name'] ?? '');
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
    } elseif (isset($_POST['signIn'])) {
        $showSignup = false;
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = database()->prepare(
            'SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1'
        );
        if (!$stmt) {
            $error = 'The login system is unavailable right now. Please try again later.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && verify_user_password($password, (string) $user['password'])) {
                $storedPassword = (string) $user['password'];
                $legacyPassword = password_get_info($storedPassword)['algo'] === null;

                if ($legacyPassword) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = database()->prepare('UPDATE users SET password = ? WHERE id = ?');
                    if ($update) {
                        $update->bind_param('si', $newHash, $user['id']);
                        $update->execute();
                    }
                }

                unset($user['password']);
                $_SESSION['user'] = $user;
                set_message('Welcome back, ' . $user['name'] . '!');

                $redirect = 'index.php';
                if (!headers_sent()) {
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    echo '<!doctype html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect) . '">';
                    echo '<script>window.location.href = ' . json_encode($redirect) . ';</script></head><body>If you are not redirected, <a href="' . htmlspecialchars($redirect) . '">click here</a>.</body></html>';
                    exit;
                }
            }

            $error = 'Not Found, Incorrect Email or Password';
        }
    }
}

if (isset($_GET['message']) && $_GET['message'] === 'logout') {
    $showLogoutPopup = true;
}

$page_title = 'Log in | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
  <div class="auth-container" id="signIn" style="display: <?php echo $showSignup ? 'none' : 'block'; ?>;">
    <h1 class="form-title">Sign In</h1>
    <?php if ($error && !$showSignup) { ?>
      <p class="error" style="color:#9b2c2c; text-align:center; margin-bottom:1rem;"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <form method="post" class="auth-form">
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" id="signin-email" placeholder="Email" required>
        <label for="signin-email">Email</label>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="signin-password" placeholder="Password" required>
        <label for="signin-password">Password</label>
      </div>
      <input type="submit" class="btn" value="Sign In" name="signIn">
    </form>
    <div class="links">
      <p>Don't have account yet?</p>
      <button type="button" id="signUpButton">Sign Up</button>
    </div>
  </div>

  <div class="auth-container" id="signup" style="display: <?php echo $showSignup ? 'block' : 'none'; ?>;">
    <h1 class="form-title">Register</h1>
    <?php if ($error && $showSignup) { ?>
      <p class="error" style="color:#9b2c2c; text-align:center; margin-bottom:1rem;"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <form method="post" class="auth-form">
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="name" id="signup-name" placeholder="Name" required>
        <label for="signup-name">Name</label>
      </div>
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" id="signup-email" placeholder="Email" required>
        <label for="signup-email">Email</label>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="signup-password" placeholder="Password" required>
        <label for="signup-password">Password</label>
      </div>
      <input type="submit" class="btn" value="Sign Up" name="signUp">
    </form>
    <div class="links">
      <p>Already Have Account ?</p>
      <button type="button" id="signInButton">Sign In</button>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<?php if ($showLogoutPopup) { ?>
<script>
  ToastSystem.show('You have logged out.', 'info');
</script>
<?php } ?>
