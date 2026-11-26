
<?php
require_once __DIR__ . '/includes/functions.php';

if (logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Login';
$pdo = require __DIR__ . '/config/database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $email = strtolower(trim((string)(isset($_POST['email']) ? $_POST['email'] : '')));
        $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'The email or password is incorrect.';
        } else {
            $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, role, profile_picture FROM users WHERE email = ? LIMIT 1');
            $stmt->execute(array($email));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
                    $update->execute(array($newPasswordHash, $user['user_id']));
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_profile_picture'] = $user['profile_picture'];

                redirect('dashboard.php');
            } else {
                $error = 'The email or password is incorrect.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<style>
html,body.login-screen{height:100%;min-height:0;margin:0;overflow:hidden}
body.login-screen{background:#071c30}
.login-main{width:100%;max-width:none;height:100%;min-height:0;margin:0;padding:0;overflow:hidden}
.login-main>.alert{display:none}
.login-page{width:100%;height:100vh;height:100svh;min-height:0;margin:0;padding:clamp(12px,2.5vh,28px) clamp(16px,3vw,55px);display:flex;align-items:center;justify-content:center;box-sizing:border-box;overflow:hidden}
.login-layout{width:100%;height:100%;max-width:1550px;max-height:900px;display:grid;grid-template-columns:minmax(0,1.15fr) minmax(340px,.85fr);gap:clamp(20px,3vw,55px);align-items:center;min-height:0}
.login-showcase{height:100%;min-height:0;padding:clamp(22px,3vh,40px) clamp(25px,3vw,48px);border-radius:22px;display:flex;flex-direction:column;justify-content:space-between;box-sizing:border-box;overflow:hidden}
.login-showcase-content h1{font-size:clamp(38px,4.2vw,64px);line-height:1.04;margin:12px 0}
.login-showcase-content p{font-size:clamp(12px,1.1vw,15px);line-height:1.65;max-width:520px}
.login-bike-art{width:100%;max-width:560px;height:clamp(120px,25vh,240px);margin:5px auto;flex-shrink:1}
.login-bike-art svg{width:100%;height:100%;display:block}
.login-showcase-bottom{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:10px}
.login-form-area{width:100%;max-height:100%;display:flex;align-items:center;justify-content:center;min-height:0;overflow:hidden}
.login-card{width:100%;max-width:480px;max-height:100%;padding:clamp(20px,3vh,36px) clamp(22px,3vw,40px);border-radius:20px;box-sizing:border-box;overflow:hidden}
.login-card-heading h2{font-size:clamp(25px,2.4vw,32px);margin:8px 0}
.login-card-heading p{font-size:13px;margin:5px 0 17px}
.login-card-icon{width:42px;height:42px}
.login-input-group{margin-bottom:15px}
.login-input-wrap{min-height:48px}
.login-input-wrap input{font-size:14px}
.login-submit,.login-register-button{min-height:48px;font-size:13px}
.login-security-note{margin-top:16px;font-size:11px}
.login-copyright{display:none!important}
.login-main>.alert{display:block;position:absolute;z-index:10;top:8px;left:50%;transform:translateX(-50%);width:min(90%,500px);margin:0}
@media(max-width:900px){
.login-page{padding:12px}
.login-layout{display:flex;justify-content:center;align-items:center;max-width:500px}
.login-showcase{display:none}
.login-form-area{width:100%;max-width:460px;max-height:100%;overflow:visible}
.login-card{max-width:100%}
}
@media(max-width:520px){
.login-page{padding:10px}
.login-layout{max-width:430px}
.login-card{padding:22px 20px;border-radius:17px}
.login-card-heading h2{font-size:27px}
.login-card-heading p{font-size:12px;margin-bottom:14px}
.login-card-icon{width:38px;height:38px}
.login-input-group{margin-bottom:13px}
.login-input-wrap{min-height:46px}
.login-submit,.login-register-button{min-height:46px}
.login-security-note{margin-top:13px}
}
@media(max-height:650px){
.login-page{padding-top:7px;padding-bottom:7px}
.login-showcase{padding-top:15px;padding-bottom:15px}
.login-showcase-content h1{font-size:clamp(30px,5vh,43px)}
.login-bike-art{height:clamp(75px,17vh,130px)}
.login-card{padding-top:15px;padding-bottom:15px}
.login-card-heading h2{font-size:24px}
.login-card-heading p{margin-bottom:10px}
.login-card-icon{width:32px;height:32px}
.login-input-group{margin-bottom:9px}
.login-input-wrap{min-height:42px}
.login-submit,.login-register-button{min-height:42px}
.login-security-note{margin-top:9px}
}
@media(max-width:900px) and (max-height:560px){
.login-card{padding:12px 20px}
.login-card-heading h2{font-size:22px;margin:4px 0}
.login-card-heading p{margin:3px 0 8px}
.login-card-icon{display:none}
.login-input-group{margin-bottom:7px}
.login-input-wrap{min-height:38px}
.login-submit,.login-register-button{min-height:38px}
.login-security-note{margin-top:7px}
.login-card-divider{margin:9px 0}
}
</style>

<section class="login-page">
    <div class="login-decoration login-decoration-one"></div>
    <div class="login-decoration login-decoration-two"></div>
    <div class="login-decoration login-decoration-three"></div>
    <div class="login-layout">
        <div class="login-showcase">
            <div class="login-showcase-brand">
                <div class="login-showcase-logo">
                    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="15" cy="43" r="11"/>
                            <circle cx="49" cy="43" r="11"/>
                            <path d="M15 43L27 22L38 43H15L32 32L49 43M25 22H33M27 22L38 43M43 20H49L52 25M23 18H30"/>
                        </g>
                        <circle cx="27" cy="22" r="3" fill="#08c5d8"/>
                    </svg>
                </div>
                <div>
                    <strong>BICYCLE</strong>
                    <span>BUDGETING SYSTEM</span>
                </div>
            </div>
            <div class="login-showcase-content">
                <span class="login-overline">YOUR CYCLING JOURNEY STARTS HERE</span>
                <h1>Build Better.<br><span>Ride Smarter.</span></h1>
                <p>Manage your bicycle projects, organize your expenses, compare components, and keep your cycling goals on track.</p>
            </div>
            <div class="login-bike-art">
                <div class="login-bike-glow"></div>
                <svg viewBox="0 0 560 300" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Decorative bicycle illustration">
                    <defs>
                        <linearGradient id="bikeFrame" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#12d6e5"/>
                            <stop offset="1" stop-color="#1685bc"/>
                        </linearGradient>
                    </defs>
                    <g fill="none" stroke="#6b91a9" stroke-width="2" opacity=".35">
                        <path d="M15 250H545M40 225H520M70 200H490"/>
                    </g>
                    <g fill="none" stroke="#d7f8ff" stroke-width="3" opacity=".8">
                        <circle cx="145" cy="205" r="79"/>
                        <circle cx="415" cy="205" r="79"/>
                    </g>
                    <g fill="none" stroke="#08c5d8" stroke-width="1.5" opacity=".45">
                        <circle cx="145" cy="205" r="68"/>
                        <circle cx="415" cy="205" r="68"/>
                        <path d="M145 126V284M66 205H224M89 149L201 261M89 261L201 149M415 126V284M336 205H494M359 149L471 261M359 261L471 149"/>
                    </g>
                    <g fill="none" stroke="url(#bikeFrame)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M145 205L235 83L320 205L145 205L258 145L415 205L320 205"/>
                        <path d="M235 83L270 83M224 72H250M320 205L344 119"/>
                    </g>
                    <g fill="none" stroke="#d7f8ff" stroke-width="6" stroke-linecap="round">
                        <path d="M220 76H251M332 113H358"/>
                    </g>
                    <g fill="#08c5d8">
                        <circle cx="145" cy="205" r="8"/>
                        <circle cx="415" cy="205" r="8"/>
                        <circle cx="320" cy="205" r="7"/>
                    </g>
                    <path d="M60 290H500" stroke="#08c5d8" stroke-width="3" opacity=".7"/>
                </svg>
            </div>
            <div class="login-showcase-bottom">
                <span><i></i> PLAN YOUR BUILDS</span>
                <span><i></i> TRACK YOUR EXPENSES</span>
                <span><i></i> MANAGE YOUR BUDGET</span>
            </div>
        </div>
        <div class="login-form-area">
            <div class="login-card">
                <div class="login-card-heading">
                    <div class="login-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                    </div>
                    <span class="login-card-label">ACCOUNT ACCESS</span>
                    <h2>Welcome Back!</h2>
                    <p>Log in to continue your cycling journey.</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert error"><?= h($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="login-input-group">
                        <label for="email">Email Address</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="m3 7 9 6 9-6"/>
                            </svg>
                            <input id="email" type="email" name="email" placeholder="Enter your email address" autocomplete="username" required value="<?= h(isset($_POST['email']) ? $_POST['email'] : '') ?>">
                        </div>
                    </div>
                    <div class="login-input-group">
                        <label for="password">Password</label>
                        <div class="login-input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="4" y="10" width="16" height="11" rx="2"/>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>
                            </svg>
                            <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="login-password-toggle" id="togglePassword" aria-label="Show password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="login-submit">
                        <span>Log In to Your Account</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </form>
                <div class="login-card-divider"><span>NEW TO THE SYSTEM?</span></div>
                <a class="login-register-button" href="<?= h(url('register.php')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Create Your Account
                </a>
                <div class="login-security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    <span>Your account information is protected.</span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var toggle = document.getElementById('togglePassword');
    var password = document.getElementById('password');

    if (toggle && password) {
        toggle.addEventListener('click', function() {
            var show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
