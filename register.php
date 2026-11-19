<?php
require_once __DIR__ . '/includes/functions.php';
if (logged_in()) redirect('dashboard.php');
$pageTitle = 'Create Account';
$pdo = require __DIR__ . '/config/database.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($name === '' || strlen($name) > 150) $errors[] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            set_flash('success', 'Account created. You can now log in.');
            redirect('login.php');
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-container">
    <p class="eyebrow">GET STARTED</p>
    <h1>Create Account</h1>
    <p>Start planning your bicycle build and budget.</p>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= h($error) ?></div><?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="form-group"><label for="full_name">Full Name</label><input id="full_name" name="full_name" required maxlength="150" value="<?= h($_POST['full_name'] ?? '') ?>"></div>
        <div class="form-group"><label for="email">Email Address</label><input id="email" type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"></div>
        <div class="form-group"><label for="password">Password</label><input id="password" type="password" name="password" required minlength="8"></div>
        <div class="form-group"><label for="confirm_password">Confirm Password</label><input id="confirm_password" type="password" name="confirm_password" required minlength="8"></div>
        <button class="btn" type="submit" style="width:100%">Create Account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="<?= h(url('login.php')) ?>">Log in</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
