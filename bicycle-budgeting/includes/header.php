
<?php
require_once __DIR__ . '/functions.php';
$pageTitle = isset($pageTitle) ? $pageTitle : APP_NAME;
$isBuilderPage = ($pageTitle === 'Bicycle Builder');
$currentPage = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
$isGaragePage = ($currentPage === 'bicycles.php' || $currentPage === 'projects.php');
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
<body>
<header class="topbar">
    <a class="brand" href="<?= h(url(logged_in() ? 'dashboard.php' : 'index.php')) ?>">
        <span class="brand-icon">🚲</span>
        <span><?= h(APP_NAME) ?></span>
    </a>
    <nav>
        <?php if (logged_in()): ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= h(url('dashboard.php')) ?>">Dashboard</a>
            <a class="<?= $isGaragePage ? 'active' : '' ?>" href="<?= h(url('bicycles.php')) ?>">My Garage</a>
            <a class="<?= $isBuilderPage ? 'active' : '' ?>" href="<?= h(url('builder/index.php')) ?>">Bicycle Builder</a>
            <a class="<?= $currentPage === 'marketplaces.php' ? 'active' : '' ?>" href="<?= h(url('marketplaces.php')) ?>">Marketplace</a>
            <a class="<?= $currentPage === 'expenses.php' ? 'active' : '' ?>" href="<?= h(url('expenses.php')) ?>">Expenses</a>
            <a class="<?= $currentPage === 'savings.php' ? 'active' : '' ?>" href="<?= h(url('savings.php')) ?>">Savings</a>
            <a class="logout-link" href="<?= h(url('logout.php')) ?>">Logout</a>
        <?php else: ?>
            <a class="<?= $currentPage === 'login.php' ? 'active' : '' ?>" href="<?= h(url('login.php')) ?>">Login</a>
            <a class="<?= $currentPage === 'register.php' ? 'active' : '' ?>" href="<?= h(url('register.php')) ?>">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php if ($flash = get_flash()): ?>
    <div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
