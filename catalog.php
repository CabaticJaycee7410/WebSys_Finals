<?php

require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $name = trim($_POST['part_name'] ?? '');

    $component = trim($_POST['component_key'] ?? '');

    $price = max(0, (float)($_POST['price'] ?? 0));

    $store = trim($_POST['store_name'] ?? '');

    $link = trim($_POST['product_url'] ?? '');

    if ($name !== '' && $component !== '') {

        $stmt = $pdo->prepare(
            'INSERT INTO parts_catalog
            (
                user_id,
                component_key,
                part_name,
                price_php,
                store_name,
                product_url
            )
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            current_user_id(),
            $component,
            $name,
            $price,
            $store,
            $link
        ]);

        flash(
            'Catalog item added. This is a user-entered item, not a live marketplace listing.'
        );
    }

    header('Location: ' . url('catalog.php'));

    exit;
}

$stmt = $pdo->prepare(
    'SELECT *
     FROM parts_catalog
     WHERE user_id = ?
     ORDER BY created_at DESC'
);

$stmt->execute([current_user_id()]);

$items = $stmt->fetchAll();

$pageTitle = 'Parts Catalog';

require __DIR__ . '/includes/header.php';

?>

<h1>My parts catalog</h1>

<p class="muted">
    Add items you have checked yourself. These are user-added items,
    not verified live marketplace data.
</p>

<div class="panel">

    <h2>Add a part</h2>

    <form method="POST" class="form-grid">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= h(csrf_token()) ?>"
        >

        <label>
            Component

            <select name="component_key">

                <?php

                $components = [
                    'frame',
                    'fork',
                    'tires',
                    'handlebar',
                    'saddle',
                    'seatpost',
                    'crankset',
                    'pedals',
                    'chain',
                    'cassette',
                    'brakes',
                    'wheels'
                ];

                foreach ($components as $component):

                ?>

                    <option value="<?= h($component) ?>">
                        <?= ucfirst($component) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Part name

            <input
                name="part_name"
                required
            >
        </label>

        <label>
            Price (PHP)

            <input
                type="number"
                name="price"
                min="0"
                step="0.01"
                required
            >
        </label>

        <label>
            Store / seller (optional)

            <input name="store_name">
        </label>

        <label>
            Product URL (optional)

            <input
                type="url"
                name="product_url"
                placeholder="https://..."
            >
        </label>

        <button class="btn">
            Add catalog item
        </button>

    </form>

</div>

<h2>Saved catalog items</h2>

<?php if (!$items): ?>

    <p>No items added yet.</p>

<?php else: ?>

    <div class="build-grid">

        <?php foreach ($items as $item): ?>

            <article class="build-card">

                <span class="tag">
                    User-added catalog item
                </span>

                <h3><?= h($item['part_name']) ?></h3>

                <p>
                    <?= h($item['component_key']) ?>
                    ·
                    <?= h($item['store_name'] ?? '') ?>
                </p>

                <strong>
                    <?= money($item['price_php']) ?>
                </strong>

                <?php if ($item['product_url']): ?>

                    <p>
                        <a
                            href="<?= h($item['product_url']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Open product page
                        </a>
                    </p>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>