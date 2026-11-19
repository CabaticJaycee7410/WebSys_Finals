
<?php

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Actual Expenses';
$pdo = require __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

$error = '';

$categories = [
    'Bicycle Parts',
    'Maintenance',
    'Repairs',
    'Daily Cycling',
    'Cycling Trips',
    'Other'
];

// Add actual expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $projectId = (int) ($_POST['project_id'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $expenseDate = $_POST['expense_date'] ?? '';

    if (!in_array($category, $categories, true)) {
        $error = 'Please select a valid expense category.';
    } elseif ($description === '') {
        $error = 'Please enter an expense description.';
    } elseif ($amount <= 0) {
        $error = 'Please enter a valid expense amount.';
    } elseif (!$expenseDate) {
        $error = 'Please select an expense date.';
    } else {

        if ($projectId > 0) {

            $check = $pdo->prepare("
                SELECT project_id
                FROM projects
                WHERE project_id = ?
                AND user_id = ?
            ");

            $check->execute([$projectId, $userId]);

            if (!$check->fetch()) {
                $error = 'Selected project was not found.';
            }
        }

        if ($error === '') {

            $stmt = $pdo->prepare("
                INSERT INTO actual_expenses (
                    user_id,
                    project_id,
                    category,
                    description,
                    amount,
                    expense_date
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $projectId > 0 ? $projectId : null,
                $category,
                $description,
                $amount,
                $expenseDate
            ]);

            header('Location: ' . url('expenses.php?saved=1'));
            exit;
        }
    }
}

// Get projects for the dropdown
$stmt = $pdo->prepare("
    SELECT project_id, project_name
    FROM projects
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get actual expenses
$stmt = $pdo->prepare("
    SELECT
        e.*,
        p.project_name
    FROM actual_expenses e
    LEFT JOIN projects p
        ON p.project_id = e.project_id
    WHERE e.user_id = ?
    ORDER BY e.expense_date DESC, e.expense_id DESC
");

$stmt->execute([$userId]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalExpenses = 0;

foreach ($expenses as $expense) {
    $totalExpenses += (float) $expense['amount'];
}

require_once __DIR__ . '/includes/header.php';

?>

<div class="page-header">
    <div>
        <h1>Actual Expenses</h1>
        <p>Track money you have actually spent on cycling.</p>
    </div>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">
        Expense recorded successfully.
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="dashboard-card">
    <p>Total Actual Expenses</p>
    <h2><?= money($totalExpenses) ?></h2>
</div>

<section class="dashboard-card">

    <h2>Record Actual Expense</h2>

    <form method="POST">

        <label for="category">Expense Category</label>

        <select name="category" id="category" required>
            <option value="">Select category</option>

            <?php foreach ($categories as $category): ?>
                <option value="<?= h($category) ?>">
                    <?= h($category) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label for="description">Description</label>

        <input
            type="text"
            name="description"
            id="description"
            placeholder="Example: Bicycle chain replacement"
            required
        >

        <label for="amount">Amount (₱)</label>

        <input
            type="number"
            name="amount"
            id="amount"
            min="0.01"
            step="0.01"
            required
        >

        <label for="project_id">Related Bicycle Build (Optional)</label>

        <select name="project_id" id="project_id">
            <option value="0">No related project</option>

            <?php foreach ($projects as $project): ?>

                <option value="<?= (int) $project['project_id'] ?>">
                    <?= h($project['project_name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="expense_date">Expense Date</label>

        <input
            type="date"
            name="expense_date"
            id="expense_date"
            value="<?= date('Y-m-d') ?>"
            required
        >

        <button type="submit">Save Expense</button>

    </form>

</section>

<section class="dashboard-projects">

    <h2>Expense History</h2>

    <?php if (empty($expenses)): ?>

        <p>No actual expenses recorded yet.</p>

    <?php else: ?>

        <div class="dashboard-card">

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Project</th>
                        <th>Amount</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($expenses as $expense): ?>

                        <tr>
                            <td><?= h($expense['expense_date']) ?></td>

                            <td><?= h($expense['category']) ?></td>

                            <td><?= h($expense['description']) ?></td>

                            <td>
                                <?= h($expense['project_name'] ?? 'Not assigned') ?>
                            </td>

                            <td><?= money($expense['amount']) ?></td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
