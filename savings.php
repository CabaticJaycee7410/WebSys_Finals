<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Savings Tracker';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $errors[] = 'Session expired. Refresh and try again.';
    $projectId = (int)($_POST['project_id'] ?? 0);
    $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
    $date = (string)($_POST['saving_date'] ?? date('Y-m-d'));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $stmt = $pdo->prepare('SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?');
    $stmt->execute([$projectId, $userId]);
    if (!$stmt->fetch()) $errors[] = 'Choose one of your bicycle plans.';
    if ($amount === false || $amount <= 0) $errors[] = 'Enter an amount greater than zero.';
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) $errors[] = 'Enter a valid date.';
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO savings_records (user_id, project_id, amount, saving_date, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $projectId, $amount, $date, $notes ?: null]);
        set_flash('success', 'Savings record added.');
        redirect('savings.php');
    }
}

$stmt = $pdo->prepare('SELECT project_id, project_name, budget_amount FROM projects WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT s.*, p.project_name FROM savings_records s LEFT JOIN projects p ON p.project_id = s.project_id WHERE s.user_id = ? ORDER BY s.saving_date DESC, s.saving_id DESC');
$stmt->execute([$userId]);
$records = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM savings_records WHERE user_id = ?');
$stmt->execute([$userId]);
$totalSavings = (float)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COALESCE(SUM(budget_amount),0) FROM projects WHERE user_id = ?');
$stmt->execute([$userId]);
$totalBudget = (float)$stmt->fetchColumn();
$remaining = max(0, $totalBudget - $totalSavings);
require __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div><p class="eyebrow">YOUR SAVINGS GOALS</p><h1>Savings Tracker</h1><p>Record money saved for your bicycle build plans.</p></div></div>
<section class="dashboard-stats"><article class="stat-card"><span>Total Savings</span><h2><?= money($totalSavings) ?></h2><p>All your recorded savings</p></article><article class="stat-card"><span>Combined Build Budgets</span><h2><?= money($totalBudget) ?></h2><p>Budget across your saved plans</p></article><article class="stat-card"><span>Remaining Savings Goal</span><h2><?= money($remaining) ?></h2><p>Combined budget less recorded savings</p></article></section>
<section class="content-card"><h2>Add Savings</h2><?php foreach ($errors as $error): ?><div class="alert error"><?= h($error) ?></div><?php endforeach; ?><?php if (!$projects): ?><p>Create a bicycle build plan before recording savings for it.</p><?php else: ?><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="form-group"><label>Bicycle Build</label><select name="project_id" required><option value="">Choose build</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['project_id'] ?>"><?= h($project['project_name']) ?> — <?= money($project['budget_amount']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Amount Saved (₱)</label><input type="number" name="amount" min="0.01" step="0.01" required></div><div class="form-group"><label>Date</label><input type="date" name="saving_date" value="<?= date('Y-m-d') ?>" required></div><div class="form-group"><label>Notes (Optional)</label><input name="notes" maxlength="255" placeholder="Example: Weekly savings"></div><button class="btn" type="submit">Record Savings</button></form><?php endif; ?></section>
<section class="dashboard-projects"><div class="section-heading"><div><h2>Savings History</h2><p>Your recorded savings entries.</p></div></div><?php if (!$records): ?><div class="empty-state">No savings records yet.</div><?php else: ?><div class="project-list"><?php foreach ($records as $record): ?><article class="savings-item"><div><strong><?= h($record['project_name'] ?: 'Unassigned build') ?></strong><p><?= h($record['notes'] ?: 'Savings contribution') ?></p><small><?= h($record['saving_date']) ?></small></div><strong><?= money($record['amount']) ?></strong></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
