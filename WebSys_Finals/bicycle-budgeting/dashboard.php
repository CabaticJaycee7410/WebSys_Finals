
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();

$stmt = $pdo->prepare("SELECT COUNT(*) AS plans,COALESCE(SUM(budget_amount),0) AS budget FROM projects WHERE user_id=?");
$stmt->execute(array($userId));
$projectSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) AS parts,COALESCE(SUM(pbp.price),0) AS planned FROM project_build_parts pbp INNER JOIN projects p ON p.project_id=pbp.project_id WHERE p.user_id=?");
$stmt->execute(array($userId));
$partSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings_records WHERE user_id=?");
$stmt->execute(array($userId));
$totalSavings = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bicycles WHERE user_id=?");
$stmt->execute(array($userId));
$totalBicycles = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM bicycles WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute(array($userId));
$bicycles = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        p.project_id,p.bicycle_id,p.project_name,p.budget_amount,p.status,p.created_at,
        COALESCE(NULLIF(bp.build_name,''),p.project_name) AS build_name,
        bp.bicycle_type,bp.bicycle_brand,bp.bicycle_model,
        COALESCE(parts.selected_parts,0) AS selected_parts,
        COALESCE(parts.parts_total,0) AS parts_total,
        COALESCE(savings.savings_total,0) AS savings_total
     FROM projects p
     LEFT JOIN (
        SELECT project_id,MAX(NULLIF(build_name,'')) AS build_name,MAX(bicycle_type) AS bicycle_type,MAX(bicycle_brand) AS bicycle_brand,MAX(bicycle_model) AS bicycle_model
        FROM builder_plans GROUP BY project_id
     ) bp ON bp.project_id=p.project_id
     LEFT JOIN (
        SELECT project_id,COUNT(*) AS selected_parts,COALESCE(SUM(price),0) AS parts_total
        FROM project_build_parts GROUP BY project_id
     ) parts ON parts.project_id=p.project_id
     LEFT JOIN (
        SELECT project_id,COALESCE(SUM(amount),0) AS savings_total
        FROM savings_records WHERE user_id=? GROUP BY project_id
     ) savings ON savings.project_id=p.project_id
     WHERE p.user_id=?
     ORDER BY p.created_at DESC,p.project_id DESC"
);
$stmt->execute(array($userId,$userId));
$plans = $stmt->fetchAll();

$plansByBicycle = array();
$unassignedPlans = array();

foreach($plans as $plan){
    if(!empty($plan['bicycle_id'])){
        $bikeId=(int)$plan['bicycle_id'];
        if(!isset($plansByBicycle[$bikeId]))$plansByBicycle[$bikeId]=array();
        $plansByBicycle[$bikeId][]=$plan;
    }else{
        $unassignedPlans[]=$plan;
    }
}

$totalBudget=(float)$projectSummary['budget'];
$plannedCost=(float)$partSummary['planned'];
$totalSelectedParts=(int)$partSummary['parts'];
$remainingBudget=$totalBudget-$plannedCost;
$remainingSavings=max(0,$totalBudget-$totalSavings);

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
.app-main .dashboard-page .dashboard-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:0 0 8px;flex-shrink:0}
.dashboard-stats .stat-card{min-width:0;padding:11px 10px;border-radius:11px;box-sizing:border-box}
.dashboard-stats .stat-card span{display:block;font-size:11px;color:#a9cbd6}
.dashboard-stats .stat-card h2{font-size:clamp(17px,1.8vw,23px);color:#53d9e8;margin:4px 0;overflow-wrap:anywhere}
.dashboard-stats .stat-card p{font-size:9px;margin:0;color:#91b8c5}
.dashboard-dynamic-stat{background:rgba(22,68,93,.67);border:1px solid rgba(120,205,220,.24);border-top:3px solid #08b9d0;border-radius:11px;box-shadow:0 8px 20px rgba(0,15,30,.2);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);transition:border-color .2s,background .2s}
.dashboard-dynamic-stat.individual-stat{border-top-color:#45d4a3;background:rgba(20,78,76,.55)}
.dashboard-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-bottom:9px;flex-shrink:0}
.dashboard-overview .overview-card{min-width:0;padding:10px;border-radius:11px;box-sizing:border-box}
.dashboard-overview .overview-card h3{font-size:11px;margin:0 0 4px;line-height:1.35}
.dashboard-overview .overview-number{font-size:17px;margin:6px 0;overflow-wrap:anywhere;line-height:1.3}
.dashboard-overview .overview-card p{font-size:9px;margin:4px 0;line-height:1.4}
.dashboard-overview .overview-card a{font-size:10px}
.dashboard-projects{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.dashboard-projects>.section-heading{margin-bottom:10px;flex-shrink:0}
.dashboard-projects>.section-heading h2{font-size:18px;margin:0;color:#d9f2f5}
.dashboard-projects>.section-heading p{font-size:11px;margin:3px 0;color:#a9cbd6}
.dashboard-projects>.section-heading>a{font-size:11px;color:#53d9e8}
.dashboard-garage-list{flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;padding:2px 7px 10px 0;scrollbar-width:thin;scrollbar-color:rgba(83,217,232,.55) rgba(10,43,64,.35)}
.dashboard-garage-list::-webkit-scrollbar{width:7px}
.dashboard-garage-list::-webkit-scrollbar-track{background:rgba(10,43,64,.35);border-radius:10px}
.dashboard-garage-list::-webkit-scrollbar-thumb{background:rgba(83,217,232,.55);border-radius:10px}
.dashboard-garage-card{display:grid;grid-template-columns:minmax(0,1fr) minmax(210px,270px);align-items:center;gap:18px;background:rgba(22,68,93,.7);border:1px solid rgba(120,205,220,.25);border-radius:12px;padding:13px;margin-bottom:9px;box-shadow:0 7px 20px rgba(0,15,30,.2);backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px);transition:background .2s,border-color .2s}
.dashboard-garage-card:hover{background:rgba(29,83,106,.76);border-color:rgba(8,197,216,.4)}
.dashboard-garage-bike{display:flex;align-items:center;gap:13px;min-width:0}
.dashboard-garage-bike .project-bike-icon{width:55px;height:55px;min-width:55px;display:flex;align-items:center;justify-content:center;font-size:31px;flex-shrink:0;background:rgba(8,185,208,.17);border:1px solid rgba(8,197,216,.25);border-radius:12px}
.dashboard-garage-bike h3{margin:0 0 4px;color:#e0f5f8;font-size:14px}
.dashboard-garage-bike p{margin:0;color:#a9cbd6;font-size:11px;overflow-wrap:anywhere}
.dashboard-garage-plans{display:grid;gap:7px;min-width:0}
.dashboard-garage-plan{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:9px 11px;background:rgba(10,43,64,.56);border:1px solid rgba(120,205,220,.2);border-radius:9px;cursor:pointer;transition:background .2s,border-color .2s,box-shadow .2s;outline:none;min-width:0}
.dashboard-garage-plan:hover{background:rgba(18,65,88,.8);border-color:#08b9d0}
.dashboard-garage-plan:focus-visible{outline:2px solid #08b9d0;outline-offset:2px}
.dashboard-garage-plan.selected{background:rgba(8,185,208,.13);border-color:#08b9d0;box-shadow:0 0 0 1px rgba(8,185,208,.16)}
.dashboard-garage-plan-cost{min-width:0;text-align:right;flex:1}
.dashboard-garage-plan-cost span{display:block;color:#91b8c5;font-size:9px}
.dashboard-garage-plan-cost strong{display:block;color:#53d9e8;font-size:16px;margin:2px 0;overflow-wrap:anywhere}
.dashboard-garage-plan-actions{position:relative;z-index:2;display:flex;justify-content:flex-end;align-items:center;flex-shrink:0}
.dashboard-garage-plan-actions a{display:inline-block;color:#53d9e8;font-size:10px;font-weight:700;text-decoration:none;white-space:nowrap}
.dashboard-garage-plan-actions a:hover{color:#a5e5e9}
.dashboard-garage-empty{padding:10px;background:rgba(10,43,64,.5);border:1px dashed rgba(120,205,220,.3);border-radius:8px;color:#a9cbd6;font-size:10px}
.dashboard-unassigned{margin-top:12px}
.dashboard-unassigned>.section-heading{margin-bottom:8px}
.dashboard-unassigned>.section-heading h3{color:#d9f2f5;font-size:14px}
.dashboard-unassigned>.section-heading p{color:#a9cbd6;font-size:10px}
.dashboard-page .empty-state{padding:22px 16px}
.dashboard-page .empty-state h3{font-size:16px}
.dashboard-page .empty-state p{font-size:11px}
@media(max-width:1000px){
.dashboard-page{height:calc(100vh - 70px);height:calc(100svh - 70px)}
.app-main .dashboard-page .dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:800px){
.dashboard-garage-card{grid-template-columns:minmax(0,1fr) minmax(190px,230px);gap:10px;padding:10px}
.dashboard-garage-bike{gap:9px}
.dashboard-garage-bike .project-bike-icon{width:48px;height:48px;min-width:48px;font-size:27px}
.dashboard-garage-plan{padding:8px}
.dashboard-garage-plan-cost strong{font-size:14px}
}
@media(max-width:700px){
.dashboard-page{height:auto;min-height:calc(100vh - 65px);min-height:calc(100svh - 65px);padding:10px 12px 15px;overflow:visible}
.dashboard-header{align-items:flex-start}
.dashboard-header h1{font-size:23px}
.dashboard-header p{font-size:11px}
.dashboard-header .dashboard-new-build{font-size:10px;padding:9px}
.app-main .dashboard-page .dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
.dashboard-stats .stat-card{padding:9px}
.dashboard-stats .stat-card span{font-size:10px;margin-bottom:4px}
.dashboard-stats .stat-card h2{font-size:16px;margin:3px 0}
.dashboard-stats .stat-card p{font-size:8px}
.dashboard-overview{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;margin-bottom:9px}
.dashboard-overview .overview-card{padding:9px}
.dashboard-overview .overview-card h3{font-size:10px}
.dashboard-overview .overview-number{font-size:15px;margin:5px 0}
.dashboard-overview .overview-card p{font-size:8px}
.dashboard-overview .overview-card a{font-size:9px}
.dashboard-overview .overview-card:last-child{grid-column:1/-1}
.dashboard-projects{flex:initial;min-height:320px;overflow:visible}
.dashboard-garage-list{flex:initial;max-height:55vh;max-height:55svh;min-height:180px;overflow-y:auto}
.dashboard-garage-card{grid-template-columns:1fr;gap:9px;padding:10px}
.dashboard-garage-plans{padding-top:8px;border-top:1px solid rgba(120,205,220,.17)}
.dashboard-garage-plan{flex-direction:row;align-items:center;padding:8px 10px}
.dashboard-garage-plan-cost{text-align:right}
.dashboard-projects>.section-heading{align-items:flex-start;gap:8px}
}
@media(max-width:480px){
.dashboard-header{flex-direction:column}
.dashboard-header .dashboard-new-build{width:100%;text-align:center;box-sizing:border-box}
.app-main .dashboard-page .dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
.dashboard-stats .stat-card{padding:9px}
.dashboard-stats .stat-card h2{font-size:15px}
.dashboard-overview .overview-card{padding:9px}
.dashboard-overview .overview-number{font-size:15px}
.dashboard-projects>.section-heading h2{font-size:16px}
.dashboard-projects>.section-heading>a{font-size:10px}
.dashboard-garage-card{padding:9px}
.dashboard-garage-bike .project-bike-icon{width:45px;height:45px;min-width:45px;font-size:25px}
.dashboard-garage-plan{gap:7px}
.dashboard-garage-plan-cost strong{font-size:14px}
}
@media(max-width:350px){
.app-main .dashboard-page .dashboard-stats{grid-template-columns:1fr}
.dashboard-overview{grid-template-columns:1fr}
.dashboard-overview .overview-card:last-child{grid-column:auto}
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
        <article class="stat-card dashboard-dynamic-stat" data-stat-card>
            <span id="dashboardBudgetLabel">Total Build Budget</span>
            <h2 id="dashboardBudgetValue"><?= money($totalBudget) ?></h2>
            <p id="dashboardBudgetNote">Combined budgets of all plans</p>
        </article>
        <article class="stat-card dashboard-dynamic-stat" data-stat-card>
            <span>Total Bicycles</span>
            <h2 id="dashboardBicyclesValue"><?= number_format($totalBicycles) ?></h2>
            <p id="dashboardBicyclesNote">Bicycles registered in your account</p>
        </article>
        <article class="stat-card dashboard-dynamic-stat" data-stat-card>
            <span id="dashboardCostLabel">Planned Parts Cost</span>
            <h2 id="dashboardCostValue"><?= money($plannedCost) ?></h2>
            <p id="dashboardCostNote">Combined cost of selected parts</p>
        </article>
        <article class="stat-card dashboard-dynamic-stat" data-stat-card>
            <span id="dashboardSavingsLabel">Total Savings</span>
            <h2 id="dashboardSavingsValue"><?= money($totalSavings) ?></h2>
            <p id="dashboardSavingsNote">Savings recorded across all plans</p>
        </article>
    </section>

    <section class="dashboard-overview">
        <article class="overview-card dashboard-dynamic-stat" data-stat-card>
            <h3 id="dashboardRemainingLabel">Total Remaining Build Budget</h3>
            <div class="overview-number" id="dashboardRemainingValue"><?= money($remainingBudget) ?></div>
            <p id="dashboardRemainingNote">Remaining budget across all plans</p>
            <a href="<?= h(url('bicycles.php')) ?>">View My Garage →</a>
        </article>
        <article class="overview-card dashboard-dynamic-stat" data-stat-card>
            <h3 id="dashboardSavingsGoalLabel">Remaining Savings Goal</h3>
            <div class="overview-number" id="dashboardSavingsGoalValue"><?= money($remainingSavings) ?></div>
            <p id="dashboardSavingsGoalNote">Combined budget less recorded savings</p>
            <a href="<?= h(url('savings.php')) ?>">Track Savings →</a>
        </article>
        <article class="overview-card dashboard-dynamic-stat" data-stat-card>
            <h3 id="dashboardPartsLabel">Total Selected Components</h3>
            <div class="overview-number" id="dashboardPartsValue"><?= number_format($totalSelectedParts) ?></div>
            <p id="dashboardPartsNote">Components across all plans</p>
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
            <?php if(!$bicycles&&!$unassignedPlans): ?>
                <div class="empty-state">
                    <h3>Your garage is waiting for its first bicycle</h3>
                    <p>Create your first bicycle and build plan using the Bicycle Builder.</p>
                    <a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build Your First Bike</a>
                </div>
            <?php else: ?>
                <?php foreach($bicycles as $bike): ?>
                    <?php
                    $bikeId=(int)$bike['bicycle_id'];
                    $bikePlans=isset($plansByBicycle[$bikeId])?$plansByBicycle[$bikeId]:array();
                    ?>
                    <article class="dashboard-garage-card">
                        <div class="dashboard-garage-bike">
                            <div class="project-bike-icon">🚲</div>
                            <div>
                                <h3><?= h($bike['bicycle_name']) ?></h3>
                                <p>
                                    <?= h($bike['bicycle_type']) ?>
                                    <?= !empty($bike['brand'])?' · '.h($bike['brand']):'' ?>
                                    <?= !empty($bike['model'])?' · '.h($bike['model']):'' ?>
                                </p>
                            </div>
                        </div>

                        <?php if($bikePlans): ?>
                            <div class="dashboard-garage-plans">
                                <?php foreach($bikePlans as $plan): ?>
                                    <div class="dashboard-garage-plan" data-plan-select tabindex="0" role="button" aria-pressed="false" aria-label="Select <?= h($plan['build_name']) ?> statistics"
                                        data-plan-bicycle-id="<?= (int)$plan['bicycle_id'] ?>"
                                        data-plan-name="<?= h($plan['build_name']) ?>"
                                        data-plan-budget="<?= h(number_format((float)$plan['budget_amount'],2,'.','')) ?>"
                                        data-plan-parts="<?= (int)$plan['selected_parts'] ?>"
                                        data-plan-cost="<?= h(number_format((float)$plan['parts_total'],2,'.','')) ?>"
                                        data-plan-savings="<?= h(number_format((float)$plan['savings_total'],2,'.','')) ?>">
                                        <div class="dashboard-garage-plan-cost">
                                            <span>Estimated Build Cost</span>
                                            <strong><?= money($plan['parts_total']) ?></strong>
                                        </div>
                                        <div class="dashboard-garage-plan-actions">
                                            <a href="<?= h(url('builder/index.php?project_id='.(int)$plan['project_id'])) ?>">Edit Plan →</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <?php if(!$bikePlans): ?>
                        <div class="dashboard-garage-empty">No saved build plans are associated with this bicycle yet.</div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if($unassignedPlans): ?>
                    <div class="dashboard-unassigned">
                        <div class="section-heading">
                            <div>
                                <h3>Other Saved Build Plans</h3>
                                <p>Build plans that are not currently linked to a bicycle profile.</p>
                            </div>
                        </div>
                        <?php foreach($unassignedPlans as $plan): ?>
                            <div class="dashboard-garage-plan" data-plan-select tabindex="0" role="button" aria-pressed="false" aria-label="Select <?= h($plan['build_name']) ?> statistics"
                                data-plan-bicycle-id="0"
                                data-plan-name="<?= h($plan['build_name']) ?>"
                                data-plan-budget="<?= h(number_format((float)$plan['budget_amount'],2,'.','')) ?>"
                                data-plan-parts="<?= (int)$plan['selected_parts'] ?>"
                                data-plan-cost="<?= h(number_format((float)$plan['parts_total'],2,'.','')) ?>"
                                data-plan-savings="<?= h(number_format((float)$plan['savings_total'],2,'.','')) ?>">
                                <div class="dashboard-garage-plan-cost">
                                    <span><?= h($plan['build_name']) ?> · Estimated Build Cost</span>
                                    <strong><?= money($plan['parts_total']) ?></strong>
                                </div>
                                <div class="dashboard-garage-plan-actions">
                                    <a href="<?= h(url('builder/index.php?project_id='.(int)$plan['project_id'])) ?>">Edit Plan →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
(function(){
    var plans=document.querySelectorAll('[data-plan-select]');
    var selectedPlan=null;

    var budgetValue=document.getElementById('dashboardBudgetValue');
    var bicyclesValue=document.getElementById('dashboardBicyclesValue');
    var partsValue=document.getElementById('dashboardPartsValue');
    var costValue=document.getElementById('dashboardCostValue');
    var savingsValue=document.getElementById('dashboardSavingsValue');
    var remainingValue=document.getElementById('dashboardRemainingValue');
    var savingsGoalValue=document.getElementById('dashboardSavingsGoalValue');

    var budgetNote=document.getElementById('dashboardBudgetNote');
    var bicyclesNote=document.getElementById('dashboardBicyclesNote');
    var partsNote=document.getElementById('dashboardPartsNote');
    var costNote=document.getElementById('dashboardCostNote');
    var savingsNote=document.getElementById('dashboardSavingsNote');
    var remainingNote=document.getElementById('dashboardRemainingNote');
    var savingsGoalNote=document.getElementById('dashboardSavingsGoalNote');

    var budgetLabel=document.getElementById('dashboardBudgetLabel');
    var costLabel=document.getElementById('dashboardCostLabel');
    var remainingLabel=document.getElementById('dashboardRemainingLabel');
    var savingsGoalLabel=document.getElementById('dashboardSavingsGoalLabel');
    var partsLabel=document.getElementById('dashboardPartsLabel');
    var planMode=document.getElementById('dashboardPlanMode');
    var statCards=document.querySelectorAll('[data-stat-card]');

    var allPlans={
        budget:<?= json_encode($totalBudget) ?>,
        bicycles:<?= json_encode($totalBicycles) ?>,
        parts:<?= json_encode($totalSelectedParts) ?>,
        cost:<?= json_encode($plannedCost) ?>,
        savings:<?= json_encode($totalSavings) ?>
    };

    function money(amount){
        return (amount<0?'-₱':'₱')+Math.abs(amount).toLocaleString('en-PH',{
            minimumFractionDigits:2,
            maximumFractionDigits:2
        });
    }

    function updateStats(values,notes){
        budgetValue.textContent=values.budget;
        bicyclesValue.textContent=values.bicycles;
        partsValue.textContent=values.parts;
        costValue.textContent=values.cost;
        savingsValue.textContent=values.savings;
        remainingValue.textContent=values.remaining;
        savingsGoalValue.textContent=values.savingsGoal;

        budgetNote.textContent=notes.budget;
        bicyclesNote.textContent=notes.bicycles;
        partsNote.textContent=notes.parts;
        costNote.textContent=notes.cost;
        savingsNote.textContent=notes.savings;
        remainingNote.textContent=notes.remaining;
        savingsGoalNote.textContent=notes.savingsGoal;
    }

    function showAllPlans(){
        selectedPlan=null;

        plans.forEach(function(plan){
            plan.classList.remove('selected');
            plan.setAttribute('aria-pressed','false');
        });

        statCards.forEach(function(card){
            card.classList.remove('individual-stat');
        });

        planMode.textContent='Showing combined totals for all build plans';
        budgetLabel.textContent='Total Build Budget';
        costLabel.textContent='Planned Parts Cost';
        remainingLabel.textContent='Total Remaining Build Budget';
        savingsGoalLabel.textContent='Remaining Savings Goal';
        partsLabel.textContent='Total Selected Components';

        updateStats({
            budget:money(allPlans.budget),
            bicycles:allPlans.bicycles.toLocaleString('en-PH'),
            parts:allPlans.parts.toLocaleString('en-PH'),
            cost:money(allPlans.cost),
            savings:money(allPlans.savings),
            remaining:money(allPlans.budget-allPlans.cost),
            savingsGoal:money(Math.max(0,allPlans.budget-allPlans.savings))
        },{
            budget:'Combined budgets of all plans',
            bicycles:'Bicycles registered in your account',
            parts:'Components across all plans',
            cost:'Combined cost of selected parts',
            savings:'Savings recorded across all plans',
            remaining:'Remaining budget across all plans',
            savingsGoal:'Combined budget less recorded savings'
        });
    }

    function selectPlan(plan){
        var name=plan.getAttribute('data-plan-name')||'Build Plan';
        var bicycleId=parseInt(plan.getAttribute('data-plan-bicycle-id'),10)||0;
        var budget=parseFloat(plan.getAttribute('data-plan-budget'))||0;
        var parts=parseInt(plan.getAttribute('data-plan-parts'),10)||0;
        var cost=parseFloat(plan.getAttribute('data-plan-cost'))||0;
        var savings=parseFloat(plan.getAttribute('data-plan-savings'))||0;
        var remaining=budget-cost;
        var savingsGoal=Math.max(0,budget-savings);

        selectedPlan=plan;

        plans.forEach(function(item){
            var active=item===plan;
            item.classList.toggle('selected',active);
            item.setAttribute('aria-pressed',active?'true':'false');
        });

        statCards.forEach(function(card){
            card.classList.add('individual-stat');
        });

        planMode.textContent='Showing individual statistics for: '+name;
        budgetLabel.textContent='Individual Build Budget';
        costLabel.textContent='Individual Parts Cost';
        remainingLabel.textContent='Individual Remaining Build Budget';
        savingsGoalLabel.textContent='Individual Remaining Savings Goal';
        partsLabel.textContent='Selected Plan Components';

        updateStats({
            budget:budget>0?money(budget):'Not set',
            bicycles:bicycleId>0?'1':'0',
            parts:parts.toLocaleString('en-PH'),
            cost:money(cost),
            savings:money(savings),
            remaining:budget>0?money(remaining):'Not set',
            savingsGoal:budget>0?money(savingsGoal):'Not set'
        },{
            budget:'Budget for this build plan',
            bicycles:bicycleId>0?'Bicycle linked to this plan':'This plan is not linked to a bicycle',
            parts:'Components in this plan',
            cost:'Total planned cost of this plan',
            savings:'Savings recorded for this plan',
            remaining:budget>0?(remaining>=0?'Available build budget':'Over the planned build budget'):'No target budget assigned',
            savingsGoal:budget>0?'This plan budget less its own savings':'No savings goal assigned'
        });
    }

    function togglePlan(plan){
        if(selectedPlan===plan)showAllPlans();
        else selectPlan(plan);
    }

    plans.forEach(function(plan){
        plan.addEventListener('click',function(event){
            if(event.target.closest('.dashboard-garage-plan-actions'))return;
            togglePlan(plan);
        });

        plan.addEventListener('keydown',function(event){
            if(event.target!==plan)return;
            if(event.key==='Enter'||event.key===' '){
                event.preventDefault();
                togglePlan(plan);
            }
        });
    });

    showAllPlans();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
