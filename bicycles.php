<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $q = $pdo->prepare("
        INSERT INTO bicycles
        (user_id, bicycle_name, bicycle_type, brand)
        VALUES (?, ?, ?, ?)
    ");

    $q->execute([
        $uid,
        trim($_POST['bicycle_name']),
        trim($_POST['bicycle_type']),
        trim($_POST['brand'])
    ]);

    flash('Bicycle saved.');
    header('Location: bicycles.php');
    exit;
}

$q = $pdo->prepare("
    SELECT * FROM bicycles
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$q->execute([$uid]);
$rows = $q->fetchAll();

$page_title = 'My Bicycles';
require __DIR__ . '/includes/header.php';
?>

<h1>My Bicycles</h1>

<section class="card">
    <h2>Add Bicycle</h2>

    <form method="POST" class="form-grid">

        <label>Bicycle Name
            <input name="bicycle_name" required>
        </label>

        <label>Bicycle Type
            <input name="bicycle_type" placeholder="Road, MTB, Gravel">
        </label>

        <label>Brand
            <input name="brand">
        </label>

        <button type="submit">Save Bicycle</button>

    </form>
</section>

<section class="card">
    <h2>Saved Bicycles</h2>

    <?php if (!$rows): ?>
        <p class="muted">No bicycles added yet.</p>
    <?php else: ?>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Brand</th>
                </tr>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= h($r['bicycle_name']) ?></td>
                        <td><?= h($r['bicycle_type']) ?></td>
                        <td><?= h($r['brand']) ?></td>
                    </tr>
                <?php endforeach; ?>

            </table>
        </div>

    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>