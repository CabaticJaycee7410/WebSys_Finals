
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'My Garage';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = array();

$types = array(
    'Mountain Bike',
    'BMX',
    'Road Bike',
    'Gravel Bike',
    'Hybrid Bike',
    'Commuter Bike',
    'City Bike',
    'Touring Bike',
    'Folding Bike',
    'Fat Bike',
    'Track / Fixed Gear',
    'Cyclocross Bike',
    'Downhill Bike',
    'Enduro Bike',
    'Cross-Country Bike',
    'Electric Bike',
    'Cargo Bike',
    'Tandem Bike',
    'Recumbent Bike',
    "Kids' Bike",
    'Custom Build'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Session expired. Refresh the page and try again.';
    } elseif ($action === 'update_bicycle') {
        $bicycleId = isset($_POST['bicycle_id']) ? (int)$_POST['bicycle_id'] : 0;
        $name = trim((string)(isset($_POST['bicycle_name']) ? $_POST['bicycle_name'] : ''));
        $type = trim((string)(isset($_POST['bicycle_type']) ? $_POST['bicycle_type'] : ''));
        $brand = trim((string)(isset($_POST['brand']) ? $_POST['brand'] : ''));
        $model = trim((string)(isset($_POST['model']) ? $_POST['model'] : ''));

        if ($bicycleId <= 0) {
            $errors[] = 'Invalid bicycle profile.';
        }
        if ($name === '') {
            $errors[] = 'Enter a bicycle name.';
        }
        if (!in_array($type, $types, true)) {
            $errors[] = 'Choose a valid bicycle type.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'UPDATE bicycles
                 SET bicycle_name = ?, bicycle_type = ?, brand = ?, model = ?
                 WHERE bicycle_id = ? AND user_id = ?'
            );
            $stmt->execute(array(
                $name,
                $type,
                $brand !== '' ? $brand : null,
                $model !== '' ? $model : null,
                $bicycleId,
                $userId
            ));

            set_flash('success', 'Bicycle profile updated successfully.');
            redirect('bicycles.php');
        }
    } elseif ($action === 'delete_bicycle') {
        $bicycleId = isset($_POST['bicycle_id']) ? (int)$_POST['bicycle_id'] : 0;

        if ($bicycleId <= 0) {
            $errors[] = 'Invalid bicycle profile.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'SELECT project_id
                     FROM projects
                     WHERE bicycle_id = ? AND user_id = ?'
                );
                $stmt->execute(array($bicycleId, $userId));
                $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($projectIds as $projectId) {
                    $projectId = (int)$projectId;

                    $stmt = $pdo->prepare(
                        'DELETE FROM project_build_parts
                         WHERE project_id = ?'
                    );
                    $stmt->execute(array($projectId));

                    $stmt = $pdo->prepare(
                        'DELETE FROM builder_plans
                         WHERE project_id = ?'
                    );
                    $stmt->execute(array($projectId));
                }

                $stmt = $pdo->prepare(
                    'DELETE FROM projects
                     WHERE bicycle_id = ? AND user_id = ?'
                );
                $stmt->execute(array($bicycleId, $userId));

                $stmt = $pdo->prepare(
                    'DELETE FROM bicycles
                     WHERE bicycle_id = ? AND user_id = ?'
                );
                $stmt->execute(array($bicycleId, $userId));

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Bicycle profile not found.');
                }

                $pdo->commit();
                set_flash('success', 'Bicycle and all its associated build plans and selected parts were deleted.');
                redirect('bicycles.php');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Unable to delete this bicycle and its build plans. ' . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT *
     FROM bicycles
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
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
     LEFT JOIN builder_plans bp ON bp.project_id = p.project_id
     LEFT JOIN (
        SELECT
            project_id,
            COUNT(*) AS selected_parts,
            COALESCE(SUM(price), 0) AS parts_total
        FROM project_build_parts
        GROUP BY project_id
     ) parts ON parts.project_id = p.project_id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC'
);
$stmt->execute(array($userId));
$plans = $stmt->fetchAll();

$plansByBicycle = array();
$unassignedPlans = array();
$totalBudget = 0;
$totalPartsCost = 0;

foreach ($plans as $plan) {
    $totalBudget += (float)$plan['budget_amount'];
    $totalPartsCost += (float)$plan['parts_total'];

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
.garage-bike-top{display:grid;grid-template-columns:minmax(220px,1fr) minmax(150px,.7fr) auto;align-items:center;gap:18px}
.garage-bike-info{display:flex;align-items:center;gap:14px;min-width:0}
.garage-bike-icon{width:50px;height:50px;display:grid;place-items:center;background:#e6f5ec;border-radius:12px;font-size:25px}
.garage-bike-info h3{margin:0 0 5px;font-size:16px}
.garage-bike-info p,.garage-bike-info small{margin:3px 0;color:#64766c;font-size:12px}
.garage-bike-type span{display:block;font-size:11px;color:#64766c;margin-bottom:5px}
.garage-bike-type strong{color:#087747}
.garage-actions{display:flex;gap:7px;flex-wrap:wrap}
.garage-button{display:inline-flex;align-items:center;justify-content:center;border:1px solid #d2e2d8;border-radius:7px;padding:8px 12px;background:#fff;color:#176b48;text-decoration:none;font-size:12px;font-weight:600;cursor:pointer}
.garage-button:hover{background:#edf7f1}
.garage-button.danger{color:#b42318;border-color:#f0d2cf}
.garage-plans{border-top:1px solid #edf1ed;margin-top:17px;padding-top:15px}
.garage-plans h4{font-size:13px;margin:0 0 12px}
.garage-plan{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:13px;background:#f7faf8;border:1px solid #e7eee9;border-radius:9px;margin-top:9px}
.garage-plan h5{font-size:13px;margin:0 0 6px}
.garage-plan p{font-size:11px;color:#64766c;margin:3px 0}
.garage-edit-form{border-top:1px solid #edf1ed;margin-top:16px;padding-top:16px}
.garage-edit-form[hidden]{display:none!important}
.garage-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.garage-edit-grid label{display:block;font-size:12px;font-weight:600}
.garage-edit-grid input,.garage-edit-grid select{width:100%;margin-top:6px;padding:10px;border:1px solid #d7e2da;border-radius:7px;background:#fff}
.garage-empty{padding:20px;text-align:center;color:#64766c;background:#f8fbf9;border:1px dashed #cbded1;border-radius:10px;font-size:12px}
.garage-unassigned{margin-top:25px}
@media(max-width:800px){.garage-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.garage-bike-top{grid-template-columns:1fr}.garage-plan{align-items:flex-start;flex-direction:column}}
@media(max-width:480px){.garage-stats{grid-template-columns:1fr}.garage-edit-grid{grid-template-columns:1fr}}
</style>

<div class="dashboard-header">
    <div>
        <p class="eyebrow">YOUR CYCLING WORKSPACE</p>
        <h1>My Garage</h1>
        <p>View your bicycles and manage their saved build plans in one place.</p>
    </div>
    <a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build New Bike</a>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endforeach; ?>

<div class="garage-stats">
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
        <small><?= array_sum(array_map(function ($plan) { return (int)$plan['selected_parts']; }, $plans)) ?> selected parts</small>
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
            $latestPlan = !empty($bikePlans) ? $bikePlans[0] : null;
            ?>
            <article class="garage-bike-card">
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

                    <div class="garage-actions">
                        <?php if ($latestPlan): ?>
                            <a class="garage-button" href="<?= h(url('builder/index.php?project_id=' . (int)$latestPlan['project_id'])) ?>">Edit</a>
                        <?php else: ?>
                            <button type="button" class="garage-button" data-toggle-edit="<?= $bikeId ?>">Edit</button>
                        <?php endif; ?>

                        <form method="post" class="delete-bicycle-form">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_bicycle">
                            <input type="hidden" name="bicycle_id" value="<?= $bikeId ?>">
                            <button type="submit" class="garage-button danger">Delete</button>
                        </form>
                    </div>
                </div>

                <form method="post" class="garage-edit-form" id="edit-bike-<?= $bikeId ?>" hidden>
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_bicycle">
                    <input type="hidden" name="bicycle_id" value="<?= $bikeId ?>">

                    <div class="garage-edit-grid">
                        <label>
                            Bicycle Name
                            <input type="text" name="bicycle_name" maxlength="150" required value="<?= h($bike['bicycle_name']) ?>">
                        </label>
                        <label>
                            Bicycle Type
                            <select name="bicycle_type" required>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= h($type) ?>" <?= $bike['bicycle_type'] === $type ? 'selected' : '' ?>><?= h($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            Brand
                            <input type="text" name="brand" maxlength="100" value="<?= h($bike['brand']) ?>">
                        </label>
                        <label>
                            Model
                            <input type="text" name="model" maxlength="100" value="<?= h($bike['model']) ?>">
                        </label>
                    </div>

                    <div class="garage-actions" style="margin-top:12px">
                        <button type="submit" class="garage-button">Save Changes</button>
                        <button type="button" class="garage-button" data-toggle-edit="<?= $bikeId ?>">Cancel</button>
                    </div>
                </form>

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
                                        <?= h($plan['bicycle_type']) ?>
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
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
document.querySelectorAll('[data-toggle-edit]').forEach(function(button) {
    button.addEventListener('click', function() {
        var form = document.getElementById('edit-bike-' + button.dataset.toggleEdit);
        if (form) form.hidden = !form.hidden;
    });
});

document.querySelectorAll('.delete-bicycle-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        if (!confirm('Delete this bicycle, all its saved build plans, and all selected parts? This action cannot be undone.')) {
            event.preventDefault();
        }
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
