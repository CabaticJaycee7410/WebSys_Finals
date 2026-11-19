<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$isBuilderPage = ($pageTitle === 'Bicycle Builder');
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= h(url('assets/css/style.css')) ?>?v=<?= APP_VERSION ?>">
    <?php if ($isBuilderPage): ?>
        <link rel="stylesheet" href="<?= h(url('assets/css/builder-wizard.css')) ?>?v=<?= APP_VERSION ?>">
    <?php endif; ?>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= h(url(logged_in() ? 'dashboard.php' : 'index.php')) ?>">
        <span class="brand-icon">🚲</span><span><?= h(APP_NAME) ?></span>
    </a>
    <nav>
        <?php if (logged_in()): ?>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= h(url('dashboard.php')) ?>">Dashboard</a>
            <a class="<?= $currentPage === 'bicycles.php' ? 'active' : '' ?>" href="<?= h(url('bicycles.php')) ?>">My Bicycles</a>
            <a class="<?= $currentPage === 'projects.php' ? 'active' : '' ?>" href="<?= h(url('projects.php')) ?>">My Builds</a>
            <a class="<?= $isBuilderPage ? 'active' : '' ?>" href="<?= h(url('builder/index.php')) ?>">Bicycle Builder</a>
            <a href="<?= h(url('catalog.php')) ?>">Parts Catalog</a>
            <a href="<?= h(url('marketplaces.php')) ?>">Marketplace</a>
            <a href="<?= h(url('expenses.php')) ?>">Expenses</a>
            <a href="<?= h(url('savings.php')) ?>">Savings</a>
            <a href="<?= h(url('builder/parts.php')) ?>">Parts Guide</a>
            <a href="<?= h(url('builder/compatibility.php')) ?>">Compatibility</a>
            <a class="logout-link" href="<?= h(url('logout.php')) ?>">Logout</a>
        <?php else: ?>
            <a href="<?= h(url('login.php')) ?>">Login</a>
            <a href="<?= h(url('register.php')) ?>">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php if ($flash = get_flash()): ?>
    <div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
