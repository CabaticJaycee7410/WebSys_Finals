
<?php

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';
$pdo = require __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| DASHBOARD TOTALS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(p.project_id) AS total_projects,

        COALESCE(SUM(p.budget_amount), 0) AS total_budget,

        COALESCE(SUM(
            CASE
                WHEN bp.project_id IS NOT NULL
                    THEN COALESCE(new_parts.planned_cost, 0)
                ELSE COALESCE(old_parts.planned_cost, 0)
            END
        ), 0) AS planned_cost,

        COALESCE(SUM(
            CASE
                WHEN bp.project_id IS NOT NULL
                    THEN COALESCE(new_parts.selected_parts, 0)
                ELSE COALESCE(old_parts.selected_parts, 0)
            END
        ), 0) AS selected_parts

    FROM projects p

    LEFT JOIN builder_plans bp
        ON bp.project_id = p.project_id

    LEFT JOIN (
        SELECT
            project_id,
            COUNT(*) AS selected_parts,
            SUM(price) AS planned_cost
        FROM project_build_parts
        GROUP BY project_id
    ) new_parts
        ON new_parts.project_id = p.project_id

    LEFT JOIN (
        SELECT
            project_id,
            COUNT(*) AS selected_parts,
            SUM(planned_price * quantity) AS planned_cost
        FROM project_parts
        GROUP BY project_id
    ) old_parts
        ON old_parts.project_id = p.project_id

    WHERE p.user_id = ?
");

$stmt->execute([$userId]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| SAVINGS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM savings_records
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$totalSavings = (float) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| ACTUAL EXPENSES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM actual_expenses
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$totalExpenses = (float) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| CALCULATED VALUES
|--------------------------------------------------------------------------
*/

$totalBudget = (float) ($summary['total_budget'] ?? 0);
$plannedCost = (float) ($summary['planned_cost'] ?? 0);
$selectedParts = (int) ($summary['selected_parts'] ?? 0);
$totalProjects = (int) ($summary['total_projects'] ?? 0);

$unallocatedBudget = $totalBudget - $plannedCost;
$remainingSavings = max(0, $totalBudget - $totalSavings);

/*
|--------------------------------------------------------------------------
| RECENT BICYCLE PLANS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.project_id,
        p.project_name,
        p.budget_amount,
        p.status,
        p.created_at,

        COALESCE(
            bp.bicycle_type,
            b.bicycle_type,
            'Custom Build'
        ) AS bicycle_type,

        COALESCE(
            bp.bicycle_brand,
            b.brand,
            ''
        ) AS bicycle_brand,

        COALESCE(bp.bicycle_model, '') AS bicycle_model,

        CASE
            WHEN bp.project_id IS NOT NULL
                THEN COALESCE(new_parts.selected_parts, 0)
            ELSE COALESCE(old_parts.selected_parts, 0)
        END AS selected_parts,

        CASE
            WHEN bp.project_id IS NOT NULL
                THEN COALESCE(new_parts.planned_cost, 0)
            ELSE COALESCE(old_parts.planned_cost, 0)
        END AS planned_cost

    FROM projects p

    LEFT JOIN bicycles b
        ON b.bicycle_id = p.bicycle_id

    LEFT JOIN builder_plans bp
        ON bp.project_id = p.project_id

    LEFT JOIN (
        SELECT
            project_id,
            COUNT(*) AS selected_parts,
            SUM(price) AS planned_cost
        FROM project_build_parts
        GROUP BY project_id
    ) new_parts
        ON new_parts.project_id = p.project_id

    LEFT JOIN (
        SELECT
            project_id,
            COUNT(*) AS selected_parts,
            SUM(planned_price * quantity) AS planned_cost
        FROM project_parts
        GROUP BY project_id
    ) old_parts
        ON old_parts.project_id = p.project_id

    WHERE p.user_id = ?

    ORDER BY p.created_at DESC
");

$stmt->execute([$userId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';

?>

<div class="dashboard-header">

    <div>
        <h1>Dashboard</h1>

        <p>
            Manage your bicycle builds, budget, and savings in one place.
        </p>
    </div>

    <a href="<?= h(url('builder/index.php')) ?>"
       class="dashboard-new-build">
        + Build New Bike
    </a>

</div>

<!-- MAIN BUDGET OVERVIEW -->

<section class="dashboard-stats">

    <div class="stat-card">
        <span>Total Build Budget</span>

        <h2><?= money($totalBudget) ?></h2>

        <p>Combined budget of your bicycle plans</p>
    </div>

    <div class="stat-card">
        <span>Planned Parts Cost</span>

        <h2><?= money($plannedCost) ?></h2>

        <p>Estimated cost of selected bicycle parts</p>
    </div>

    <div class="stat-card">
        <span>Total Savings</span>

        <h2><?= money($totalSavings) ?></h2>

        <p>Money recorded in your savings tracker</p>
    </div>

    <div class="stat-card">
        <span>Actual Expenses</span>

        <h2><?= money($totalExpenses) ?></h2>

        <p>Recorded maintenance and cycling expenses</p>
    </div>

</section>

<!-- BUDGET OVERVIEW -->

<section class="dashboard-overview">

    <div class="overview-card">

        <h3>Unallocated Budget</h3>

        <div class="overview-number">
            <?= money($unallocatedBudget) ?>
        </div>

        <p>
            Your combined build budget minus your planned parts cost.
        </p>

        <a href="<?= h(url('projects.php')) ?>">
            View My Builds
        </a>

    </div>

    <div class="overview-card">

        <h3>Remaining Savings Goal</h3>

        <div class="overview-number">
            <?= money($remainingSavings) ?>
        </div>

        <p>
            The remaining amount needed to reach your combined build budget.
        </p>

        <a href="<?= h(url('savings.php')) ?>">
            Manage Savings
        </a>

    </div>

    <div class="overview-card">

        <h3>Selected Parts</h3>

        <div class="overview-number">
            <?= number_format($selectedParts) ?>
        </div>

        <p>
            Total components selected across your bicycle plans.
        </p>

        <a href="<?= h(url('projects.php')) ?>">
            View Selected Builds
        </a>

    </div>

</section>

<!-- SAVED BICYCLE PLANS -->

<section class="dashboard-projects">

    <div class="section-heading">

        <div>
            <h2>My Bicycle Plans</h2>

            <p>
                You currently have <?= number_format($totalProjects) ?>
                bicycle plan<?= $totalProjects === 1 ? '' : 's' ?>.
            </p>
        </div>

        <a href="<?= h(url('projects.php')) ?>">
            View All Plans
        </a>

    </div>

    <?php if (empty($projects)): ?>

        <div class="empty-state">

            <h3>No Bicycle Plans Yet</h3>

            <p>
                Start planning your bicycle by selecting a bicycle type
                and choosing its components.
            </p>

            <a href="<?= h(url('builder/index.php')) ?>"
               class="dashboard-new-build">
                Build Your First Bike
            </a>

        </div>

    <?php else: ?>

        <div class="project-list">

            <?php foreach ($projects as $project): ?>

                <article class="project-card">

                    <div class="project-card-main">

                        <div class="project-bike-icon">🚲</div>

                        <div>

                            <h3><?= h($project['project_name']) ?></h3>

                            <p>
                                <?= h($project['bicycle_type']) ?>

                                <?php if (!empty($project['bicycle_brand'])): ?>
                                    · <?= h($project['bicycle_brand']) ?>
                                <?php endif; ?>

                                <?php if (!empty($project['bicycle_model'])): ?>
                                    · <?= h($project['bicycle_model']) ?>
                                <?php endif; ?>
                            </p>

                            <p>
                                <?= (int) $project['selected_parts'] ?>
                                parts selected
                            </p>

                            <span class="project-status">
                                <?= h($project['status']) ?>
                            </span>

                        </div>

                    </div>

                    <div class="project-card-budget">

                        <span>Estimated Build Cost</span>

                        <strong>
                            <?= money($project['planned_cost']) ?>
                        </strong>

                        <small>
                            Budget: <?= money($project['budget_amount']) ?>
                        </small>

                        <a href="<?= h(url(
                            'builder/index.php?project_id=' .
                            (int) $project['project_id']
                        )) ?>">
                            Continue Building →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
