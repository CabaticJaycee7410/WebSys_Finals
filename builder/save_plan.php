<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function save_reply(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function clean_product_url($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;
    if (!filter_var($value, FILTER_VALIDATE_URL)) return null;

    $parts = parse_url($value);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return null;
    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) return null;

    return substr($value, 0, 2000);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    save_reply(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    save_reply(['success' => false, 'message' => 'Invalid request data.'], 400);
}
if (!verify_csrf_token($input['csrfToken'] ?? null)) {
    save_reply(['success' => false, 'message' => 'Session expired. Refresh the page.'], 403);
}

$userId = current_user_id();
$projectId = max(0, (int)($input['projectId'] ?? 0));
$type = trim((string)($input['bicycleType'] ?? ''));
$brand = trim((string)($input['bicycleBrand'] ?? ''));
$model = trim((string)($input['bicycleModel'] ?? ''));
$name = trim((string)($input['buildName'] ?? ''));
$budget = filter_var($input['budget'] ?? 0, FILTER_VALIDATE_FLOAT);
$parts = $input['parts'] ?? [];

$allowedTypes = [
    'Mountain Bike', 'BMX', 'Road Bike', 'Gravel Bike', 'Hybrid Bike', 'Commuter Bike',
    'City Bike', 'Touring Bike', 'Folding Bike', 'Fat Bike', 'Track / Fixed Gear',
    'Cyclocross Bike', 'Downhill Bike', 'Enduro Bike', 'Cross-Country Bike',
    'Electric Bike', 'Cargo Bike', 'Tandem Bike', 'Recumbent Bike', "Kids' Bike", 'Custom Build'
];

$components = [
    'frame' => 'Frame', 'fork' => 'Fork', 'handlebar' => 'Handlebar', 'stem' => 'Stem',
    'headset' => 'Headset', 'grips' => 'Grips', 'saddle' => 'Saddle', 'seatpost' => 'Seatpost',
    'wheelset' => 'Wheelset', 'front_tire' => 'Front Tire', 'rear_tire' => 'Rear Tire',
    'crankset' => 'Crankset', 'bottom_bracket' => 'Bottom Bracket', 'pedals' => 'Pedals',
    'chain' => 'Chain', 'cassette' => 'Cassette / Rear Cog', 'shifter' => 'Shifter',
    'front_derailleur' => 'Front Derailleur', 'rear_derailleur' => 'Rear Derailleur',
    'front_brake' => 'Front Brake', 'rear_brake' => 'Rear Brake',
    'motor' => 'Electric Motor', 'battery' => 'Battery',
    'controller' => 'Controller / Display', 'cargo_rack' => 'Cargo Rack'
];

if (!in_array($type, $allowedTypes, true)) {
    save_reply(['success' => false, 'message' => 'Choose a valid bicycle type.'], 422);
}
if ($name === '') $name = $type . ' Build';
if (strlen($name) > 150 || strlen($brand) > 120 || strlen($model) > 120) {
    save_reply(['success' => false, 'message' => 'One of the bicycle details is too long.'], 422);
}
if ($budget === false || $budget < 0 || $budget > 999999999) {
    save_reply(['success' => false, 'message' => 'Enter a valid target budget.'], 422);
}
if (!is_array($parts) || count($parts) > count($components)) {
    save_reply(['success' => false, 'message' => 'Invalid list of selected parts.'], 422);
}

$pdo = require __DIR__ . '/../config/database.php';

try {
    $pdo->beginTransaction();

    if ($projectId > 0) {
        $stmt = $pdo->prepare('SELECT project_id, bicycle_id FROM projects WHERE project_id = ? AND user_id = ? FOR UPDATE');
        $stmt->execute([$projectId, $userId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $pdo->rollBack();
            save_reply(['success' => false, 'message' => 'Build plan not found.'], 404);
        }

        $bicycleId = (int)($existing['bicycle_id'] ?? 0);
        if ($bicycleId > 0) {
            $stmt = $pdo->prepare('UPDATE bicycles SET bicycle_name = ?, bicycle_type = ?, brand = ? WHERE bicycle_id = ? AND user_id = ?');
            $stmt->execute([$name, $type, $brand ?: null, $bicycleId, $userId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO bicycles (user_id, bicycle_name, bicycle_type, brand) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $name, $type, $brand ?: null]);
            $bicycleId = (int)$pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('UPDATE projects SET bicycle_id = ?, project_name = ?, budget_amount = ? WHERE project_id = ? AND user_id = ?');
        $stmt->execute([$bicycleId, $name, $budget, $projectId, $userId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO bicycles (user_id, bicycle_name, bicycle_type, brand) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $type, $brand ?: null]);
        $bicycleId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO projects (user_id, bicycle_id, project_name, budget_amount, status) VALUES (?, ?, ?, ?, 'Planning')");
        $stmt->execute([$userId, $bicycleId, $name, $budget]);
        $projectId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('INSERT INTO builder_plans (project_id, bicycle_type, bicycle_brand, bicycle_model, build_name) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bicycle_type = VALUES(bicycle_type), bicycle_brand = VALUES(bicycle_brand), bicycle_model = VALUES(bicycle_model), build_name = VALUES(build_name)');
    $stmt->execute([$projectId, $type, $brand ?: null, $model ?: null, $name]);

    $stmt = $pdo->prepare('DELETE FROM project_build_parts WHERE project_id = ?');
    $stmt->execute([$projectId]);

    $insert = $pdo->prepare('INSERT INTO project_build_parts (project_id, component_key, component_label, product_title, brand, price, image_url, product_url, source, provider_item_id, provider_sku_id, compatibility_json, compatibility_status, forced_incompatible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $catalog = $pdo->prepare('INSERT INTO parts_catalog (user_id, component_key, product_title, brand, price, image_url, product_url, source, provider_item_id, provider_sku_id, compatibility_json) SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM parts_catalog WHERE user_id = ? AND source = ? AND provider_item_id = ? AND provider_sku_id = ?)');
    $savedKeys = [];

    foreach ($parts as $part) {
        if (!is_array($part)) continue;
        $key = trim((string)($part['component_key'] ?? ''));
        $title = trim((string)($part['title'] ?? ''));

        if (!isset($components[$key]) || $title === '' || isset($savedKeys[$key])) continue;
        $savedKeys[$key] = true;

        $price = filter_var($part['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0 || $price > 999999999) $price = 0;

        $compatibility = $part['compatibility'] ?? [];
        if (!is_array($compatibility)) $compatibility = [];
        $compatibilityJson = json_encode($compatibility, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($compatibilityJson === false || strlen($compatibilityJson) > 10000) {
            $compatibilityJson = '{}';
        }

        $status = (string)($part['compatibility_status'] ?? 'needs_review');
        if (!in_array($status, ['compatible', 'needs_review', 'incompatible'], true)) {
            $status = 'needs_review';
        }

        $source = substr(trim((string)($part['source'] ?? 'Lazada')), 0, 50) ?: 'Lazada';
        $itemId = substr(trim((string)($part['item_id'] ?? '')), 0, 100);
        $skuId = substr(trim((string)($part['sku_id'] ?? '')), 0, 100);
        $image = clean_product_url($part['image'] ?? '');
        $link = clean_product_url($part['url'] ?? '');
        $brandValue = substr(trim((string)($part['brand'] ?? '')), 0, 120) ?: null;
        $productTitle = substr($title, 0, 255);
        $label = $components[$key];
        $forced = !empty($part['forced_incompatible']) && $status === 'incompatible' ? 1 : 0;

        $insert->execute([
            $projectId, $key, $label, $productTitle, $brandValue, $price,
            $image, $link, $source, $itemId ?: null, $skuId ?: null,
            $compatibilityJson, $status, $forced
        ]);

        if ($itemId !== '') {
            $catalog->execute([
                $userId, $key, $productTitle, $brandValue, $price, $image,
                $link, $source, $itemId, $skuId ?: null, $compatibilityJson,
                $userId, $source, $itemId, $skuId
            ]);
        }
    }

    $pdo->commit();
    save_reply([
        'success' => true,
        'project_id' => $projectId,
        'message' => 'Your bicycle plan has been saved.'
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Builder save: ' . $error->getMessage());
    save_reply(['success' => false, 'message' => 'Unable to save the plan. Check your database tables and try again.'], 500);
}
