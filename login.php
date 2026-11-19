<?php

require_once __DIR__ . '/includes/functions.php';

if (logged_in()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, password_hash
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (
        $user &&
        password_verify(
            $_POST['password'] ?? '',
            $user['password_hash']
        )
    ) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['user_id'];

        $_SESSION['full_name'] = $user['full_name'];

        header('Location: ' . url('dashboard.php'));
        exit;

    } else {

        $error = 'Incorrect email or password.';

    }
}

$pageTitle = 'Login';

require __DIR__ . '/includes/header.php';

?>

<section class="auth-card">

    <h1>Welcome back</h1>

    <?php if ($error): ?>

        <div class="alert error">
            <?= h($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= h(csrf_token()) ?>"
        >

        <label>
            Email

            <input
                type="email"
                name="email"
                required
            >
        </label>

        <label>
            Password

            <input
                type="password"
                name="password"
                required
            >
        </label>

        <button class="btn" type="submit">
            Log in
        </button>

    </form>

    <p>
        No account yet?

        <a href="<?= h(url('register.php')) ?>">
            Register here
        </a>
    </p>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>