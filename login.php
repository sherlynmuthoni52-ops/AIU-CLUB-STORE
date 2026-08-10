<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Login
 *
 * Authenticates users and creates a session on success.
 */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = database()->prepare(
        'SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        set_message('Welcome back, ' . $user['name'] . '!');
        header('Location: index.php');
        exit;
    }

    $error = 'Incorrect email or password.';
}

$page_title = 'Log in | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<section class="container">
    <?php if ($error) { ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <div class="login-container">
        <div class="circle circle-one"></div>
        <div class="form-container">
            <img src="https://raw.githubusercontent.com/hicodersofficial/glassmorphism-login-form/master/assets/illustration.png" alt="illustration" class="illustration" />
            <h1 class="opacity">LOGIN</h1>
            <form method="post">
                <input name="email" type="text" placeholder="USERNAME" />
                <input name="password" type="password" placeholder="PASSWORD" />
                <button class="opacity" type="submit">SUBMIT</button>
            </form>
            <div class="register-forget opacity">
                <a href="register.php">REGISTER</a>
                <a href="forgot.php">FORGOT PASSWORD</a>
            </div>
        </div>
        <div class="circle circle-two"></div>
    </div>
    <div class="theme-btn-container"></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
