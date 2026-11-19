<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Dashboard';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();

$stmt = $pdo->prepare("SELECT COUNT(*) AS plans, COALESCE(SUM(budget_amount),0) AS budget FROM projects WHERE user_id = ?");
$stmt->execute([$userId]);
$projectSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) AS parts, COALESCE(SUM(pbp.price),0) AS planned FROM project_build_parts pbp INNER JOIN projects p ON p.project_id = pbp.project_id WHERE p.user_id = ?");
$stmt->execute([$userId]);
$partSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings_records WHERE user_id = ?");
$stmt->execute([$userId]);
$totalSavings = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM actual_expenses WHERE user_id = ?");
$stmt->execute([$userId]);
$totalExpenses = (float)$stmt->fetchColumn();

$totalBudget = (float)$projectSummary['budget'];
$plannedCost = (float)$partSummary['planned'];
$remainingBudget = $totalBudget - $plannedCost;
$remainingSavings = max(0, $totalBudget - $totalSavings);

$stmt = $pdo->prepare("SELECT p.project_id, p.project_name, p.budget_amount, p.status, p.created_at, bp.bicycle_type, bp.bicycle_brand, bp.bicycle_model, COUNT(pbp.build_part_id) AS selected_parts, COALESCE(SUM(pbp.price),0) AS planned_cost FROM projects p LEFT JOIN builder_plans bp ON bp.project_id = p.project_id LEFT JOIN project_build_parts pbp ON pbp.project_id = p.project_id WHERE p.user_id = ? GROUP BY p.project_id ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="dashboard-header">
    <div><p class="eyebrow">YOUR CYCLING WORKSPACE</p><h1>Welcome, <?= h($_SESSION['user_name'] ?? 'Cyclist') ?>!</h1><p>Here's an overview of your bicycle plans and budget.</p></div>
    <a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build New Bike</a>
</div>
<section class="dashboard-stats">
    <article class="stat-card"><span>Total Build Budget</span><h2><?= money($totalBudget) ?></h2><p>Combined budgets for your build plans</p></article>
    <article class="stat-card"><span>Planned Parts Cost</span><h2><?= money($plannedCost) ?></h2><p>Estimated value of selected parts</p></article>
    <article class="stat-card"><span>Total Savings</span><h2><?= money($totalSavings) ?></h2><p>Money recorded toward your plans</p></article>
    <article class="stat-card"><span>Actual Expenses</span><h2><?= money($totalExpenses) ?></h2><p>Recorded cycling and maintenance expenses</p></article>
</section>
<section class="dashboard-overview">
    <article class="overview-card"><h3>Unallocated Build Budget</h3><div class="overview-number"><?= money($remainingBudget) ?></div><p>Combined build budget minus planned parts cost.</p><a href="<?= h(url('projects.php')) ?>">View My Builds →</a></article>
    <article class="overview-card"><h3>Remaining Savings Goal</h3><div class="overview-number"><?= money($remainingSavings) ?></div><p>Amount still needed to reach your combined build budget.</p><a href="<?= h(url('savings.php')) ?>">Track Savings →</a></article>
    <article class="overview-card"><h3>Selected Bicycle Parts</h3><div class="overview-number"><?= number_format((int)$partSummary['parts']) ?></div><p>Components selected across your saved bicycle plans.</p><a href="<?= h(url('projects.php')) ?>">View Plans →</a></article>
</section>
<section class="dashboard-projects">
    <div class="section-heading"><div><h2>My Bicycle Plans</h2><p><?= (int)$projectSummary['plans'] ?> saved plan(s)</p></div><a href="<?= h(url('projects.php')) ?>">View All Plans →</a></div>
    <?php if (!$projects): ?>
        <div class="empty-state"><h3>Your first bicycle build starts here</h3><p>Choose a bicycle type, select its components from live product listings, and see the estimated cost update automatically.</p><a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">Build Your First Bike</a></div>
    <?php else: ?>
        <div class="project-list">
            <?php foreach ($projects as $project): ?>
                <article class="project-card">
                    <div class="project-card-main"><div class="project-bike-icon">🚲</div><div class="project-card-details"><h3><?= h($project['project_name']) ?></h3><p><?= h($project['bicycle_type'] ?: 'Custom Build') ?><?= $project['bicycle_brand'] ? ' · ' . h($project['bicycle_brand']) : '' ?><?= $project['bicycle_model'] ? ' · ' . h($project['bicycle_model']) : '' ?></p><p><?= (int)$project['selected_parts'] ?> part(s) selected · <?= h($project['status']) ?></p></div></div>
                    <div class="project-card-budget"><span>Estimated Build Cost</span><strong><?= money($project['planned_cost']) ?></strong><small>Target budget: <?= money($project['budget_amount']) ?></small><a href="<?= h(url('builder/index.php?project_id=' . (int)$project['project_id'])) ?>">Continue Building →</a></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
