
<?php
require_once __DIR__ . '/functions.php';
$pageTitle = isset($pageTitle) ? $pageTitle : APP_NAME;
$isBuilderPage = ($pageTitle === 'Bicycle Builder');
$currentPage = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
$isGaragePage = ($currentPage === 'bicycles.php' || $currentPage === 'projects.php');
$isLoginPage = ($currentPage === 'login.php');
$isRegisterPage = ($currentPage === 'register.php');
$isLogoutPage = ($currentPage === 'logout.php');
$isLoggedIn = logged_in();
$profilePicture = isset($_SESSION['user_profile_picture']) ? $_SESSION['user_profile_picture'] : '';
$profilePictureUrl = '';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if ($profilePicture !== '') {
    $profileFile = basename($profilePicture);
    $profilePicturePath = __DIR__ . '/../uploads/profile-pictures/' . $profileFile;
    if (strpos($profilePicture, 'uploads/profile-pictures/') === 0 && is_file($profilePicturePath)) {
        $profilePictureUrl = url('uploads/profile-pictures/' . rawurlencode($profileFile));
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= h(url('assets/css/style.css')) ?>?v=<?= time() ?>">
    <?php if ($isBuilderPage): ?>
        <link rel="stylesheet" href="<?= h(url('assets/css/builder-wizard.css')) ?>?v=<?= time() ?>">
    <?php endif; ?>
</head>
<body class="<?= $isLoginPage ? 'login-screen' : ($isRegisterPage ? 'register-screen' : ($isLogoutPage ? 'logout-screen' : '')) ?>">
<?php if ($isLogoutPage): ?>
    <main class="logout-main">
<?php elseif ($isLoginPage): ?>
    <main class="login-main">
<?php elseif ($isRegisterPage): ?>
    <main class="register-main">
<?php elseif ($isLoggedIn): ?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= h(url('dashboard.php')) ?>">
            <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-label="Bicycle logo" role="img">
                <g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="15" cy="43" r="11"/>
                    <circle cx="49" cy="43" r="11"/>
                    <path d="M15 43L27 22L38 43H15L32 32L49 43M25 22H33M27 22L38 43M43 20H49L52 25M23 18H30"/>
                </g>
                <circle cx="27" cy="22" r="3" fill="#08b9d0"/>
            </svg>
            <span><?= h(APP_NAME) ?></span>
        </a>
        <div class="sidebar-menu-label">MAIN MENU</div>
        <nav class="sidebar-nav">
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= h(url('dashboard.php')) ?>"><span class="sidebar-nav-icon">⌂</span><span>Dashboard</span></a>
            <a class="<?= $isGaragePage ? 'active' : '' ?>" href="<?= h(url('bicycles.php')) ?>"><span class="sidebar-nav-icon">▣</span><span>My Garage</span></a>
            <a class="<?= $isBuilderPage ? 'active' : '' ?>" href="<?= h(url('builder/index.php')) ?>"><span class="sidebar-nav-icon">⚙</span><span>Bicycle Builder</span></a>
            <a class="<?= $currentPage === 'marketplaces.php' ? 'active' : '' ?>" href="<?= h(url('marketplaces.php')) ?>"><span class="sidebar-nav-icon">▤</span><span>Marketplace</span></a>
            <a class="<?= $currentPage === 'expenses.php' ? 'active' : '' ?>" href="<?= h(url('expenses.php')) ?>"><span class="sidebar-nav-icon">₱</span><span>Expenses</span></a>
            <a class="<?= $currentPage === 'savings.php' ? 'active' : '' ?>" href="<?= h(url('savings.php')) ?>"><span class="sidebar-nav-icon">◷</span><span>Savings</span></a>
        </nav>
        <div class="sidebar-profile">
            <details class="sidebar-profile-menu">
                <summary class="sidebar-profile-trigger">
                    <?php if ($profilePictureUrl !== ''): ?>
                        <img class="sidebar-avatar" src="<?= h($profilePictureUrl) ?>" alt="Profile picture">
                    <?php else: ?>
                        <span class="sidebar-avatar sidebar-avatar-initial"><?= h(strtoupper(substr($userName, 0, 1))) ?></span>
                    <?php endif; ?>
                    <span class="sidebar-profile-info">
                        <strong><?= h($userName) ?></strong>
                        <small><?= h($userEmail) ?></small>
                    </span>
                    <span class="sidebar-profile-arrow">⌃</span>
                </summary>
                <div class="sidebar-profile-dropdown">
                    <a href="<?= h(url('account.php?section=picture')) ?>"><span>▣</span> Profile Picture</a>
                    <a href="<?= h(url('account.php?section=personal')) ?>"><span>♙</span> Personal Information</a>
                    <a href="<?= h(url('account.php?section=password')) ?>"><span>⚿</span> Change Password</a>
                    <div class="sidebar-dropdown-divider"></div>
                    <a href="<?= h(url('logout.php')) ?>" class="sidebar-logout"><span>↪</span> Logout</a>
                </div>
            </details>
        </div>
    </aside>
    <div class="app-main">
        <main class="container">
<?php else: ?>
<div class="guest-layout">
    <header class="guest-header">
        <a class="brand" href="<?= h(url('index.php')) ?>">
            <svg class="brand-logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-label="Bicycle logo" role="img">
                <g fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="15" cy="43" r="11"/>
                    <circle cx="49" cy="43" r="11"/>
                    <path d="M15 43L27 22L38 43H15L32 32L49 43M25 22H33M27 22L38 43M43 20H49L52 25M23 18H30"/>
                </g>
                <circle cx="27" cy="22" r="3" fill="#08b9d0"/>
            </svg>
            <span><?= h(APP_NAME) ?></span>
        </a>
        <nav>
            <a class="<?= $currentPage === 'login.php' ? 'active' : '' ?>" href="<?= h(url('login.php')) ?>">Login</a>
            <a class="<?= $currentPage === 'register.php' ? 'active' : '' ?>" href="<?= h(url('register.php')) ?>">Register</a>
        </nav>
    </header>
    <main class="container">
<?php endif; ?>
<?php if ($flash = get_flash()): ?>
    <div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
