<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Expenses';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$categories = ['Maintenance','Repairs','Daily Cycling','Cycling Trips','Parts and Accessories','Transportation','Other'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $errors[] = 'Session expired. Refresh and try again.';
    $category = trim((string)($_POST['category'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
    $date = (string)($_POST['expense_date'] ?? date('Y-m-d'));
    $projectId = (int)($_POST['project_id'] ?? 0);
    if (!in_array($category, $categories, true)) $errors[] = 'Choose a valid expense category.';
    if ($description === '') $errors[] = 'Enter an expense description.';
    if ($amount === false || $amount <= 0) $errors[] = 'Enter an amount greater than zero.';
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) $errors[] = 'Enter a valid date.';
    if ($projectId > 0) {
        $stmt = $pdo->prepare('SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?');
        $stmt->execute([$projectId, $userId]);
        if (!$stmt->fetch()) $errors[] = 'Selected build was not found.';
    } else $projectId = 0;
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO actual_expenses (user_id, project_id, category, description, amount, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $projectId ?: null, $category, $description, $amount, $date]);
        set_flash('success', 'Expense recorded.');
        redirect('expenses.php');
    }
}

$stmt = $pdo->prepare('SELECT project_id, project_name FROM projects WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT e.*, p.project_name FROM actual_expenses e LEFT JOIN projects p ON p.project_id = e.project_id WHERE e.user_id = ? ORDER BY e.expense_date DESC, e.expense_id DESC LIMIT 100');
$stmt->execute([$userId]);
$expenses = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM actual_expenses WHERE user_id = ?');
$stmt->execute([$userId]);
$total = (float)$stmt->fetchColumn();
require __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div><p class="eyebrow">ACTUAL SPENDING</p><h1>Expenses</h1><p>Record maintenance, repairs, daily cycling, and trip expenses.</p></div></div>
<section class="dashboard-stats"><article class="stat-card"><span>Total Recorded Expenses</span><h2><?= money($total) ?></h2><p>Actual spending recorded in your account</p></article><article class="stat-card"><span>Expense Records</span><h2><?= count($expenses) ?></h2><p>Recent records shown below</p></article></section>
<section class="content-card"><h2>Record an Expense</h2><?php foreach ($errors as $error): ?><div class="alert error"><?= h($error) ?></div><?php endforeach; ?><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="form-group"><label>Category</label><select name="category" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= h($category) ?>"><?= h($category) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Related Bicycle Build (Optional)</label><select name="project_id"><option value="0">General expense</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['project_id'] ?>"><?= h($project['project_name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Description</label><input name="description" required maxlength="255" placeholder="Example: Brake adjustment"></div><div class="form-group"><label>Amount (₱)</label><input name="amount" type="number" min="0.01" step="0.01" required></div><div class="form-group"><label>Expense Date</label><input name="expense_date" type="date" value="<?= date('Y-m-d') ?>" required></div><button class="btn" type="submit">Save Expense</button></form></section>
<section class="dashboard-projects"><div class="section-heading"><div><h2>Expense History</h2><p>Your latest recorded expenses.</p></div></div><?php if (!$expenses): ?><div class="empty-state">No expenses recorded yet.</div><?php else: ?><div class="project-list"><?php foreach ($expenses as $expense): ?><article class="expense-item"><div><strong><?= h($expense['description']) ?></strong><p><?= h($expense['category']) ?> · <?= h($expense['project_name'] ?: 'General expense') ?></p><small><?= h($expense['expense_date']) ?></small></div><strong><?= money($expense['amount']) ?></strong></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
