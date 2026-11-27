
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Savings Tracker';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Session expired. Refresh and try again.';
    }

    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $amountInput = isset($_POST['amount']) ? $_POST['amount'] : null;
    $amount = filter_var($amountInput, FILTER_VALIDATE_FLOAT);
    $date = isset($_POST['saving_date']) ? (string)$_POST['saving_date'] : date('Y-m-d');
    $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';

    $stmt = $pdo->prepare('SELECT project_id FROM projects WHERE project_id = ? AND user_id = ?');
    $stmt->execute(array($projectId, $userId));
    if (!$stmt->fetch()) {
        $errors[] = 'Choose one of your bicycle plans.';
    }
    if ($amount === false || $amount <= 0) {
        $errors[] = 'Enter an amount greater than zero.';
    }
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
        $errors[] = 'Enter a valid date.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO savings_records (user_id, project_id, amount, saving_date, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array($userId, $projectId, $amount, $date, $notes !== '' ? $notes : null));
        set_flash('success', 'Savings record added.');
        redirect('savings.php');
    }
}

$stmt = $pdo->prepare('SELECT project_id, project_name, budget_amount FROM projects WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute(array($userId));
$projects = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT s.*, p.project_name FROM savings_records s LEFT JOIN projects p ON p.project_id = s.project_id WHERE s.user_id = ? ORDER BY s.saving_date DESC, s.saving_id DESC');
$stmt->execute(array($userId));
$records = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM savings_records WHERE user_id = ?');
$stmt->execute(array($userId));
$totalSavings = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(budget_amount),0) FROM projects WHERE user_id = ?');
$stmt->execute(array($userId));
$totalBudget = (float)$stmt->fetchColumn();

$remaining = max(0, $totalBudget - $totalSavings);

require __DIR__ . '/includes/header.php';
?>
<style>
.history-vertical-slider{width:100%;margin-top:14px}
.history-vertical-viewport{height:150px;width:100%;overflow-x:hidden;overflow-y:auto;border-radius:15px;scroll-snap-type:y mandatory;scroll-behavior:smooth;overscroll-behavior-y:contain;scrollbar-width:thin;scrollbar-color:#53d9e8 rgba(120,205,220,.12)}
.history-vertical-viewport::-webkit-scrollbar{width:7px}
.history-vertical-viewport::-webkit-scrollbar-track{background:rgba(120,205,220,.1);border-radius:10px}
.history-vertical-viewport::-webkit-scrollbar-thumb{background:#53d9e8;border-radius:10px}
.history-vertical-track{display:flex;flex-direction:column;gap:10px;width:100%}
.history-vertical-slide{display:flex;flex:0 0 150px;width:100%;height:150px;min-width:0;box-sizing:border-box;justify-content:space-between;align-items:center;gap:20px;padding:24px;border:1px solid rgba(120,205,220,.23);border-radius:15px;background:rgba(22,68,93,.68);box-shadow:var(--shadow);scroll-snap-align:start;scroll-snap-stop:always;backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px)}
.history-vertical-info{flex:1;min-width:0}
.history-vertical-info strong{display:block;color:#e0f5f8;font-size:16px;overflow-wrap:anywhere}
.history-vertical-info p{margin:7px 0;color:#a9cbd6;font-size:13px;overflow-wrap:anywhere}
.history-vertical-info small{color:#91b8c5;font-size:11px}
.history-vertical-amount{flex-shrink:0;color:#53d9e8;font-size:24px;font-weight:800;text-align:right}
.history-vertical-hint{margin:9px 0 0;color:#91b8c5;font-size:11px;text-align:center}
@media(max-width:600px){.history-vertical-slide{padding:18px;gap:12px}.history-vertical-info strong{font-size:14px}.history-vertical-info p{font-size:12px}.history-vertical-amount{font-size:19px}}
@media(max-width:380px){.history-vertical-viewport{height:165px}.history-vertical-slide{height:165px;flex-basis:165px;align-items:flex-start;flex-direction:column;padding:15px;gap:8px}.history-vertical-amount{align-self:flex-end;font-size:20px}}
</style>
<div class="page-header">
    <div>
        <p class="eyebrow">YOUR SAVINGS GOALS</p>
        <h1>Savings Tracker</h1>
        <p>Record money saved for your bicycle build plans.</p>
    </div>
    <button type="button" class="user-guide-trigger" id="startUserGuide">ⓘ How to Use</button>
</div>
<section class="dashboard-stats savings-stats" data-guide="savings-overview">
    <article class="stat-card">
        <span>Total Savings</span>
        <h2><?= money($totalSavings) ?></h2>
        <p>All your recorded savings</p>
    </article>
    <article class="stat-card">
        <span>Combined Build Budgets</span>
        <h2><?= money($totalBudget) ?></h2>
        <p>Budget across your saved plans</p>
    </article>
    <article class="stat-card">
        <span>Remaining Savings Goal</span>
        <h2><?= money($remaining) ?></h2>
        <p>Combined budget less recorded savings</p>
    </article>
</section>
<section class="content-card savings-form-card">
    <h2>Add Savings</h2>
    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= h($error) ?></div>
    <?php endforeach; ?>
    <?php if (!$projects): ?>
        <p>Create a bicycle build plan before recording savings for it.</p>
    <?php else: ?>
        <form method="post" class="form-grid savings-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-group" data-guide="savings-project">
                <label>Bicycle Build</label>
                <select name="project_id" required>
                    <option value="">Choose build</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= (int)$project['project_id'] ?>"><?= h($project['project_name']) ?> — <?= money($project['budget_amount']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" data-guide="savings-amount">
                <label>Amount Saved (₱)</label>
                <input type="number" name="amount" min="0.01" step="0.01" required>
            </div>
            <div class="form-group" data-guide="savings-date">
                <label>Date</label>
                <input type="date" name="saving_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group" data-guide="savings-notes">
                <label>Notes (Optional)</label>
                <input name="notes" maxlength="255" placeholder="Example: Weekly savings">
            </div>
            <button class="btn savings-submit" type="submit" data-guide="savings-save">Record Savings</button>
        </form>
    <?php endif; ?>
</section>
<section class="dashboard-projects savings-history" data-guide="savings-history">
    <div class="section-heading">
        <div>
            <h2>Savings History</h2>
            <p>Your recorded savings entries.</p>
        </div>
    </div>
    <?php if (!$records): ?>
        <div class="empty-state">No savings records yet.</div>
    <?php else: ?>
        <div class="history-vertical-slider">
            <div class="history-vertical-viewport">
                <div class="history-vertical-track">
                    <?php foreach ($records as $record): ?>
                        <article class="history-vertical-slide">
                            <div class="history-vertical-info">
                                <strong><?= h($record['project_name'] ? $record['project_name'] : 'Unassigned build') ?></strong>
                                <p><?= h($record['notes'] ? $record['notes'] : 'Savings contribution') ?></p>
                                <small><?= h($record['saving_date']) ?></small>
                            </div>
                            <strong class="history-vertical-amount"><?= money($record['amount']) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (count($records) > 1): ?>
                <p class="history-vertical-hint">Scroll up or down to browse your savings records</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<div class="user-guide-backdrop" id="userGuideBackdrop" hidden></div>
<div class="user-guide-panel" id="userGuidePanel" role="dialog" aria-modal="true" aria-labelledby="userGuideTitle" hidden>
    <div class="user-guide-panel-top">
        <div>
            <p class="user-guide-label" id="userGuideCount">HOW TO USE</p>
            <h3 id="userGuideTitle">Savings Guide</h3>
        </div>
        <button type="button" class="user-guide-close" id="userGuideClose" aria-label="Close guide">×</button>
    </div>
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
<script src="<?= h(url('assets/js/user-guide.js')) ?>?v=<?= APP_VERSION ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
