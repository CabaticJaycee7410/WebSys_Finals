
<?php

require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'My Bicycles';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = array();

$types = array(
    'Mountain Bike',
    'BMX',
    'Road Bike',
    'Gravel Bike',
    'Hybrid Bike',
    'Commuter Bike',
    'City Bike',
    'Touring Bike',
    'Folding Bike',
    'Fat Bike',
    'Track / Fixed Gear',
    'Cyclocross Bike',
    'Downhill Bike',
    'Enduro Bike',
    'Cross-Country Bike',
    'Electric Bike',
    'Cargo Bike',
    'Tandem Bike',
    'Recumbent Bike',
    "Kids' Bike",
    'Custom Build'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Session expired. Refresh and try again.';
    }

    $name = trim((string)(isset($_POST['bicycle_name']) ? $_POST['bicycle_name'] : ''));
    $type = trim((string)(isset($_POST['bicycle_type']) ? $_POST['bicycle_type'] : ''));
    $brand = trim((string)(isset($_POST['brand']) ? $_POST['brand'] : ''));

    if ($name === '') {
        $errors[] = 'Enter a bicycle name.';
    }

    if (!in_array($type, $types, true)) {
        $errors[] = 'Choose a valid bicycle type.';
    }

    if (!$errors) {

        $stmt = $pdo->prepare(
            'INSERT INTO bicycles (user_id, bicycle_name, bicycle_type, brand) VALUES (?, ?, ?, ?)'
        );

        $stmt->execute(array(
            $userId,
            $name,
            $type,
            $brand !== '' ? $brand : null
        ));

        set_flash('success', 'Bicycle profile added. You can now create a build plan for it');
        redirect('bicycles.php');
    }
}

$stmt = $pdo->prepare(
    'SELECT b.*, COUNT(p.project_id) AS build_count
     FROM bicycles b
     LEFT JOIN projects p ON p.bicycle_id = b.bicycle_id
     WHERE b.user_id = ?
     GROUP BY b.bicycle_id
     ORDER BY b.created_at DESC'
);

$stmt->execute(array($userId));
$bicycles = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <p class="eyebrow">YOUR GARAGE</p>
        <h1>My Bicycles</h1>
        <p>Keep your bicycle profiles organized.</p>
    </div>

    <a class="dashboard-new-build" href="<?= h(url('builder/index.php')) ?>">＋ Build New Bike</a>
</div>

<section class="content-card">
    <h2>Add a Bicycle Profile</h2>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= h($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

        <div class="form-group">
            <label>Bicycle Name</label>
            <input name="bicycle_name" required placeholder="Example: My Trail Bike">
        </div>

        <div class="form-group">
            <label>Bicycle Type</label>
            <select name="bicycle_type" required>
                <option value="">Choose bicycle type</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= h($type) ?>"><?= h($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Brand (Optional)</label>
            <input name="brand" placeholder="Example: Giant">
        </div>

        <button class="btn" type="submit">Save Bicycle</button>
    </form>
</section>

<section class="dashboard-projects">
    <div class="section-heading">
        <div>
            <h2>Your Bicycles</h2>
            <p><?= count($bicycles) ?> bicycle profile(s)</p>
        </div>
    </div>

    <?php if (!$bicycles): ?>

        <div class="empty-state">
            <h3>No bicycles registered</h3>
            <p>You can add a bicycle here or start a new build using the Bicycle Builder.</p>
        </div>

    <?php else: ?>

        <div class="project-list">

            <?php foreach ($bicycles as $bike): ?>

                <article class="project-card">
                    <div class="project-card-main">
                        <div class="project-bike-icon">🚲</div>

                        <div class="project-card-details">
                            <h3><?= h($bike['bicycle_name']) ?></h3>

                            <p>
                                <?= h($bike['bicycle_type']) ?>
                                <?= !empty($bike['brand']) ? ' · ' . h($bike['brand']) : '' ?>
                            </p>

                            <small><?= (int)$bike['build_count'] ?> associated build plan(s)</small>
                        </div>
                    </div>

                    <div class="project-card-budget">
                        <a href="<?= h(url('builder/index.php')) ?>">Create a Build Plan →</a>
                    </div>
                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
