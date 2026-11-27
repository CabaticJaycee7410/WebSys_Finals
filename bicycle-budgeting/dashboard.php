
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();

$stmt = $pdo->prepare("SELECT COUNT(*) AS plans,COALESCE(SUM(budget_amount),0) AS budget FROM projects WHERE user_id = ?");
$stmt->execute(array($userId));
$projectSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) AS parts,COALESCE(SUM(pbp.price),0) AS planned FROM project_build_parts pbp INNER JOIN projects p ON p.project_id = pbp.project_id WHERE p.user_id = ?");
$stmt->execute(array($userId));
$partSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings_records WHERE user_id = ?");
$stmt->execute(array($userId));
$totalSavings = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bicycles WHERE user_id = ?");
$stmt->execute(array($userId));
$totalBicycles = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT b.bicycle_id,b.bicycle_name,b.bicycle_type,b.brand,b.model,p.project_id,p.project_name,p.budget_amount,p.status,bp.bicycle_type AS plan_bicycle_type,bp.bicycle_brand,bp.bicycle_model,COALESCE(parts.selected_parts,0) AS selected_parts,COALESCE(parts.planned_cost,0) AS planned_cost
     FROM bicycles b
     LEFT JOIN projects p ON p.project_id = (
        SELECT p2.project_id FROM projects p2 WHERE p2.bicycle_id = b.bicycle_id AND p2.user_id = b.user_id ORDER BY p2.created_at DESC,p2.project_id DESC LIMIT 1
     )
     LEFT JOIN (
        SELECT project_id,MAX(bicycle_type) AS bicycle_type,MAX(bicycle_brand) AS bicycle_brand,MAX(bicycle_model) AS bicycle_model FROM builder_plans GROUP BY project_id
     ) bp ON bp.project_id = p.project_id
     LEFT JOIN (
        SELECT project_id,COUNT(*) AS selected_parts,COALESCE(SUM(price),0) AS planned_cost FROM project_build_parts GROUP BY project_id
     ) parts ON parts.project_id = p.project_id
     WHERE b.user_id = ? ORDER BY b.created_at DESC"
);
$stmt->execute(array($userId));
$garageItems = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT p.project_id,p.project_name,p.budget_amount,p.status,bp.bicycle_type,bp.bicycle_brand,bp.bicycle_model,COALESCE(parts.selected_parts,0) AS selected_parts,COALESCE(parts.planned_cost,0) AS planned_cost
     FROM projects p
     LEFT JOIN (
        SELECT project_id,MAX(bicycle_type) AS bicycle_type,MAX(bicycle_brand) AS bicycle_brand,MAX(bicycle_model) AS bicycle_model FROM builder_plans GROUP BY project_id
     ) bp ON bp.project_id = p.project_id
     LEFT JOIN (
        SELECT project_id,COUNT(*) AS selected_parts,COALESCE(SUM(price),0) AS planned_cost FROM project_build_parts GROUP BY project_id
     ) parts ON parts.project_id = p.project_id
     WHERE p.user_id = ? AND (p.bicycle_id IS NULL OR p.bicycle_id = 0)
     ORDER BY p.created_at DESC LIMIT 8"
);
$stmt->execute(array($userId));
$unassignedPlans = $stmt->fetchAll();

$totalBudget = (float)$projectSummary['budget'];
$plannedCost = (float)$partSummary['planned'];
$remainingBudget = $totalBudget - $plannedCost;
$remainingSavings = max(0,$totalBudget - $totalSavings);

require __DIR__ . '/includes/header.php';
?>

<style>
.site-footer{display:none!important}
.dashboard-page{width:100%;height:calc(100vh - 75px);height:calc(100svh - 75px);min-height:0;margin:0;padding:8px clamp(14px,2.5vw,34px) 12px;box-sizing:border-box;display:flex;flex-direction:column;overflow:hidden}
.dashboard-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;flex-shrink:0}
.dashboard-header h1{font-size:clamp(22px,2.2vw,29px);margin:0 0 4px}
.dashboard-header p{font-size:12px;margin:0;color:#a9cbd6}
.dashboard-plan-mode{display:block;margin-top:4px;color:#53d9e8;font-size:10px}
.dashboard-new-build{white-space:nowrap}
.dashboard-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0 0 11px;flex-shrink:0}
.dashboard-stats .stat-card{min-width:0;padding:13px;border-radius:11px}
.dashboard-stats .stat-card span{font-size:11px}
.dashboard-stats .stat-card h2{font-size:clamp(18px,2vw,24px);margin:5px 0}
.dashboard-stats .stat-card p{font-size:10px;margin:0}
.dashboard-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:12px;flex-shrink:0}
.dashboard-overview .overview-card{min-width:0;padding:12px;border-radius:11px}
.dashboard-overview .overview-card h3{font-size:12px;margin:0 0 6px}
.dashboard-overview .overview-number{font-size:clamp(17px,1.8vw,22px);overflow-wrap:anywhere}
.dashboard-overview .overview-card p{font-size:10px;margin:5px 0}
.dashboard-overview .overview-card a{font-size:11px}
.dashboard-projects{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.dashboard-projects>.section-heading{margin-bottom:10px;flex-shrink:0}
.dashboard-projects>.section-heading h2{font-size:18px;margin:0}
.dashboard-projects>.section-heading p{font-size:11px;margin:3px 0}
.dashboard-projects>.section-heading>a{font-size:11px}
.dashboard-garage-list{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;padding:2px 7px 10px 0;scrollbar-width:thin;scrollbar-color:rgba(83,217,232,.55) rgba(10,43,64,.35)}
.dashboard-garage-list::-webkit-scrollbar{width:7px}
.dashboard-garage-list::-webkit-scrollbar-track{background:rgba(10,43,64,.35);border-radius:10px}
.dashboard-garage-list::-webkit-scrollbar-thumb{background:rgba(83,217,232,.55);border-radius:10px}
.dashboard-garage-card{background:rgba(22,68,93,.7);border:1px solid rgba(120,205,220,.25);border-radius:12px;padding:14px;margin-bottom:10px;box-shadow:0 7px 20px rgba(0,15,30,.2);backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px)}
.dashboard-garage-bike{display:flex;align-items:center;gap:15px}
.dashboard-garage-bike .project-bike-icon{width:60px;height:60px;min-width:60px;display:flex;align-items:center;justify-content:center;font-size:35px;flex-shrink:0;background:rgba(8,185,208,.17);border:1px solid rgba(8,197,216,.25);border-radius:14px}
.dashboard-garage-bike h3{margin:0 0 5px;color:#e0f5f8;font-size:15px}
.dashboard-garage-bike p{margin:3px 0;color:#a9cbd6;font-size:11px}
.dashboard-garage-plan{display:flex;justify-content:space-between;align-items:center;gap:12px;background:rgba(10,43,64,.56);border:1px solid rgba(120,205,220,.2);border-radius:9px;padding:12px;margin-top:11px;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)}
.dashboard-garage-plan h4{margin:0 0 5px;font-size:13px;color:#d9f2f5}
.dashboard-garage-plan p{margin:3px 0;color:#a9cbd6;font-size:11px}
.dashboard-garage-plan-info{min-width:0}
.dashboard-garage-plan-cost{text-align:right;flex-shrink:0}
.dashboard-garage-plan-cost span{display:block;color:#91b8c5;font-size:10px}
.dashboard-garage-plan-cost strong{display:block;color:#53d9e8;font-size:17px;margin:4px 0}
.dashboard-garage-plan-cost small{display:block;color:#a9cbd6;font-size:10px}
.dashboard-garage-plan-cost a{display:inline-block;margin-top:6px;color:#53d9e8;font-size:11px;font-weight:600;text-decoration:none;position:relative;z-index:2}
.dashboard-garage-plan-cost a:hover{color:#a5e5e9}
.dashboard-garage-empty{margin-top:10px;padding:10px;background:rgba(10,43,64,.5);border:1px dashed rgba(120,205,220,.3);border-radius:8px;color:#a9cbd6;font-size:11px}
.dashboard-unassigned{margin-top:15px}
.dashboard-unassigned .project-card{margin-bottom:9px;padding:13px;background:rgba(22,68,93,.7);border:1px solid rgba(120,205,220,.25);backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px)}
.dashboard-unassigned .project-card:hover{background:rgba(22,68,93,.7);border-color:rgba(120,205,220,.25)}
.dashboard-unassigned .project-card-main{display:flex;align-items:center;gap:15px}
.dashboard-unassigned .project-bike-icon{width:60px;height:60px;min-width:60px;display:flex;align-items:center;justify-content:center;font-size:32px;border-radius:14px}
.dashboard-unassigned .project-card-details h3{color:#e0f5f8;font-size:13px}
.dashboard-unassigned .project-card-details p{color:#a9cbd6;font-size:10px}
.dashboard-unassigned .project-card-budget strong{color:#53d9e8;font-size:15px}
.dashboard-unassigned .project-card-budget span,.dashboard-unassigned .project-card-budget small{color:#a9cbd6;font-size:10px}
.dashboard-unassigned .project-card-budget a{color:#53d9e8;font-size:11px;position:relative;z-index:2}
.dashboard-unassigned .section-heading h3{color:#d9f2f5;font-size:15px}
.dashboard-unassigned .section-heading p{color:#a9cbd6;font-size:10px}
.dashboard-projects>.section-heading h2{color:#d9f2f5}
.dashboard-projects>.section-heading p{color:#a9cbd6}
.dashboard-projects>.section-heading>a{color:#53d9e8}
.dashboard-page .empty-state{padding:22px 16px}
.dashboard-page .empty-state h3{font-size:16px}
.dashboard-page .empty-state p{font-size:11px}
.dashboard-garage-plan[data-plan-select],.dashboard-unassigned .project-card[data-plan-select]{cursor:pointer;pointer-events:auto;position:relative;z-index:1}
@media(max-width:1000px){
.dashboard-page{height:calc(100vh - 70px);height:calc(100svh - 70px)}
.dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
.dashboard-overview{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(max-width:700px){
.dashboard-page{height:auto;min-height:calc(100vh - 65px);min-height:calc(100svh - 65px);padding:10px 12px 15px;overflow:visible}
.dashboard-header{align-items:flex-start}
.dashboard-header h1{font-size:23px}
.dashboard-header p{font-size:11px}
.dashboard-header .dashboard-new-build{font-size:10px;padding:9px}
.dashboard-overview{grid-template-columns:1fr}
.dashboard-overview .overview-card{padding:11px}
.dashboard-overview .overview-card h3{font-size:12px}
.dashboard-overview .overview-number{font-size:19px}
.dashboard-projects{flex:initial;min-height:320px;overflow:visible}
.dashboard-garage-list{flex:initial;max-height:55vh;max-height:55svh;min-height:180px;overflow-y:auto}
.dashboard-garage-plan{align-items:flex-start;flex-direction:column}
.dashboard-garage-plan-cost{text-align:left}
.dashboard-projects>.section-heading{align-items:flex-start;gap:8px}
}
@media(max-width:430px){
.dashboard-header{flex-direction:column}
.dashboard-header .dashboard-new-build{width:100%;text-align:center;box-sizing:border-box}
.dashboard-stats{gap:8px}
.dashboard-stats .stat-card{padding:10px}
.dashboard-stats .stat-card h2{font-size:17px}
.dashboard-stats .stat-card p{font-size:9px}
.dashboard-projects>.section-heading h2{font-size:16px}
.dashboard-projects>.section-heading>a{font-size:10px}
.dashboard-garage-card{padding:10px}
.dashboard-garage-plan{padding:9px}
.dashboard-garage-bike .project-bike-icon,.dashboard-unassigned .project-bike-icon{width:50px;height:50px;min-width:50px;font-size:28px}
}
</style>

<div class="dashboard-page">
    <div class="dashboard-header">
        <div>
            <h1>Welcome back, <?= h(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Cyclist') ?>!</h1>
            <p>Here's an overview of your bicycles, build plans, and budget.</p>
            <small class="dashboard-plan-mode" id="dashboardPlanMode">Showing combined totals for all build plans</small>
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
            <span id="dashboardBudgetLabel">Total Build Budget</span>
            <h2 id="dashboardBudgetValue"><?= money($totalBudget) ?></h2>
            <p id="dashboardBudgetNote">Combined budgets for your build plans</p>
        </article>
        <article class="stat-card">
            <span id="dashboardCostLabel">Planned Parts Cost</span>
            <h2 id="dashboardCostValue"><?= money($plannedCost) ?></h2>
            <p id="dashboardCostNote">Estimated value of selected parts</p>
        </article>
        <article class="stat-card">
            <span>Total Savings</span>
            <h2><?= money($totalSavings) ?></h2>
            <p>Money recorded toward your plans</p>
        </article>
    </section>

    <section class="dashboard-overview">
        <article class="overview-card">
            <h3 id="dashboardRemainingLabel">Unallocated Build Budget</h3>
            <div class="overview-number" id="dashboardRemainingValue"><?= money($remainingBudget) ?></div>
            <p id="dashboardRemainingNote">Combined build budget minus planned parts cost.</p>
            <a href="<?= h(url('bicycles.php')) ?>">View My Garage →</a>
        </article>
        <article class="overview-card">
            <h3>Remaining Savings Goal</h3>
            <div class="overview-number"><?= money($remainingSavings) ?></div>
            <p>Amount still needed to reach your combined build budget.</p>
            <a href="<?= h(url('savings.php')) ?>">Track Savings →</a>
        </article>
        <article class="overview-card">
            <h3 id="dashboardPartsLabel">Selected Bicycle Parts</h3>
            <div class="overview-number" id="dashboardPartsValue"><?= number_format((int)$partSummary['parts']) ?></div>
            <p id="dashboardPartsNote">Components selected across your saved bicycle plans.</p>
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

        <div class="dashboard-garage-list" id="dashboardGarageList">
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
                            <div class="dashboard-garage-plan" data-plan-select data-plan-id="<?= (int)$item['project_id'] ?>" data-plan-name="<?= h($item['project_name']) ?>" data-plan-budget="<?= h(number_format((float)$item['budget_amount'],2,'.','')) ?>" data-plan-cost="<?= h(number_format((float)$item['planned_cost'],2,'.','')) ?>" data-plan-parts="<?= (int)$item['selected_parts'] ?>" role="button" tabindex="0" aria-pressed="false">
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
                            <article class="project-card" data-plan-select data-plan-id="<?= (int)$plan['project_id'] ?>" data-plan-name="<?= h($plan['project_name']) ?>" data-plan-budget="<?= h(number_format((float)$plan['budget_amount'],2,'.','')) ?>" data-plan-cost="<?= h(number_format((float)$plan['planned_cost'],2,'.','')) ?>" data-plan-parts="<?= (int)$plan['selected_parts'] ?>" role="button" tabindex="0" aria-pressed="false">
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
        </div>
    </section>
</div>

<script>
(function(){
    function initDashboardPlans(){
        var list=document.getElementById('dashboardGarageList');
        if(!list)return;

        var selectedPlan=null;
        var totalBudget=<?= json_encode($totalBudget) ?>;
        var totalCost=<?= json_encode($plannedCost) ?>;
        var totalParts=<?= json_encode((int)$partSummary['parts']) ?>;

        var ids=['dashboardPlanMode','dashboardBudgetLabel','dashboardBudgetValue','dashboardBudgetNote','dashboardCostLabel','dashboardCostValue','dashboardCostNote','dashboardRemainingLabel','dashboardRemainingValue','dashboardRemainingNote','dashboardPartsLabel','dashboardPartsValue','dashboardPartsNote'];
        var e={};
        ids.forEach(function(id){e[id]=document.getElementById(id);});

        function money(value){
            return '₱'+Number(value||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
        }

        function reset(){
            selectedPlan=null;
            list.querySelectorAll('[data-plan-select]').forEach(function(plan){
                plan.setAttribute('aria-pressed','false');
            });

            e.dashboardPlanMode.textContent='Showing combined totals for all build plans';
            e.dashboardBudgetLabel.textContent='Total Build Budget';
            e.dashboardBudgetValue.textContent=money(totalBudget);
            e.dashboardBudgetNote.textContent='Combined budgets for your build plans';
            e.dashboardCostLabel.textContent='Planned Parts Cost';
            e.dashboardCostValue.textContent=money(totalCost);
            e.dashboardCostNote.textContent='Estimated value of selected parts';
            e.dashboardRemainingLabel.textContent='Unallocated Build Budget';
            e.dashboardRemainingValue.textContent=money(totalBudget-totalCost);
            e.dashboardRemainingNote.textContent='Combined build budget minus planned parts cost.';
            e.dashboardPartsLabel.textContent='Selected Bicycle Parts';
            e.dashboardPartsValue.textContent=Number(totalParts).toLocaleString('en-PH');
            e.dashboardPartsNote.textContent='Components selected across your saved bicycle plans.';
        }

        function select(plan){
            selectedPlan=plan;
            list.querySelectorAll('[data-plan-select]').forEach(function(item){
                item.setAttribute('aria-pressed',item===plan?'true':'false');
            });

            var name=plan.getAttribute('data-plan-name')||'Selected Build Plan';
            var budget=Number(plan.getAttribute('data-plan-budget'))||0;
            var cost=Number(plan.getAttribute('data-plan-cost'))||0;
            var parts=Number(plan.getAttribute('data-plan-parts'))||0;

            e.dashboardPlanMode.textContent='Showing individual statistics for: '+name;
            e.dashboardBudgetLabel.textContent='Selected Plan Budget';
            e.dashboardBudgetValue.textContent=money(budget);
            e.dashboardBudgetNote.textContent=name;
            e.dashboardCostLabel.textContent='Selected Plan Parts Cost';
            e.dashboardCostValue.textContent=money(cost);
            e.dashboardCostNote.textContent='Estimated cost of this plan’s selected parts';
            e.dashboardRemainingLabel.textContent='Remaining Plan Budget';
            e.dashboardRemainingValue.textContent=money(budget-cost);
            e.dashboardRemainingNote.textContent='Selected plan budget minus its planned parts cost.';
            e.dashboardPartsLabel.textContent='Selected Plan Components';
            e.dashboardPartsValue.textContent=parts.toLocaleString('en-PH');
            e.dashboardPartsNote.textContent='Components selected for this build plan.';
        }

        function findPlan(target){
            while(target&&target!==list){
                if(target.hasAttribute&&target.hasAttribute('data-plan-select'))return target;
                target=target.parentNode;
            }
            return null;
        }

        function handlePlan(plan){
            if(!plan)return;
            if(selectedPlan===plan)reset();
            else select(plan);
        }

        list.addEventListener('click',function(event){
            if(event.target.closest&&event.target.closest('a'))return;
            handlePlan(findPlan(event.target));
        });

        list.addEventListener('keydown',function(event){
            if(event.key!=='Enter'&&event.key!==' ')return;
            if(event.target.closest&&event.target.closest('a'))return;
            var plan=findPlan(event.target);
            if(!plan)return;
            event.preventDefault();
            handlePlan(plan);
        });

        reset();
    }

    if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',initDashboardPlans);
    }else{
        initDashboardPlans();
    }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
