<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Parts Catalog';
$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = [];
$componentOptions = ['frame'=>'Frame','fork'=>'Fork','handlebar'=>'Handlebar','stem'=>'Stem','headset'=>'Headset','grips'=>'Grips','saddle'=>'Saddle','seatpost'=>'Seatpost','wheelset'=>'Wheelset','front_tire'=>'Front Tire','rear_tire'=>'Rear Tire','crankset'=>'Crankset','bottom_bracket'=>'Bottom Bracket','pedals'=>'Pedals','chain'=>'Chain','cassette'=>'Cassette','shifter'=>'Shifter','front_derailleur'=>'Front Derailleur','rear_derailleur'=>'Rear Derailleur','front_brake'=>'Front Brake','rear_brake'=>'Rear Brake','other'=>'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $errors[] = 'Session expired. Refresh and try again.';
    $action = $_POST['action'] ?? 'add';
    if ($action === 'delete') {
        $catalogId = (int)($_POST['catalog_id'] ?? 0);
        if (!$errors) {
            $stmt = $pdo->prepare('DELETE FROM parts_catalog WHERE catalog_id = ? AND user_id = ?');
            $stmt->execute([$catalogId, $userId]);
            set_flash('success', 'Catalog item removed.');
            redirect('catalog.php');
        }
    } else {
        $key = (string)($_POST['component_key'] ?? 'other');
        $title = trim((string)($_POST['product_title'] ?? ''));
        $brand = trim((string)($_POST['brand'] ?? ''));
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $urlValue = trim((string)($_POST['product_url'] ?? ''));
        if (!isset($componentOptions[$key])) $errors[] = 'Choose a valid component.';
        if ($title === '') $errors[] = 'Enter a part name.';
        if ($price === false || $price < 0) $errors[] = 'Enter a valid price.';
        if ($urlValue !== '' && !filter_var($urlValue, FILTER_VALIDATE_URL)) $errors[] = 'Enter a valid product URL.';
        if (!$errors) {
            $stmt = $pdo->prepare("INSERT INTO parts_catalog (user_id,component_key,product_title,brand,price,product_url,source) VALUES (?,?,?,?,?,?, 'Manual')");
            $stmt->execute([$userId,$key,$title,$brand ?: null,$price,$urlValue ?: null]);
            set_flash('success', 'Part added to your catalog.');
            redirect('catalog.php');
        }
    }
}
$stmt = $pdo->prepare('SELECT * FROM parts_catalog WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$parts = $stmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div><p class="eyebrow">YOUR SAVED COMPONENTS</p><h1>Parts Catalog</h1><p>Live products you selected in the builder are saved here for reference.</p></div></div>
<section class="content-card"><h2>Add a Custom Part</h2><p class="muted">Use this only for parts that are not available in the live marketplace.</p><?php foreach ($errors as $error): ?><div class="alert error"><?= h($error) ?></div><?php endforeach; ?><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="form-group"><label>Component</label><select name="component_key"><?php foreach ($componentOptions as $key=>$label): ?><option value="<?= h($key) ?>"><?= h($label) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Part Name</label><input name="product_title" required maxlength="255"></div><div class="form-group"><label>Brand</label><input name="brand" maxlength="120"></div><div class="form-group"><label>Estimated Price (₱)</label><input name="price" type="number" min="0" step="0.01" required></div><div class="form-group full-width"><label>Product URL (Optional)</label><input name="product_url" type="url" placeholder="https://..."></div><button class="btn" type="submit">Add Custom Part</button></form></section>
<section class="dashboard-projects"><div class="section-heading"><div><h2>Saved Parts</h2><p><?= count($parts) ?> catalog item(s)</p></div></div><?php if (!$parts): ?><div class="empty-state"><h3>Your catalog is empty</h3><p>Products selected and saved through the Bicycle Builder will appear here when the marketplace provides an item ID.</p></div><?php else: ?><div class="catalog-grid"><?php foreach ($parts as $part): ?><article class="catalog-card"><?php if ($part['image_url']): ?><img src="<?= h($part['image_url']) ?>" alt="<?= h($part['product_title']) ?>" loading="lazy"><?php else: ?><div class="catalog-placeholder">🚲</div><?php endif; ?><div class="catalog-card-body"><span class="catalog-category"><?= h($componentOptions[$part['component_key']] ?? $part['component_key']) ?></span><h3><?= h($part['product_title']) ?></h3><p><?= h($part['brand'] ?: 'Brand not specified') ?> · <?= h($part['source']) ?></p><strong><?= money($part['price']) ?></strong><?php if ($part['product_url']): ?><a href="<?= h($part['product_url']) ?>" target="_blank" rel="noopener noreferrer">View listing ↗</a><?php endif; ?><form method="post" onsubmit="return confirm('Remove this catalog item?')"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="catalog_id" value="<?= (int)$part['catalog_id'] ?>"><button class="project-delete-button" type="submit">Remove</button></form></div></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
