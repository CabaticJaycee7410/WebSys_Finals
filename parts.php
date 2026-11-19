<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->beginTransaction();

    try {

        // Save bicycle part
        $q = $pdo->prepare("
            INSERT INTO parts
            (category_id, part_name, brand, description)
            VALUES (?, ?, ?, ?)
        ");

        $q->execute([
            $_POST['category_id'] ?: null,
            trim($_POST['part_name']),
            trim($_POST['brand']),
            trim($_POST['description'])
        ]);

        $part = $pdo->lastInsertId();

        // Save supplier
        $s = $pdo->prepare("
            INSERT INTO suppliers
            (supplier_name, marketplace, store_url)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            supplier_id = LAST_INSERT_ID(supplier_id)
        ");

        $s->execute([
            trim($_POST['supplier_name']),
            $_POST['marketplace'],
            trim($_POST['store_url'] ?? '')
        ]);

        $supplier = $pdo->lastInsertId();

        // Save product listing
        $p = $pdo->prepare("
            INSERT INTO supplier_products
            (part_id, supplier_id, product_title, product_url,
             price, currency, shipping_cost, source_type, last_checked)
            VALUES (?, ?, ?, ?, ?, 'PHP', ?, 'manual', NOW())
        ");

        $p->execute([
            $part,
            $supplier,
            trim($_POST['product_title']),
            trim($_POST['product_url']),
            (float)$_POST['price'],
            (float)$_POST['shipping_cost']
        ]);

        // Save price history
        $productId = $pdo->lastInsertId();

        $history = $pdo->prepare("
            INSERT INTO price_history
            (supplier_product_id, price, shipping_cost)
            VALUES (?, ?, ?)
        ");

        $history->execute([
            $productId,
            (float)$_POST['price'],
            (float)$_POST['shipping_cost']
        ]);

        $pdo->commit();

        flash('Part and price listing saved.');

    } catch (Throwable $e) {

        $pdo->rollBack();
        flash('Could not save listing. Check your entries.');
    }

    header('Location: parts.php');
    exit;
}

// Get categories
$cats = $pdo->query("
    SELECT * FROM categories
    ORDER BY category_name
")->fetchAll();

// Get price listings
$rows = $pdo->query("
    SELECT
        p.part_name,
        p.brand,
        sp.product_title,
        sp.product_url,
        sp.price,
        sp.shipping_cost,
        sp.currency,
        sp.source_type,
        s.supplier_name,
        s.marketplace
    FROM supplier_products sp
    JOIN parts p ON p.part_id = sp.part_id
    JOIN suppliers s ON s.supplier_id = sp.supplier_id
    ORDER BY p.part_name, (sp.price + sp.shipping_cost)
")->fetchAll();

$page_title = 'Parts and Price Comparison';
require __DIR__ . '/includes/header.php';
?>

<h1>Parts & Price Comparison</h1>

<p class="muted">
    Compare bicycle components from different marketplaces.
</p>

<section class="card">

    <h2>Add Part Listing</h2>

    <form method="POST" class="form-grid">

        <label>Part Name
            <input name="part_name" placeholder="Shimano Brake Set" required>
        </label>

        <label>Category
            <select name="category_id">
                <option value="">Other</option>

                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['category_id'] ?>">
                        <?= h($c['category_name']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </label>

        <label>Brand
            <input name="brand">
        </label>

        <label>Description
            <input name="description">
        </label>

        <label>Marketplace
            <select name="marketplace">
                <option>Shopee</option>
                <option>TikTok Shop</option>
                <option>Lazada</option>
                <option>Local Store</option>
                <option>Other</option>
            </select>
        </label>

        <label>Store Name
            <input name="supplier_name" required>
        </label>

        <label>Product Title
            <input name="product_title" required>
        </label>

        <label>Product URL
            <input type="url" name="product_url">
        </label>

        <label>Item Price (PHP)
            <input type="number" min="0" step=".01"
                   name="price" required>
        </label>

        <label>Shipping Cost (PHP)
            <input type="number" min="0" step=".01"
                   name="shipping_cost" value="0">
        </label>

        <button type="submit">Save Price Listing</button>

    </form>

</section>

<section class="card">

    <h2>Available Listings</h2>

    <?php if (!$rows): ?>

        <p class="muted">
            No listings yet. Add the same part from multiple stores.
        </p>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <tr>
                    <th>Part</th>
                    <th>Store</th>
                    <th>Item Price</th>
                    <th>Shipping</th>
                    <th>Total</th>
                    <th>Source</th>
                    <th>Link</th>
                </tr>

                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <?= h($r['brand'] . ' ' . $r['part_name']) ?>
                        </td>

                        <td>
                            <?= h($r['marketplace'] . ' / ' . $r['supplier_name']) ?>
                        </td>

                        <td><?= money($r['price']) ?></td>

                        <td><?= money($r['shipping_cost']) ?></td>

                        <td>
                            <strong>
                                <?= money($r['price'] + $r['shipping_cost']) ?>
                            </strong>
                        </td>

                        <td><?= h($r['source_type']) ?></td>

                        <td>
                            <?php if ($r['product_url']): ?>
                                <a href="<?= h($r['product_url']) ?>"
                                   target="_blank" rel="noopener">
                                    View Product
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>