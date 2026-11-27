
<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/bicycle_visuals.php';
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

$totalRemainingBudget = $totalBudget - $totalPartsCost;

require __DIR__ . '/includes/header.php';
?>

<style>
.site-footer{display:none!important}
.garage-page{width:100%;height:calc(100vh - 75px);height:calc(100svh - 75px);min-height:0;margin:0;padding:8px clamp(14px,2.5vw,34px) 12px;box-sizing:border-box;display:flex;flex-direction:column;overflow:hidden}
.garage-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px;flex-shrink:0}
.garage-header h1{font-size:clamp(22px,2.2vw,29px);margin:0 0 4px}
.garage-header>div>p:last-child{font-size:12px;margin:0;color:#a9cbd6}
.garage-header .eyebrow{font-size:10px;letter-spacing:1.3px;margin:0 0 4px;color:#53d9e8}
.garage-header .dashboard-guide-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.garage-header .dashboard-guide-actions a,.garage-header .dashboard-guide-actions button{white-space:nowrap}
.garage-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0 0 10px;flex-shrink:0}
.garage-stat{min-width:0;background:rgba(22,68,93,.67);border:1px solid rgba(120,205,220,.24);border-top:3px solid #08b9d0;border-radius:11px;padding:13px;box-shadow:0 8px 20px rgba(0,15,30,.2);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);transition:border-color .2s,background .2s}
.garage-stat span{display:block;font-size:11px;color:#a9cbd6}
.garage-stat strong{display:block;font-size:clamp(17px,1.8vw,23px);color:#53d9e8;margin-top:6px;overflow-wrap:anywhere}
.garage-stat small{display:block;margin-top:4px;color:#91b8c5;font-size:10px}
.garage-stat.individual-stat{border-top-color:#45d4a3;background:rgba(20,78,76,.55)}
.garage-selected-plan{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 13px;margin-bottom:10px;border:1px solid rgba(120,205,220,.2);border-radius:10px;background:rgba(10,43,64,.48);flex-shrink:0}
.garage-selected-plan strong{display:block;color:#e0f5f8;font-size:12px}
.garage-selected-plan span{display:block;margin-top:3px;color:#91b8c5;font-size:10px}
.garage-selection-hint{color:#53d9e8!important;font-size:10px!important;text-align:right}
.garage-content-scroll{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;padding:2px 8px 12px 0;scrollbar-width:thin;scrollbar-color:rgba(83,217,232,.55) rgba(10,43,64,.35)}
.garage-content-scroll::-webkit-scrollbar{width:7px}
.garage-content-scroll::-webkit-scrollbar-track{background:rgba(10,43,64,.35);border-radius:10px}
.garage-content-scroll::-webkit-scrollbar-thumb{background:rgba(83,217,232,.55);border-radius:10px}
.garage-content-scroll .section-heading{margin-bottom:10px}
.garage-content-scroll .section-heading h2{font-size:18px;margin:0;color:#d9f2f5}
.garage-content-scroll .section-heading p{font-size:11px;margin:3px 0;color:#a9cbd6}
.garage-bike-card{background:rgba(22,68,93,.7);border:1px solid rgba(120,205,220,.25);border-radius:13px;padding:15px;margin-bottom:11px;box-shadow:0 8px 22px rgba(0,15,30,.2);backdrop-filter:blur(17px);-webkit-backdrop-filter:blur(17px);transition:background .2s,border-color .2s}
.garage-bike-card:hover{background:rgba(29,83,106,.76);border-color:rgba(8,197,216,.4)}
.garage-bike-top{display:grid;grid-template-columns:minmax(220px,1fr) minmax(150px,.7fr);align-items:center;gap:15px}
.garage-bike-info{display:flex;align-items:center;gap:15px;min-width:0}
.garage-bike-icon{width:66px;height:66px;min-width:66px;display:flex;align-items:center;justify-content:center;background:rgba(8,185,208,.17);border:1px solid rgba(8,197,216,.3);border-radius:15px;flex-shrink:0}
.garage-bike-icon svg{display:block;width:62px;height:42px;max-width:100%;overflow:visible}
.garage-bike-info h3{margin:0 0 5px;font-size:16px;color:#e0f5f8}
.garage-bike-info p,.garage-bike-info small{margin:3px 0;color:#a9cbd6;font-size:11px}
.garage-bike-type span{display:block;font-size:10px;color:#91b8c5;margin-bottom:5px}
.garage-bike-type strong{color:#53d9e8;font-size:13px}
.garage-button{display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(120,205,220,.27);border-radius:8px;padding:9px 13px;background:rgba(35,101,122,.4);color:#c8edf1;text-decoration:none;font-size:11px;font-weight:700;cursor:pointer;transition:.2s}
.garage-button:hover{background:rgba(8,185,208,.25);border-color:#08b9d0;color:#e9fbfd}
.garage-button.danger{color:#ffb9c1;border-color:rgba(220,83,100,.35);background:rgba(220,53,69,.15)}
.garage-button.danger:hover{background:rgba(185,54,74,.7);color:#ffe9ec}
.garage-plans{border-top:1px solid rgba(120,205,220,.2);margin-top:13px;padding-top:12px}
.garage-plans h4{font-size:12px;margin:0 0 9px;color:#8cecf3}
.garage-plan{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px;background:rgba(10,43,64,.56);border:1px solid rgba(120,205,220,.2);border-radius:9px;margin-top:8px;cursor:pointer;transition:background .2s,border-color .2s,box-shadow .2s;outline:none}
.garage-plan:hover{background:rgba(18,65,88,.8);border-color:#08b9d0}
.garage-plan:focus-visible{outline:2px solid #08b9d0;outline-offset:2px}
.garage-plan.selected{background:rgba(8,185,208,.13);border-color:#08b9d0;box-shadow:0 0 0 1px rgba(8,185,208,.16)}
.garage-plan h5{font-size:12px;margin:0 0 5px;color:#e0f5f8}
.garage-plan p{font-size:10px;color:#a9cbd6;margin:3px 0}
.garage-plan-actions{position:relative;z-index:2;display:flex;gap:7px;flex-wrap:wrap;flex-shrink:0}
.garage-plan-actions form{margin:0}
.garage-empty{padding:16px;text-align:center;color:#a9cbd6;background:rgba(10,43,64,.5);border:1px dashed rgba(120,205,220,.3);border-radius:9px;font-size:11px}
.garage-unassigned{margin-top:18px}
.garage-unassigned .section-heading h2{font-size:16px}
.garage-unassigned .garage-plan{margin-bottom:9px}
.garage-unassigned .garage-plan-actions{align-self:center}
.garage-page .empty-state{padding:22px 16px}
.garage-page .empty-state h3{font-size:16px}
.garage-page .empty-state p{font-size:11px}
.garage-page>.alert{flex-shrink:0;margin:0 0 10px}
@media(max-width:1000px){
.garage-page{height:calc(100vh - 70px);height:calc(100svh - 70px)}
.garage-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:800px){
.garage-bike-top{grid-template-columns:1fr}
.garage-bike-type{padding-left:81px}
.garage-plan{align-items:flex-start;flex-direction:column}
.garage-plan-actions{width:100%}
}
@media(max-width:700px){
.garage-page{height:calc(100vh - 65px);height:calc(100svh - 65px);padding:10px 12px 12px}
.garage-header{align-items:flex-start}
.garage-header h1{font-size:23px}
.garage-header>div>p:last-child{font-size:11px}
.garage-header .dashboard-guide-actions{gap:7px}
.garage-stats{gap:8px}
.garage-stat{padding:10px}
.garage-stat strong{font-size:18px}
.garage-stat small{font-size:9px}
.garage-selected-plan{align-items:flex-start;flex-direction:column}
.garage-selection-hint{text-align:left}
.garage-content-scroll .section-heading h2{font-size:16px}
.garage-bike-card{padding:11px}
.garage-bike-info{gap:12px}
.garage-bike-icon{width:55px;height:55px;min-width:55px}
.garage-bike-icon svg{width:52px;height:36px}
.garage-bike-type{padding-left:67px}
.garage-plan{padding:10px}
.garage-plan-actions{flex-wrap:wrap}
}
@media(max-width:480px){
.garage-header{flex-direction:column}
.garage-header .dashboard-guide-actions{width:100%}
.garage-header .dashboard-guide-actions a,.garage-header .dashboard-guide-actions button{flex:1;text-align:center;justify-content:center}
.garage-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
.garage-stat strong{font-size:16px}
.garage-bike-info h3{font-size:14px}
.garage-bike-type{padding-left:67px}
.garage-plan-actions .garage-button{flex:1}
}
</style>

<div class="garage-page">
    <div class="garage-header">
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

    <div class="garage-stats" id="garageStats" data-guide="garage-overview">
        <div class="garage-stat" data-stat-card>
            <span>Total Build Budget</span>
            <strong id="statPlanBudget"><?= money($totalBudget) ?></strong>
            <small id="statPlanBudgetNote">Combined budgets of all plans</small>
        </div>
        <div class="garage-stat" data-stat-card>
            <span>Total Selected Components</span>
            <strong id="statSelectedParts"><?= number_format($totalSelectedParts) ?></strong>
            <small id="statSelectedPartsNote">Components across all plans</small>
        </div>
        <div class="garage-stat" data-stat-card>
            <span>Total Planned Cost</span>
            <strong id="statPartsCost"><?= money($totalPartsCost) ?></strong>
            <small id="statPartsCostNote">Combined cost of selected parts</small>
        </div>
        <div class="garage-stat" data-stat-card>
            <span>Total Remaining Budget</span>
            <strong id="statRemainingBudget"><?= money($totalRemainingBudget) ?></strong>
            <small id="statRemainingBudgetNote">Remaining across all plans</small>
        </div>
    </div>

    <div class="garage-selected-plan" id="garageSelectedPlan">
        <div>
            <strong id="selectedPlanName">All Build Plans</strong>
            <span id="selectedPlanDetails">Combined statistics from all your saved build plans.</span>
        </div>
        <span class="garage-selection-hint" id="selectedPlanHint">Showing all plans</span>
    </div>

    <div class="garage-content-scroll">
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
                                <div class="garage-bike-icon">
                                    <?= bicycle_type_icon_svg($bike['bicycle_type'], 'garage') ?>
                                </div>
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
                                    <div class="garage-plan" data-plan-select tabindex="0" role="button" aria-pressed="false" aria-label="Select <?= h($plan['build_name']) ?> statistics"
                                        data-plan-id="<?= (int)$plan['project_id'] ?>"
                                        data-plan-name="<?= h($plan['build_name']) ?>"
                                        data-plan-type="<?= h(!empty($plan['bicycle_type']) ? $plan['bicycle_type'] : $bike['bicycle_type']) ?>"
                                        data-plan-brand="<?= h($plan['bicycle_brand']) ?>"
                                        data-plan-model="<?= h($plan['bicycle_model']) ?>"
                                        data-plan-status="<?= h($plan['status']) ?>"
                                        data-plan-budget="<?= h(number_format((float)$plan['budget_amount'], 2, '.', '')) ?>"
                                        data-plan-parts="<?= (int)$plan['selected_parts'] ?>"
                                        data-plan-cost="<?= h(number_format((float)$plan['parts_total'], 2, '.', '')) ?>">
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
                    <div class="garage-plan" data-plan-select tabindex="0" role="button" aria-pressed="false" aria-label="Select <?= h($plan['build_name']) ?> statistics"
                        data-plan-id="<?= (int)$plan['project_id'] ?>"
                        data-plan-name="<?= h($plan['build_name']) ?>"
                        data-plan-type="<?= h($plan['bicycle_type']) ?>"
                        data-plan-brand="<?= h($plan['bicycle_brand']) ?>"
                        data-plan-model="<?= h($plan['bicycle_model']) ?>"
                        data-plan-status="<?= h($plan['status']) ?>"
                        data-plan-budget="<?= h(number_format((float)$plan['budget_amount'], 2, '.', '')) ?>"
                        data-plan-parts="<?= (int)$plan['selected_parts'] ?>"
                        data-plan-cost="<?= h(number_format((float)$plan['parts_total'], 2, '.', '')) ?>">
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
    </div>
</div>

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
(function() {
    var plans = document.querySelectorAll('[data-plan-select]');
    var selectedPlan = null;
    var selectedName = document.getElementById('selectedPlanName');
    var selectedDetails = document.getElementById('selectedPlanDetails');
    var selectedHint = document.getElementById('selectedPlanHint');
    var budgetValue = document.getElementById('statPlanBudget');
    var partsValue = document.getElementById('statSelectedParts');
    var costValue = document.getElementById('statPartsCost');
    var remainingValue = document.getElementById('statRemainingBudget');
    var budgetNote = document.getElementById('statPlanBudgetNote');
    var partsNote = document.getElementById('statSelectedPartsNote');
    var costNote = document.getElementById('statPartsCostNote');
    var remainingNote = document.getElementById('statRemainingBudgetNote');
    var statCards = document.querySelectorAll('[data-stat-card]');

    var allPlans = {
        budget: <?= json_encode($totalBudget) ?>,
        parts: <?= json_encode($totalSelectedParts) ?>,
        cost: <?= json_encode($totalPartsCost) ?>
    };

    function peso(amount) {
        return (amount < 0 ? '-₱' : '₱') + Math.abs(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateStatCards(values, notes) {
        budgetValue.textContent = values.budget;
        partsValue.textContent = values.parts;
        costValue.textContent = values.cost;
        remainingValue.textContent = values.remaining;
        budgetNote.textContent = notes.budget;
        partsNote.textContent = notes.parts;
        costNote.textContent = notes.cost;
        remainingNote.textContent = notes.remaining;
    }

    function showAllPlans() {
        selectedPlan = null;

        plans.forEach(function(plan) {
            plan.classList.remove('selected');
            plan.setAttribute('aria-pressed', 'false');
        });

        statCards.forEach(function(card) {
            card.classList.remove('individual-stat');
        });

        selectedName.textContent = 'All Build Plans';
        selectedDetails.textContent = 'Combined statistics from all your saved build plans.';
        selectedHint.textContent = 'Showing all plans';

        updateStatCards({
            budget: peso(allPlans.budget),
            parts: allPlans.parts.toLocaleString('en-PH'),
            cost: peso(allPlans.cost),
            remaining: peso(allPlans.budget - allPlans.cost)
        }, {
            budget: 'Combined budgets of all plans',
            parts: 'Components across all plans',
            cost: 'Combined cost of selected parts',
            remaining: 'Remaining across all plans'
        });
    }

    function selectPlan(plan) {
        var name = plan.getAttribute('data-plan-name') || 'Build Plan';
        var type = plan.getAttribute('data-plan-type') || '';
        var brand = plan.getAttribute('data-plan-brand') || '';
        var model = plan.getAttribute('data-plan-model') || '';
        var status = plan.getAttribute('data-plan-status') || '';
        var budget = parseFloat(plan.getAttribute('data-plan-budget')) || 0;
        var parts = parseInt(plan.getAttribute('data-plan-parts'), 10) || 0;
        var cost = parseFloat(plan.getAttribute('data-plan-cost')) || 0;
        var remaining = budget - cost;

        selectedPlan = plan;

        plans.forEach(function(item) {
            var active = item === plan;
            item.classList.toggle('selected', active);
            item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        statCards.forEach(function(card) {
            card.classList.add('individual-stat');
        });

        selectedName.textContent = name;
        selectedDetails.textContent = [type, brand, model, status].filter(function(value) {
            return value !== '';
        }).join(' · ');
        selectedHint.textContent = 'Individual plan · Click the same plan again to show all plans';

        updateStatCards({
            budget: budget > 0 ? peso(budget) : 'Not set',
            parts: parts.toLocaleString('en-PH'),
            cost: peso(cost),
            remaining: budget > 0 ? peso(remaining) : 'Not set'
        }, {
            budget: 'Budget for this build plan',
            parts: 'Components in this plan',
            cost: 'Total planned cost of this plan',
            remaining: budget > 0 ? (remaining >= 0 ? 'Available budget' : 'Over the planned budget') : 'No target budget assigned'
        });
    }

    function togglePlan(plan) {
        if (selectedPlan === plan) {
            showAllPlans();
        } else {
            selectPlan(plan);
        }
    }

    plans.forEach(function(plan) {
        plan.addEventListener('click', function(event) {
            if (event.target.closest('.garage-plan-actions')) return;
            togglePlan(plan);
        });

        plan.addEventListener('keydown', function(event) {
            if (event.target !== plan) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                togglePlan(plan);
            }
        });
    });

    document.querySelectorAll('.delete-plan-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!confirm('Are you sure you want to delete this build plan? Its selected parts and unused bicycle profile will also be deleted.')) {
                event.preventDefault();
            }
        });
    });

    showAllPlans();
})();
</script>
<script src="<?= h(url('assets/js/user-guide.js')) ?>?v=2"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
