<?php
require_once __DIR__ . '/includes/functions.php';
if (logged_in()) redirect('dashboard.php');
$pageTitle = 'Login';
$pdo = require __DIR__ . '/config/database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect('dashboard.php');
        }
        $error = 'The email or password is incorrect.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-container">
    <p class="eyebrow">WELCOME BACK</p>
    <h1>Log In</h1>
    <p>Continue planning your bicycle build.</p>
    <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="form-group"><label for="email">Email Address</label><input id="email" type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"></div>
        <div class="form-group"><label for="password">Password</label><input id="password" type="password" name="password" required></div>
        <button class="btn" type="submit" style="width:100%">Log In</button>
    </form>
    <p class="auth-switch">Don't have an account? <a href="<?= h(url('register.php')) ?>">Create one</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
