<?php

require_once __DIR__ . '/includes/functions.php';

if (logged_in()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {

        $error = 'Enter your name, a valid email, and a password with at least 8 characters.';

    } else {

        try {

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (full_name, email, password_hash, role)
                VALUES (?, ?, ?, ?)'
            );

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'user'
            ]);

            flash('Account created. You can now log in.');

            header('Location: ' . url('login.php'));
            exit;

        } catch (PDOException $e) {

            $error = 'That email may already be registered.';

        }
    }
}

$pageTitle = 'Create account';

require __DIR__ . '/includes/header.php';

?>

<section class="auth-card">

    <h1>Create account</h1>

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
            Full name

            <input
                type="text"
                name="full_name"
                required
                maxlength="100"
            >
        </label>

        <label>
            Email

            <input
                type="email"
                name="email"
                required
                maxlength="150"
            >
        </label>

        <label>
            Password (at least 8 characters)

            <input
                type="password"
                name="password"
                minlength="8"
                required
            >
        </label>

        <button class="btn" type="submit">
            Register
        </button>

    </form>

    <p>
        Already registered?

        <a href="<?= h(url('login.php')) ?>">
            Log in
        </a>
    </p>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>