
<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Logout';
$isLogoutPage = true;

if (!logged_in()) {
    redirect('login.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        header('Location: ' . url('login.php'));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.logout-page{min-height:calc(100vh - 40px);display:flex;align-items:center;justify-content:center;padding:30px 20px;box-sizing:border-box}
.logout-card{width:100%;max-width:620px;padding:55px 48px;text-align:center;background:linear-gradient(145deg,rgba(22,68,93,.92),rgba(10,39,62,.94));border:1px solid rgba(8,185,208,.35);border-radius:26px;box-shadow:0 24px 65px rgba(0,0,0,.3);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}
.logout-icon{width:100px;height:100px;margin:0 auto 26px;border-radius:28px;display:flex;align-items:center;justify-content:center;background:rgba(8,185,208,.13);border:1px solid rgba(8,185,208,.35);color:#08c5d8}
.logout-icon svg{width:48px;height:48px}
.logout-card h1{margin:0 0 13px;color:#a5e5e9;font-size:clamp(30px,4vw,40px);font-weight:800;letter-spacing:-1px}
.logout-card p{max-width:410px;margin:0 auto 32px;color:#b9dde4;font-size:16px;line-height:1.7}
.logout-actions{display:flex;justify-content:center;gap:14px;flex-wrap:wrap}
.logout-button{min-height:56px;min-width:190px;padding:0 25px;border:1px solid rgba(8,185,208,.4);border-radius:13px;font-size:15px;font-weight:750;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box;transition:.2s}
.logout-confirm{background:#08b9d0;color:#082d43}
.logout-confirm:hover{background:#28cce0;transform:translateY(-2px)}
.logout-cancel{background:rgba(8,185,208,.1);color:#a5e5e9}
.logout-cancel:hover{background:rgba(8,185,208,.2);transform:translateY(-2px)}
.logout-error{margin:0 auto 22px;padding:13px 16px;border:1px solid rgba(255,125,125,.4);border-radius:12px;background:rgba(130,35,50,.25);color:#ffd0d0;font-size:14px}
@media(max-width:600px){.logout-page{min-height:calc(100vh - 20px);padding:18px}.logout-card{padding:38px 22px}.logout-icon{width:82px;height:82px}.logout-actions{flex-direction:column}.logout-button{width:100%}}
</style>

<div class="logout-page">
    <section class="logout-card">
        <div class="logout-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 17l5-5-5-5"/>
                <path d="M15 12H3"/>
                <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
            </svg>
        </div>
        <h1>Ready to Leave?</h1>
        <p>Are you sure you want to log out of your Bicycle Budgeting System account?</p>
        <?php if ($error !== ''): ?>
            <div class="logout-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= h(url('logout.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="logout-actions">
                <button type="submit" class="logout-button logout-confirm">Confirm Logout</button>
                <a href="<?= h(url('dashboard.php')) ?>" class="logout-button logout-cancel">Cancel</a>
            </div>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
