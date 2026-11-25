
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

$stmt = $pdo->prepare(
    "SELECT
        b.bicycle_id,
        b.bicycle_name,
        b.bicycle_type,
        b.brand,
        b.model,
        p.project_id,
        p.project_name,
        p.budget_amount,
        p.status,
        bp.bicycle_type AS plan_bicycle_type,
        bp.bicycle_brand,
        bp.bicycle_model,
        COALESCE(parts.selected_parts, 0) AS selected_parts,
        COALESCE(parts.planned_cost, 0) AS planned_cost
     FROM bicycles b
     LEFT JOIN projects p ON p.project_id = (
        SELECT p2.project_id
        FROM projects p2
        WHERE p2.bicycle_id = b.bicycle_id AND p2.user_id = b.user_id
        ORDER BY p2.created_at DESC, p2.project_id DESC
        LIMIT 1
     )
     LEFT JOIN (
        SELECT project_id, MAX(bicycle_type) AS bicycle_type, MAX(bicycle_brand) AS bicycle_brand, MAX(bicycle_model) AS bicycle_model
        FROM builder_plans
        GROUP BY project_id
     ) bp ON bp.project_id = p.project_id
     LEFT JOIN (
        SELECT project_id, COUNT(*) AS selected_parts, COALESCE(SUM(price), 0) AS planned_cost
        FROM project_build_parts
        GROUP BY project_id
     ) parts ON parts.project_id = p.project_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
$stmt->execute(array($userId));
$garageItems = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        p.project_id,
        p.project_name,
        p.budget_amount,
        p.status,
        bp.bicycle_type,
        bp.bicycle_brand,
        bp.bicycle_model,
        COALESCE(parts.selected_parts, 0) AS selected_parts,
        COALESCE(parts.planned_cost, 0) AS planned_cost
     FROM projects p
     LEFT JOIN (
        SELECT project_id, MAX(bicycle_type) AS bicycle_type, MAX(bicycle_brand) AS bicycle_brand, MAX(bicycle_model) AS bicycle_model
        FROM builder_plans
        GROUP BY project_id
     ) bp ON bp.project_id = p.project_id
     LEFT JOIN (
        SELECT project_id, COUNT(*) AS selected_parts, COALESCE(SUM(price), 0) AS planned_cost
        FROM project_build_parts
        GROUP BY project_id
     ) parts ON parts.project_id = p.project_id
     WHERE p.user_id = ? AND (p.bicycle_id IS NULL OR p.bicycle_id = 0)
     ORDER BY p.created_at DESC
     LIMIT 8"
);
$stmt->execute(array($userId));
$unassignedPlans = $stmt->fetchAll();

$totalBudget = (float)$projectSummary['budget'];
$plannedCost = (float)$partSummary['planned'];
$remainingBudget = $totalBudget - $plannedCost;
$remainingSavings = max(0, $totalBudget - $totalSavings);

require __DIR__ . '/includes/header.php';
?>

<style>
.dashboard-garage-card{background:rgba(22,68,93,.7);border:1px solid rgba(120,205,220,.25);border-radius:14px;padding:20px;margin-bottom:15px;box-shadow:0 9px 28px rgba(0,15,30,.22);backdrop-filter:blur(17px);-webkit-backdrop-filter:blur(17px);transition:background .2s,border-color .2s}
.dashboard-garage-card:hover{background:rgba(29,83,106,.76);border-color:rgba(8,197,216,.4)}
.dashboard-garage-bike{display:flex;align-items:center;gap:14px}
.dashboard-garage-bike .project-bike-icon{flex-shrink:0;background:rgba(8,185,208,.17);border:1px solid rgba(8,197,216,.25);border-radius:12px}
.dashboard-garage-bike h3{margin:0 0 5px;color:#e0f5f8}
.dashboard-garage-bike p{margin:3px 0;color:#a9cbd6;font-size:13px}
.dashboard-garage-plan{display:flex;justify-content:space-between;align-items:center;gap:15px;background:rgba(10,43,64,.56);border:1px solid rgba(120,205,220,.2);border-radius:10px;padding:15px;margin-top:17px;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)}
.dashboard-garage-plan h4{margin:0 0 6px;font-size:13px;color:#d9f2f5}
.dashboard-garage-plan p{margin:4px 0;color:#a9cbd6;font-size:12px}
.dashboard-garage-plan-info{min-width:0}
.dashboard-garage-plan-cost{text-align:right;flex-shrink:0}
.dashboard-garage-plan-cost span{display:block;color:#91b8c5;font-size:11px}
.dashboard-garage-plan-cost strong{display:block;color:#53d9e8;font-size:18px;margin:5px 0}
.dashboard-garage-plan-cost small{display:block;color:#a9cbd6;font-size:11px}
.dashboard-garage-plan-cost a{display:inline-block;margin-top:8px;color:#53d9e8;font-size:12px;font-weight:600;text-decoration:none}
.dashboard-garage-plan-cost a:hover{color:#a5e5e9}
.dashboard-garage-empty{margin-top:15px;padding:12px;background:rgba(10,43,64,.5);border:1px dashed rgba(120,205,220,.3);border-radius:8px;color:#a9cbd6;font-size:12px}
.dashboard-unassigned{margin-top:20px}
.dashboard-unassigned .project-card{margin-bottom:12px;background:rgba(22,68,93,.7);border:1px solid rgba(120,205,220,.25);backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px)}
.dashboard-unassigned .project-card-details h3{color:#e0f5f8}
.dashboard-unassigned .project-card-details p{color:#a9cbd6}
.dashboard-unassigned .project-card-budget strong{color:#53d9e8}
.dashboard-unassigned .project-card-budget span,.dashboard-unassigned .project-card-budget small{color:#a9cbd6}
.dashboard-unassigned .project-card-budget a{color:#53d9e8}
.dashboard-projects>.section-heading h2,.dashboard-unassigned .section-heading h3{color:#d9f2f5}
.dashboard-projects>.section-heading p,.dashboard-unassigned .section-heading p{color:#a9cbd6}
.dashboard-projects>.section-heading>a{color:#53d9e8}
@media(max-width:650px){.dashboard-garage-plan{align-items:flex-start;flex-direction:column}.dashboard-garage-plan-cost{text-align:left}}
</style>

<div class="dashboard-header">
    <div>
        <h1>Welcome back, <?= h(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Cyclist') ?>!</h1>
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
        <?php foreach ($garageItems as $item): ?>
            <article class="dashboard-garage-card">
                <div class="dashboard-garage-bike">
                    <div class="project-bike-icon">🚲</div>
                    <div class="project-card-details">
                        <h3><?= h($item['bicycle_name']) ?></h3>
                        <p>
                            <?= h($item['bicycle_type']) ?>
                            <?= !empty($item['brand']) ? ' · ' . h($item['brand']) : '' ?>
                            <?= !empty($item['model']) ? ' · ' . h($item['model']) : '' ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($item['project_id'])): ?>
                    <div class="dashboard-garage-plan">
                        <div class="dashboard-garage-plan-info">
                            <h4><?= h($item['project_name']) ?></h4>
                            <p>
                                <?= h($item['plan_bicycle_type'] ? $item['plan_bicycle_type'] : $item['bicycle_type']) ?>
                                <?= !empty($item['bicycle_brand']) ? ' · ' . h($item['bicycle_brand']) : '' ?>
                                <?= !empty($item['bicycle_model']) ? ' · ' . h($item['bicycle_model']) : '' ?>
                            </p>
                            <p><?= (int)$item['selected_parts'] ?> part(s) selected · <?= h($item['status']) ?></p>
                        </div>
                        <div class="dashboard-garage-plan-cost">
                            <span>Estimated Build Cost</span>
                            <strong><?= money($item['planned_cost']) ?></strong>
                            <small>Target budget: <?= money($item['budget_amount']) ?></small>
                            <a href="<?= h(url('builder/index.php?project_id=' . (int)$item['project_id'])) ?>">Edit Build Plan →</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="dashboard-garage-empty">No saved build plan is associated with this bicycle yet.</div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if ($unassignedPlans): ?>
            <div class="dashboard-unassigned">
                <div class="section-heading">
                    <div>
                        <h3>Other Saved Build Plans</h3>
                        <p>Plans that are not linked to a bicycle profile.</p>
                    </div>
                </div>
                <?php foreach ($unassignedPlans as $plan): ?>
                    <article class="project-card">
                        <div class="project-card-main">
                            <div class="project-bike-icon">🔧</div>
                            <div class="project-card-details">
                                <h3><?= h($plan['project_name']) ?></h3>
                                <p>
                                    <?= h($plan['bicycle_type'] ? $plan['bicycle_type'] : 'Custom Build') ?>
                                    <?= !empty($plan['bicycle_brand']) ? ' · ' . h($plan['bicycle_brand']) : '' ?>
                                    <?= !empty($plan['bicycle_model']) ? ' · ' . h($plan['bicycle_model']) : '' ?>
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
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
