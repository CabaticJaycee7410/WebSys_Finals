
<?php
require_once __DIR__ . '/includes/functions.php';

if (logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Create Account';
$pdo = require __DIR__ . '/config/database.php';
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }

    $name = trim((string)(isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $email = strtolower(trim((string)(isset($_POST['email']) ? $_POST['email'] : '')));
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    $confirm = (string)(isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '');

    if ($name === '' || strlen($name) > 150) {
        $errors[] = 'Enter your name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute(array($email));

        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute(array($name, $email, password_hash($password, PASSWORD_DEFAULT)));
            set_flash('success', 'Account created. You can now log in.');
            redirect('login.php');
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<style>
.register-screen{min-height:100vh;background:linear-gradient(135deg,rgba(5,20,38,.82),rgba(7,55,76,.68)),url('<?= h(url('assets/images/cycling-background.jpg')) ?>') center center/cover fixed no-repeat}
.register-main{width:100%;max-width:none;margin:0;padding:0}
.register-page{width:100%;min-height:100vh;min-height:100svh;padding:30px 22px;display:flex;align-items:center;justify-content:center;box-sizing:border-box}
.register-card{width:100%;max-width:650px;padding:clamp(30px,4vw,48px);background:linear-gradient(145deg,rgba(22,68,93,.93),rgba(10,39,62,.95));border:1px solid rgba(8,185,208,.38);border-radius:25px;box-shadow:0 24px 65px rgba(0,0,0,.35);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);box-sizing:border-box}
.register-heading{text-align:center;margin-bottom:30px}
.register-eyebrow{display:inline-block;margin-bottom:12px;padding:7px 14px;border:1px solid rgba(8,185,208,.35);border-radius:30px;background:rgba(8,185,208,.12);color:#08c5d8;font-size:11px;font-weight:800;letter-spacing:2px}
.register-heading h1{margin:0 0 10px;color:#a5e5e9;font-size:clamp(30px,4vw,39px);font-weight:800;letter-spacing:-1px}
.register-heading p{margin:0;color:#b9dde4;font-size:14px;line-height:1.6}
.register-form{display:grid;grid-template-columns:1fr 1fr;gap:19px}
.register-field{min-width:0}
.register-field.full{grid-column:1/-1}
.register-field label{display:block;margin-bottom:9px;color:#a5e5e9;font-size:13px;font-weight:700}
.register-field input{width:100%;height:53px;padding:0 15px;border:1px solid rgba(8,185,208,.28);border-radius:11px;outline:none;background:rgba(5,28,47,.62);color:#d5f1f4;font-size:14px;box-sizing:border-box;transition:.2s}
.register-field input:focus{border-color:#08c5d8;box-shadow:0 0 0 3px rgba(8,185,208,.13)}
.register-field input::placeholder{color:#78aeb9}
.register-submit{grid-column:1/-1;width:100%;height:56px;margin-top:5px;border:0;border-radius:12px;background:#08b9d0;color:#082d43;font-size:15px;font-weight:800;cursor:pointer;transition:.2s}
.register-submit:hover{background:#28cce0;transform:translateY(-2px)}
.register-errors{margin-bottom:20px;padding:13px 16px;border:1px solid rgba(255,125,125,.35);border-radius:12px;background:rgba(130,35,50,.22);color:#ffd0d0;font-size:13px;line-height:1.6}
.register-switch{text-align:center;margin:25px 0 0;color:#b9dde4;font-size:14px}
.register-switch a{color:#08c5d8;font-weight:800;text-decoration:none}
.register-switch a:hover{text-decoration:underline}
@media(max-width:520px){.register-page{padding:18px 13px}.register-card{padding:29px 20px;border-radius:19px}.register-form{grid-template-columns:1fr;gap:16px}.register-field.full,.register-submit{grid-column:1}.register-heading{margin-bottom:24px}}
</style>

<div class="register-page">
    <section class="register-card">
        <div class="register-heading">
            <span class="register-eyebrow">GET STARTED</span>
            <h1>Create Account</h1>
            <p>Start planning your bicycle builds and managing your cycling budget.</p>
        </div>

        <?php if ($errors): ?>
            <div class="register-errors">
                <?php foreach ($errors as $error): ?>
                    <div><?= h($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= h(url('register.php')) ?>" class="register-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

            <div class="register-field full">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" placeholder="Enter your full name" required maxlength="150" value="<?= h(isset($_POST['full_name']) ? $_POST['full_name'] : '') ?>">
            </div>

            <div class="register-field full">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" placeholder="Enter your email address" required value="<?= h(isset($_POST['email']) ? $_POST['email'] : '') ?>">
            </div>

            <div class="register-field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="At least 8 characters" required minlength="8" autocomplete="new-password">
            </div>

            <div class="register-field">
                <label for="confirm_password">Confirm Password</label>
                <input id="confirm_password" type="password" name="confirm_password" placeholder="Re-enter password" required minlength="8" autocomplete="new-password">
            </div>

            <button class="register-submit" type="submit">Create Account</button>
        </form>

        <p class="register-switch">Already have an account? <a href="<?= h(url('login.php')) ?>">Log in</a></p>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
