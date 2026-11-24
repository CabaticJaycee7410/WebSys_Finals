
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'My Garage';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Session expired. Refresh the page and try again.';
    } elseif ($action === 'delete_plan') {
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

        if ($projectId <= 0) {
            $errors[] = 'Invalid build plan.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT project_id, bicycle_id FROM projects WHERE project_id = ? AND user_id = ? LIMIT 1');
                $stmt->execute(array($projectId, $userId));
                $project = $stmt->fetch();

                if (!$project) {
                    throw new Exception('Build plan not found.');
                }

                $bicycleId = (int)$project['bicycle_id'];
                $stmt = $pdo->prepare('DELETE FROM project_build_parts WHERE project_id = ?');
                $stmt->execute(array($projectId));
                $stmt = $pdo->prepare('DELETE FROM builder_plans WHERE project_id = ?');
                $stmt->execute(array($projectId));
                $stmt = $pdo->prepare('DELETE FROM projects WHERE project_id = ? AND user_id = ?');
                $stmt->execute(array($projectId, $userId));

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Unable to delete the build plan.');
                }

                if ($bicycleId > 0) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE bicycle_id = ? AND user_id = ?');
                    $stmt->execute(array($bicycleId, $userId));

                    if ((int)$stmt->fetchColumn() === 0) {
                        $stmt = $pdo->prepare('DELETE FROM bicycles WHERE bicycle_id = ? AND user_id = ?');
                        $stmt->execute(array($bicycleId, $userId));
                    }
                }

                $pdo->commit();
                set_flash('success', 'Build plan, selected parts, and unused bicycle profile were deleted successfully.');
                redirect('bicycles.php');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Unable to delete this build plan. ' . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM bicycles WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute(array($userId));
$bicycles = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT
        p.project_id,
        p.bicycle_id,
        p.project_name,
        p.budget_amount,
        p.status,
        p.created_at,
        COALESCE(NULLIF(bp.build_name, \'\'), p.project_name) AS build_name,
        bp.bicycle_type,
        bp.bicycle_brand,
        bp.bicycle_model,
        COALESCE(parts.selected_parts, 0) AS selected_parts,
        COALESCE(parts.parts_total, 0) AS parts_total
     FROM projects p
     LEFT JOIN (
        SELECT
            project_id,
            MAX(NULLIF(build_name, \'\')) AS build_name,
            MAX(bicycle_type) AS bicycle_type,
            MAX(bicycle_brand) AS bicycle_brand,
            MAX(bicycle_model) AS bicycle_model
        FROM builder_plans
        GROUP BY project_id
     ) bp ON bp.project_id = p.project_id
     LEFT JOIN (
        SELECT
            project_id,
            COUNT(*) AS selected_parts,
            COALESCE(SUM(price), 0) AS parts_total
        FROM project_build_parts
        GROUP BY project_id
     ) parts ON parts.project_id = p.project_id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC, p.project_id DESC'
);
$stmt->execute(array($userId));
$plans = $stmt->fetchAll();

$plansByBicycle = array();
$unassignedPlans = array();
$totalBudget = 0;
$totalPartsCost = 0;
$totalSelectedParts = 0;

foreach ($plans as $plan) {
    $totalBudget += (float)$plan['budget_amount'];
    $totalPartsCost += (float)$plan['parts_total'];
    $totalSelectedParts += (int)$plan['selected_parts'];

    if (!empty($plan['bicycle_id'])) {
        $bikeId = (int)$plan['bicycle_id'];
        if (!isset($plansByBicycle[$bikeId])) {
            $plansByBicycle[$bikeId] = array();
        }
        $plansByBicycle[$bikeId][] = $plan;
    } else {
        $unassignedPlans[] = $plan;
    }
}

require __DIR__ . '/includes/header.php';
?>

<style>
.garage-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:22px 0}
.garage-stat{background:#fff;border:1px solid #dce9e1;border-top:3px solid #15945d;border-radius:11px;padding:18px;box-shadow:0 5px 18px rgba(20,60,40,.05)}
.garage-stat span{display:block;font-size:12px;color:#64766c}
.garage-stat strong{display:block;font-size:24px;color:#087747;margin-top:9px}
.garage-bike-card{background:#fff;border:1px solid #dce9e1;border-radius:12px;padding:18px;margin-bottom:16px;box-shadow:0 5px 18px rgba(20,60,40,.04)}
.garage-bike-top{display:grid;grid-template-columns:minmax(220px,1fr) minmax(150px,.7fr);align-items:center;gap:18px}
.garage-bike-info{display:flex;align-items:center;gap:14px;min-width:0}
.garage-bike-icon{width:50px;height:50px;display:grid;place-items:center;background:#e6f5ec;border-radius:12px;font-size:25px;flex-shrink:0}
.garage-bike-info h3{margin:0 0 5px;font-size:16px}
.garage-bike-info p,.garage-bike-info small{margin:3px 0;color:#64766c;font-size:12px}
.garage-bike-type span{display:block;font-size:11px;color:#64766c;margin-bottom:5px}
.garage-bike-type strong{color:#087747}
.garage-button{display:inline-flex;align-items:center;justify-content:center;border:1px solid #d2e2d8;border-radius:7px;padding:8px 12px;background:#fff;color:#176b48;text-decoration:none;font-size:12px;font-weight:600;cursor:pointer}
.garage-button:hover{background:#edf7f1}
.garage-button.danger{color:#b42318;border-color:#f0d2cf}
.garage-plans{border-top:1px solid #edf1ed;margin-top:17px;padding-top:15px}
.garage-plans h4{font-size:13px;margin:0 0 12px}
.garage-plan{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:13px;background:#f7faf8;border:1px solid #e7eee9;border-radius:9px;margin-top:9px}
.garage-plan h5{font-size:13px;margin:0 0 6px}
.garage-plan p{font-size:11px;color:#64766c;margin:3px 0}
.garage-plan-actions{display:flex;gap:7px;flex-wrap:wrap;flex-shrink:0}
.garage-empty{padding:20px;text-align:center;color:#64766c;background:#f8fbf9;border:1px dashed #cbded1;border-radius:10px;font-size:12px}
.garage-unassigned{margin-top:25px}
.dashboard-guide-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
@media(max-width:800px){.garage-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.garage-bike-top{grid-template-columns:1fr}.garage-plan{align-items:flex-start;flex-direction:column}}
@media(max-width:480px){.garage-stats{grid-template-columns:1fr}.dashboard-guide-actions{width:100%}.dashboard-guide-actions a,.dashboard-guide-actions button{width:100%}}
</style>

<div class="dashboard-header">
    <div>
        <p class="eyebrow">YOUR CYCLING WORKSPACE</p>
        <h1>My Garage</h1>
        <p>View your bicycles and manage their saved build plans in one place.</p>
    </div>
    <div class="dashboard-guide-actions">
        <button type="button" class="user-guide-trigger" id="startUserGuide">How to Use</button>
        <a class="dashboard-new-build" data-guide="build-bike" href="<?= h(url('builder/index.php')) ?>">＋ Build New Bike</a>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endforeach; ?>

<div class="garage-stats" data-guide="garage-overview">
    <div class="garage-stat">
        <span>Total Bicycles</span>
        <strong><?= count($bicycles) ?></strong>
        <small>Registered bicycles</small>
    </div>
    <div class="garage-stat">
        <span>Saved Build Plans</span>
        <strong><?= count($plans) ?></strong>
        <small>Plans created in Bicycle Builder</small>
    </div>
    <div class="garage-stat">
        <span>Total Build Budget</span>
        <strong>₱<?= number_format($totalBudget, 2) ?></strong>
        <small>Combined target budgets</small>
    </div>
    <div class="garage-stat">
        <span>Planned Parts Cost</span>
        <strong>₱<?= number_format($totalPartsCost, 2) ?></strong>
        <small><?= $totalSelectedParts ?> selected parts</small>
    </div>
</div>

<section class="dashboard-projects">
    <div class="section-heading">
        <div>
            <h2>My Bicycles</h2>
            <p>Your registered bicycles and their associated build plans.</p>
        </div>
    </div>

    <?php if (!$bicycles): ?>
        <div class="empty-state">
            <h3>No bicycles registered yet</h3>
            <p>Start in the Bicycle Builder to create your first bicycle profile.</p>
            <a class="btn" href="<?= h(url('builder/index.php')) ?>">Build New Bike</a>
        </div>
    <?php else: ?>
        <?php foreach ($bicycles as $bike): ?>
            <?php
            $bikeId = (int)$bike['bicycle_id'];
            $bikePlans = isset($plansByBicycle[$bikeId]) ? $plansByBicycle[$bikeId] : array();
            ?>
            <article class="garage-bike-card" data-guide="bicycle-profiles">
                <div class="garage-bike-top">
                    <div class="garage-bike-info">
                        <div class="garage-bike-icon">🚲</div>
                        <div>
                            <h3><?= h($bike['bicycle_name']) ?></h3>
                            <p>
                                <?= h($bike['bicycle_type']) ?>
                                <?= !empty($bike['brand']) ? ' · ' . h($bike['brand']) : '' ?>
                                <?= !empty($bike['model']) ? ' · ' . h($bike['model']) : '' ?>
                            </p>
                            <small><?= count($bikePlans) ?> saved build plan(s)</small>
                        </div>
                    </div>
                    <div class="garage-bike-type">
                        <span>Bicycle Profile</span>
                        <strong><?= h($bike['bicycle_type']) ?></strong>
                    </div>
                </div>

                <div class="garage-plans">
                    <h4>Saved Build Plans</h4>
                    <?php if (!$bikePlans): ?>
                        <div class="garage-empty">No saved build plans are associated with this bicycle yet.</div>
                    <?php else: ?>
                        <?php foreach ($bikePlans as $plan): ?>
                            <div class="garage-plan">
                                <div>
                                    <h5><?= h($plan['build_name']) ?></h5>
                                    <p>
                                        <?= h($plan['bicycle_type'] ? $plan['bicycle_type'] : $bike['bicycle_type']) ?>
                                        <?= !empty($plan['bicycle_brand']) ? ' · ' . h($plan['bicycle_brand']) : '' ?>
                                        <?= !empty($plan['bicycle_model']) ? ' · ' . h($plan['bicycle_model']) : '' ?>
                                    </p>
                                    <p>
                                        <?= (int)$plan['selected_parts'] ?> selected part(s)
                                        · Parts cost: ₱<?= number_format((float)$plan['parts_total'], 2) ?>
                                        · Budget: ₱<?= number_format((float)$plan['budget_amount'], 2) ?>
                                    </p>
                                    <p>Status: <?= h($plan['status']) ?></p>
                                </div>
                                <div class="garage-plan-actions">
                                    <a class="garage-button" data-guide="edit-plan" href="<?= h(url('builder/index.php?project_id=' . (int)$plan['project_id'])) ?>">Edit Plan</a>
                                    <form method="post" class="delete-plan-form">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete_plan">
                                        <input type="hidden" name="project_id" value="<?= (int)$plan['project_id'] ?>">
                                        <button type="submit" class="garage-button danger" data-guide="delete-plan">Delete Plan</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php if ($unassignedPlans): ?>
    <section class="garage-unassigned dashboard-projects">
        <div class="section-heading">
            <div>
                <h2>Other Saved Build Plans</h2>
                <p>Build plans that are not currently linked to a bicycle profile.</p>
            </div>
        </div>
        <?php foreach ($unassignedPlans as $plan): ?>
            <div class="garage-plan">
                <div>
                    <h5><?= h($plan['build_name']) ?></h5>
                    <p><?= (int)$plan['selected_parts'] ?> selected part(s) · Parts cost: ₱<?= number_format((float)$plan['parts_total'], 2) ?></p>
                    <p>Budget: ₱<?= number_format((float)$plan['budget_amount'], 2) ?> · Status: <?= h($plan['status']) ?></p>
                </div>
                <div class="garage-plan-actions">
                    <a class="garage-button" data-guide="edit-plan" href="<?= h(url('builder/index.php?project_id=' . (int)$plan['project_id'])) ?>">Edit Plan</a>
                    <form method="post" class="delete-plan-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete_plan">
                        <input type="hidden" name="project_id" value="<?= (int)$plan['project_id'] ?>">
                        <button type="submit" class="garage-button danger" data-guide="delete-plan">Delete Plan</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<div class="user-guide-backdrop" id="userGuideBackdrop" hidden></div>
<div class="user-guide-panel" id="userGuidePanel" role="dialog" aria-modal="true" aria-labelledby="userGuideTitle" aria-describedby="userGuideText" hidden>
    <div class="user-guide-panel-top">
        <span class="user-guide-label" id="userGuideCount"></span>
        <button type="button" class="user-guide-close" id="userGuideClose" aria-label="Close guide">×</button>
    </div>
    <h3 id="userGuideTitle"></h3>
    <p id="userGuideText"></p>
    <div class="user-guide-progress">
        <div class="user-guide-progress-fill" id="userGuideProgress"></div>
    </div>
    <div class="user-guide-panel-actions">
        <span class="user-guide-counter" id="userGuideCounter"></span>
        <div class="user-guide-buttons">
            <button type="button" class="user-guide-button" id="userGuidePrevious">Previous</button>
            <button type="button" class="user-guide-button primary" id="userGuideNext">Next</button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.delete-plan-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        if (!confirm('Are you sure you want to delete this build plan? Its selected parts and unused bicycle profile will also be deleted.')) {
            event.preventDefault();
        }
    });
});
</script>
<script src="<?= h(url('assets/js/user-guide.js')) ?>?v=2"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
