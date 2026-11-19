<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'My Builds';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();

$stmt = $pdo->prepare("SELECT p.project_id, p.project_name, p.budget_amount, p.status, p.created_at, bp.bicycle_type, bp.bicycle_brand, bp.bicycle_model, COUNT(pbp.build_part_id) AS selected_parts, COALESCE(SUM(pbp.price),0) AS planned_cost FROM projects p LEFT JOIN builder_plans bp ON bp.project_id = p.project_id LEFT JOIN project_build_parts pbp ON pbp.project_id = p.project_id WHERE p.user_id = ? GROUP BY p.project_id ORDER BY p.created_at DESC");
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();
$totalBudget = 0;
$totalCost = 0;
$totalParts = 0;
foreach ($projects as $project) {
    $totalBudget += (float)$project['budget_amount'];
    $totalCost += (float)$project['planned_cost'];
    $totalParts += (int)$project['selected_parts'];
}
require __DIR__ . '/includes/header.php';
?>
<div class="dashboard-header"><div><p class="eyebrow">YOUR SAVED BUILDS</p><h1>My Bicycle Builds</h1><p>Create, edit, and manage your bicycle plans.</p></div><a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build New Bike</a></div>
<section class="dashboard-stats">
    <article class="stat-card"><span>Total Plans</span><h2><?= count($projects) ?></h2><p>Saved bicycle plans</p></article>
    <article class="stat-card"><span>Total Build Budget</span><h2><?= money($totalBudget) ?></h2><p>Combined target budgets</p></article>
    <article class="stat-card"><span>Planned Parts Cost</span><h2><?= money($totalCost) ?></h2><p>Estimated component total</p></article>
    <article class="stat-card"><span>Selected Parts</span><h2><?= $totalParts ?></h2><p>Across all saved builds</p></article>
</section>
<div class="section-heading"><div><h2>Saved Bicycle Plans</h2><p>Open a plan to continue building.</p></div></div>
<?php if (!$projects): ?>
<div class="empty-state"><h3>No bicycle plans yet</h3><p>Create your first build by choosing a bicycle type and selecting components.</p><a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">Build New Bike</a></div>
<?php else: ?>
<div class="project-list">
<?php foreach ($projects as $project): ?>
<article class="project-card" id="project-<?= (int)$project['project_id'] ?>">
    <div class="project-card-main"><div class="project-bike-icon">🚲</div><div class="project-card-details"><h3><?= h($project['project_name']) ?></h3><p><?= h($project['bicycle_type'] ?: 'Custom Build') ?><?= $project['bicycle_brand'] ? ' · ' . h($project['bicycle_brand']) : '' ?><?= $project['bicycle_model'] ? ' · ' . h($project['bicycle_model']) : '' ?></p><p><?= (int)$project['selected_parts'] ?> part(s) selected · <?= h($project['status']) ?></p><small>Created <?= h(date('M d, Y', strtotime($project['created_at']))) ?></small></div></div>
    <div class="project-card-budget"><span>Estimated Build Cost</span><strong><?= money($project['planned_cost']) ?></strong><small>Target budget: <?= money($project['budget_amount']) ?></small><small>Remaining: <?= money((float)$project['budget_amount'] - (float)$project['planned_cost']) ?></small><div class="project-card-actions"><a class="project-edit-link" href="<?= h(url('builder/index.php?project_id=' . (int)$project['project_id'])) ?>">Edit Build</a><button type="button" class="project-delete-button" data-project-id="<?= (int)$project['project_id'] ?>">Delete</button></div></div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<script>
window.PROJECTS_CONFIG = {csrfToken: <?= json_encode(csrf_token()) ?>, deleteUrl: <?= json_encode(url('builder/delete_plan.php')) ?>};
document.addEventListener('DOMContentLoaded', () => {
 document.querySelectorAll('.project-delete-button').forEach(button => button.addEventListener('click', async () => {
  if (!confirm('Delete this bicycle plan? Its saved build parts will also be deleted.')) return;
  button.disabled = true; button.textContent = 'Deleting...';
  try {
   const response = await fetch(window.PROJECTS_CONFIG.deleteUrl, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({projectId:Number(button.dataset.projectId),csrfToken:window.PROJECTS_CONFIG.csrfToken})});
   const data = await response.json();
   if (!response.ok || !data.success) throw new Error(data.message || 'Delete failed');
   document.getElementById('project-' + button.dataset.projectId)?.remove();
   location.reload();
  } catch (error) { alert(error.message); button.disabled = false; button.textContent = 'Delete'; }
 }));
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
