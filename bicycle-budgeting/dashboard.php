
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();

$stmt = $pdo->prepare("SELECT COUNT(*) AS plans, COALESCE(SUM(budget_amount),0) AS budget FROM projects WHERE user_id = ?");
$stmt->execute(array($userId));
$projectSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) AS parts, COALESCE(SUM(pbp.price),0) AS planned FROM project_build_parts pbp INNER JOIN projects p ON p.project_id = pbp.project_id WHERE p.user_id = ?");
$stmt->execute(array($userId));
$partSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings_records WHERE user_id = ?");
$stmt->execute(array($userId));
$totalSavings = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bicycles WHERE user_id = ?");
$stmt->execute(array($userId));
$totalBicycles = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT b.bicycle_id, b.bicycle_name, b.bicycle_type, b.brand, b.model, p.project_id, p.project_name, p.budget_amount, p.status, bp.bicycle_type AS plan_bicycle_type, bp.bicycle_brand, bp.bicycle_model, COUNT(pbp.build_part_id) AS selected_parts, COALESCE(SUM(pbp.price),0) AS planned_cost FROM bicycles b LEFT JOIN projects p ON p.bicycle_id = b.bicycle_id AND p.user_id = b.user_id LEFT JOIN builder_plans bp ON bp.project_id = p.project_id LEFT JOIN project_build_parts pbp ON pbp.project_id = p.project_id WHERE b.user_id = ? GROUP BY b.bicycle_id, p.project_id ORDER BY b.created_at DESC, p.created_at DESC LIMIT 20");
$stmt->execute(array($userId));
$garageItems = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT p.project_id, p.project_name, p.budget_amount, p.status, bp.bicycle_type, bp.bicycle_brand, bp.bicycle_model, COUNT(pbp.build_part_id) AS selected_parts, COALESCE(SUM(pbp.price),0) AS planned_cost FROM projects p LEFT JOIN builder_plans bp ON bp.project_id = p.project_id LEFT JOIN project_build_parts pbp ON pbp.project_id = p.project_id WHERE p.user_id = ? AND (p.bicycle_id IS NULL OR p.bicycle_id = 0) GROUP BY p.project_id ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute(array($userId));
$unassignedPlans = $stmt->fetchAll();

$totalBudget = (float)$projectSummary['budget'];
$plannedCost = (float)$partSummary['planned'];
$remainingBudget = $totalBudget - $plannedCost;
$remainingSavings = max(0, $totalBudget - $totalSavings);

require __DIR__ . '/includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <p class="eyebrow">YOUR CYCLING WORKSPACE</p>
        <h1>Welcome, <?= h(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Cyclist') ?>!</h1>
        <p>Here's an overview of your bicycles, build plans, and budget.</p>
    </div>
    <a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build New Bike</a>
</div>

<section class="dashboard-stats">
    <article class="stat-card">
        <span>Total Bicycles</span>
        <h2><?= number_format($totalBicycles) ?></h2>
        <p>Bicycles registered in your account</p>
    </article>
    <article class="stat-card">
        <span>Total Build Budget</span>
        <h2><?= money($totalBudget) ?></h2>
        <p>Combined budgets for your build plans</p>
    </article>
    <article class="stat-card">
        <span>Planned Parts Cost</span>
        <h2><?= money($plannedCost) ?></h2>
        <p>Estimated value of selected parts</p>
    </article>
    <article class="stat-card">
        <span>Total Savings</span>
        <h2><?= money($totalSavings) ?></h2>
        <p>Money recorded toward your plans</p>
    </article>
</section>

<section class="dashboard-overview">
    <article class="overview-card">
        <h3>Unallocated Build Budget</h3>
        <div class="overview-number"><?= money($remainingBudget) ?></div>
        <p>Combined build budget minus planned parts cost.</p>
        <a href="<?= h(url('bicycles.php')) ?>">View My Garage →</a>
    </article>
    <article class="overview-card">
        <h3>Remaining Savings Goal</h3>
        <div class="overview-number"><?= money($remainingSavings) ?></div>
        <p>Amount still needed to reach your combined build budget.</p>
        <a href="<?= h(url('savings.php')) ?>">Track Savings →</a>
    </article>
    <article class="overview-card">
        <h3>Selected Bicycle Parts</h3>
        <div class="overview-number"><?= number_format((int)$partSummary['parts']) ?></div>
        <p>Components selected across your saved bicycle plans.</p>
        <a href="<?= h(url('bicycles.php')) ?>">View Garage →</a>
    </article>
</section>

<section class="dashboard-projects">
    <div class="section-heading">
        <div>
            <h2>My Garage</h2>
            <p><?= number_format($totalBicycles) ?> bicycle(s) · <?= number_format((int)$projectSummary['plans']) ?> saved build plan(s)</p>
        </div>
        <a href="<?= h(url('bicycles.php')) ?>">Open My Garage →</a>
    </div>

    <?php if (!$garageItems && !$unassignedPlans): ?>
        <div class="empty-state">
            <h3>Your garage is waiting for its first bicycle</h3>
            <p>Create your first bicycle and build plan using the Bicycle Builder. You can manage your bicycles and saved plans together in My Garage.</p>
            <a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build Your First Bike</a>
        </div>
    <?php else: ?>
        <div class="project-list">
            <?php
            $displayedBicycles = array();
            foreach ($garageItems as $item):
                $bicycleId = (int)$item['bicycle_id'];
                if (!isset($displayedBicycles[$bicycleId])):
                    $displayedBicycles[$bicycleId] = true;
            ?>
                <article class="project-card">
                    <div class="project-card-main">
                        <div class="project-bike-icon">🚲</div>
                        <div class="project-card-details">
                            <h3><?= h($item['bicycle_name']) ?></h3>
                            <p>
                                <?= h($item['bicycle_type']) ?>
                                <?= $item['brand'] ? ' · ' . h($item['brand']) : '' ?>
                                <?= $item['model'] ? ' · ' . h($item['model']) : '' ?>
                            </p>
                        </div>
                    </div>
                    <div class="project-card-budget">
                        <span>My Bicycle</span>
                        <strong><?= h($item['bicycle_type']) ?></strong>
                        <a href="<?= h(url('bicycles.php')) ?>">Manage in Garage →</a>
                    </div>
                </article>
                <?php endif; ?>

                <?php if (!empty($item['project_id'])): ?>
                    <article class="project-card">
                        <div class="project-card-main">
                            <div class="project-bike-icon">🔧</div>
                            <div class="project-card-details">
                                <h3><?= h($item['project_name']) ?></h3>
                                <p>
                                    <?= h($item['plan_bicycle_type'] ? $item['plan_bicycle_type'] : $item['bicycle_type']) ?>
                                    <?= $item['bicycle_brand'] ? ' · ' . h($item['bicycle_brand']) : '' ?>
                                    <?= $item['bicycle_model'] ? ' · ' . h($item['bicycle_model']) : '' ?>
                                </p>
                                <p><?= (int)$item['selected_parts'] ?> part(s) selected · <?= h($item['status']) ?></p>
                            </div>
                        </div>
                        <div class="project-card-budget">
                            <span>Estimated Build Cost</span>
                            <strong><?= money($item['planned_cost']) ?></strong>
                            <small>Target budget: <?= money($item['budget_amount']) ?></small>
                            <a href="<?= h(url('builder/index.php?project_id=' . (int)$item['project_id'])) ?>">Edit Build Plan →</a>
                        </div>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php foreach ($unassignedPlans as $plan): ?>
                <article class="project-card">
                    <div class="project-card-main">
                        <div class="project-bike-icon">🔧</div>
                        <div class="project-card-details">
                            <h3><?= h($plan['project_name']) ?></h3>
                            <p>
                                <?= h($plan['bicycle_type'] ? $plan['bicycle_type'] : 'Custom Build') ?>
                                <?= $plan['bicycle_brand'] ? ' · ' . h($plan['bicycle_brand']) : '' ?>
                                <?= $plan['bicycle_model'] ? ' · ' . h($plan['bicycle_model']) : '' ?>
                            </p>
                            <p><?= (int)$plan['selected_parts'] ?> part(s) selected · <?= h($plan['status']) ?> · Not linked to a bicycle</p>
                        </div>
                    </div>
                    <div class="project-card-budget">
                        <span>Estimated Build Cost</span>
                        <strong><?= money($plan['planned_cost']) ?></strong>
                        <small>Target budget: <?= money($plan['budget_amount']) ?></small>
                        <a href="<?= h(url('builder/index.php?project_id=' . (int)$plan['project_id'])) ?>">Edit Build Plan →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section-heading" style="margin-top:24px;">
        <p>Manage your bicycle profiles and all their saved build plans in one place.</p>
        <a href="<?= h(url('bicycles.php')) ?>">Go to My Garage →</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
