
<?php

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'My Builds';
$pdo = require __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| GET ALL USER BUILD PLANS
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
        AND b.user_id = p.user_id

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

/*
|--------------------------------------------------------------------------
| PAGE SUMMARY
|--------------------------------------------------------------------------
*/

$totalPlans = count($projects);
$totalBudget = 0;
$totalPlannedCost = 0;
$totalSelectedParts = 0;

foreach ($projects as $project) {
    $totalBudget += (float) $project['budget_amount'];
    $totalPlannedCost += (float) $project['planned_cost'];
    $totalSelectedParts += (int) $project['selected_parts'];
}

$remainingBudget = $totalBudget - $totalPlannedCost;

require_once __DIR__ . '/includes/header.php';

?>

<div class="dashboard-header">

    <div>
        <h1>My Bicycle Builds</h1>

        <p>
            Create, edit, and manage your bicycle building plans.
        </p>
    </div>

    <a
        href="<?= h(url('builder/index.php')) ?>"
        class="dashboard-new-build"
    >
        + Build New Bike
    </a>

</div>

<!-- BUILD SUMMARY -->

<section class="dashboard-stats">

    <div class="stat-card">
        <span>Total Bicycle Plans</span>

        <h2><?= number_format($totalPlans) ?></h2>

        <p>Your saved bicycle projects</p>
    </div>

    <div class="stat-card">
        <span>Total Build Budget</span>

        <h2><?= money($totalBudget) ?></h2>

        <p>Combined budget of all plans</p>
    </div>

    <div class="stat-card">
        <span>Planned Parts Cost</span>

        <h2><?= money($totalPlannedCost) ?></h2>

        <p>Combined estimated component costs</p>
    </div>

    <div class="stat-card">
        <span>Selected Parts</span>

        <h2><?= number_format($totalSelectedParts) ?></h2>

        <p>Components across all bicycle plans</p>
    </div>

</section>

<!-- BICYCLE PLANS -->

<section class="dashboard-projects">

    <div class="section-heading">

        <div>
            <h2>Saved Bicycle Plans</h2>

            <p>
                Select a plan to continue building or update its details.
            </p>
        </div>

    </div>

    <div id="projectMessage" class="builder-message" role="status"></div>

    <?php if (empty($projects)): ?>

        <div class="empty-state">

            <h3>You Haven't Created a Bicycle Plan Yet</h3>

            <p>
                Start by choosing your bicycle type and selecting
                the parts you want to include.
            </p>

            <a
                href="<?= h(url('builder/index.php')) ?>"
                class="dashboard-new-build"
            >
                Build Your First Bike
            </a>

        </div>

    <?php else: ?>

        <div class="project-list">

            <?php foreach ($projects as $project): ?>

                <article
                    class="project-card"
                    id="project-<?= (int) $project['project_id'] ?>"
                >

                    <div class="project-card-main">

                        <div class="project-bike-icon">
                            🚲
                        </div>

                        <div class="project-card-details">

                            <h3>
                                <?= h($project['project_name']) ?>
                            </h3>

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

                            <small>
                                Created:
                                <?= h(date(
                                    'M d, Y',
                                    strtotime($project['created_at'])
                                )) ?>
                            </small>

                        </div>

                    </div>

                    <div class="project-card-budget">

                        <span>Estimated Build Cost</span>

                        <strong>
                            <?= money($project['planned_cost']) ?>
                        </strong>

                        <small>
                            Target Budget:
                            <?= money($project['budget_amount']) ?>
                        </small>

                        <small>
                            Remaining:
                            <?= money(
                                (float) $project['budget_amount']
                                - (float) $project['planned_cost']
                            ) ?>
                        </small>

                        <div class="project-card-actions">

                            <a
                                href="<?= h(url(
                                    'builder/index.php?project_id=' .
                                    (int) $project['project_id']
                                )) ?>"
                                class="project-edit-link"
                            >
                                Edit Build
                            </a>

                            <button
                                type="button"
                                class="project-delete-button"
                                data-project-id="<?= (int) $project['project_id'] ?>"
                            >
                                Delete
                            </button>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<script>
window.PROJECTS_CONFIG = {
    csrfToken: <?= json_encode($_SESSION['csrf_token']) ?>,
    deleteUrl: <?= json_encode(url('builder/delete_plan.php')) ?>
};
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const config = window.PROJECTS_CONFIG || {};

    const message = document.getElementById('projectMessage');

    document.querySelectorAll('.project-delete-button').forEach(button => {

        button.addEventListener('click', async () => {

            const projectId = button.dataset.projectId;

            const confirmed = window.confirm(
                'Are you sure you want to delete this bicycle plan? ' +
                'Its selected parts, savings, and linked expenses will also be deleted.'
            );

            if (!confirmed) return;

            button.disabled = true;
            button.textContent = 'Deleting...';

            message.textContent = 'Deleting bicycle plan...';

            try {

                const response = await fetch(config.deleteUrl, {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },

                    body: JSON.stringify({
                        projectId: Number(projectId),
                        csrfToken: config.csrfToken
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    message.textContent =
                        data.message || 'Unable to delete this plan.';

                    button.disabled = false;
                    button.textContent = 'Delete';

                    return;
                }

                const card = document.getElementById(
                    'project-' + projectId
                );

                if (card) {
                    card.remove();
                }

                message.textContent = 'Bicycle plan deleted.';

                window.location.reload();

            } catch (error) {

                console.error(error);

                message.textContent =
                    'Unable to connect to the server. Try again.';

                button.disabled = false;
                button.textContent = 'Delete';

            }

        });

    });

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
