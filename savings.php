
<?php

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Savings Tracker';
$pdo = require __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

$message = '';
$error = '';

// Add savings record.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_saving'])) {

    $projectId = (int) ($_POST['project_id'] ?? 0);
    $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
    $savingDate = $_POST['saving_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($projectId <= 0 || $amount === false || $amount <= 0) {
        $error = 'Please enter a valid project and savings amount.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $savingDate)) {
        $error = 'Please enter a valid savings date.';
    } else {

        $check = $pdo->prepare("
            SELECT project_id
            FROM projects
            WHERE project_id = ?
            AND user_id = ?
        ");

        $check->execute([$projectId, $userId]);

        if (!$check->fetch()) {
            $error = 'Invalid bicycle project.';
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO savings_records
                (user_id, project_id, amount, saving_date, notes)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $projectId,
                $amount,
                $savingDate,
                $notes
            ]);

            $message = 'Savings record added successfully.';
        }
    }
}

// Delete savings record.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_saving'])) {

    $savingId = (int) ($_POST['saving_id'] ?? 0);

    $stmt = $pdo->prepare("
        DELETE FROM savings_records
        WHERE saving_id = ?
        AND user_id = ?
    ");

    $stmt->execute([$savingId, $userId]);

    $message = 'Savings record deleted.';
}

// Get projects.
$projectStmt = $pdo->prepare("
    SELECT project_id, project_name, budget_amount
    FROM projects
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$projectStmt->execute([$userId]);
$projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

// Get savings history.
$savingStmt = $pdo->prepare("
    SELECT
        s.saving_id,
        s.project_id,
        s.amount,
        s.saving_date,
        s.notes,
        p.project_name,
        p.budget_amount
    FROM savings_records s
    INNER JOIN projects p
        ON p.project_id = s.project_id
    WHERE s.user_id = ?
    AND p.user_id = ?
    ORDER BY s.saving_date DESC, s.saving_id DESC
");

$savingStmt->execute([$userId, $userId]);
$savings = $savingStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals.
$totalSaved = 0;

foreach ($savings as $saving) {
    $totalSaved += (float) $saving['amount'];
}

$totalTarget = 0;

foreach ($projects as $project) {
    $totalTarget += (float) $project['budget_amount'];
}

$remaining = max(0, $totalTarget - $totalSaved);

require __DIR__ . '/includes/header.php';

?>

<div class="dashboard-header">
    <div>
        <h1>Savings Tracker</h1>
        <p>Track the money you save for your bicycle builds.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert success"><?= h($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>

<section class="dashboard-stats">

    <div class="stat-card">
        <span>Total Savings</span>
        <h2>₱<?= number_format($totalSaved, 2) ?></h2>
    </div>

    <div class="stat-card">
        <span>Total Build Budget</span>
        <h2>₱<?= number_format($totalTarget, 2) ?></h2>
    </div>

    <div class="stat-card">
        <span>Remaining Savings Goal</span>
        <h2>₱<?= number_format($remaining, 2) ?></h2>
    </div>

</section>

<section class="overview-card">

    <h2>Add Savings</h2>

    <?php if (empty($projects)): ?>

        <p>Create a bicycle project first before recording savings.</p>

    <?php else: ?>

        <form method="POST">

            <label>Select Bicycle Build</label>

            <select name="project_id" required>
                <option value="">Choose a project</option>

                <?php foreach ($projects as $project): ?>
                    <option value="<?= (int) $project['project_id'] ?>">
                        <?= h($project['project_name']) ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <label>Amount Saved (PHP)</label>

            <input
                type="number"
                name="amount"
                min="0.01"
                step="0.01"
                placeholder="Enter amount"
                required
            >

            <label>Date Saved</label>

            <input
                type="date"
                name="saving_date"
                value="<?= date('Y-m-d') ?>"
                required
            >

            <label>Notes (Optional)</label>

            <input
                type="text"
                name="notes"
                maxlength="255"
                placeholder="Example: Weekly savings"
            >

            <button type="submit" name="add_saving">
                Save Record
            </button>

        </form>

    <?php endif; ?>

</section>

<section class="overview-card" style="margin-top: 25px;">

    <h2>Savings History</h2>

    <?php if (empty($savings)): ?>

        <p>No savings records yet.</p>

    <?php else: ?>

        <div style="overflow-x:auto;">

            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($savings as $saving): ?>

                        <tr>
                            <td><?= h($saving['project_name']) ?></td>

                            <td>
                                ₱<?= number_format((float) $saving['amount'], 2) ?>
                            </td>

                            <td><?= h($saving['saving_date']) ?></td>

                            <td><?= h($saving['notes'] ?? '') ?></td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this savings record?');">

                                    <input
                                        type="hidden"
                                        name="saving_id"
                                        value="<?= (int) $saving['saving_id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_saving"
                                    >
                                        Delete
                                    </button>

                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
