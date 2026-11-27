
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function save_reply($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_product_url($value)
{
    $value = trim((string)$value);
    if ($value === '') return null;
    if (!filter_var($value, FILTER_VALIDATE_URL)) return null;
    $parts = parse_url($value);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return null;
    if (!in_array(strtolower($parts['scheme']), array('http', 'https'), true)) return null;
    return substr($value, 0, 2000);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    save_reply(array('success' => false, 'message' => 'Invalid request method.'), 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    save_reply(array('success' => false, 'message' => 'Invalid request data.'), 400);
}

if (!verify_csrf_token(isset($input['csrfToken']) ? $input['csrfToken'] : null)) {
    save_reply(array('success' => false, 'message' => 'Session expired. Refresh the page.'), 403);
}

$userId = current_user_id();
$projectId = max(0, (int)(isset($input['projectId']) ? $input['projectId'] : 0));
$bicycleId = max(0, (int)(isset($input['bicycleId']) ? $input['bicycleId'] : 0));
$type = trim((string)(isset($input['bicycleType']) ? $input['bicycleType'] : ''));
$brand = trim((string)(isset($input['bicycleBrand']) ? $input['bicycleBrand'] : ''));
$model = trim((string)(isset($input['bicycleModel']) ? $input['bicycleModel'] : ''));
$bicycleName = trim((string)(isset($input['bicycleName']) ? $input['bicycleName'] : ''));
$name = trim((string)(isset($input['buildName']) ? $input['buildName'] : ''));
$budget = filter_var(isset($input['budget']) ? $input['budget'] : 0, FILTER_VALIDATE_FLOAT);
$parts = isset($input['parts']) ? $input['parts'] : array();

$allowedTypes = array(
    'Mountain Bike',
    'Road Bike',
    'BMX',
    'Gravel Bike',
    'Hybrid Bike',
    'Touring Bike',
    'Folding Bike',
    'Fat Bike',
    'Track / Fixed Gear',
    'Electric Bike',
    'Cargo Bike',
    'Custom Build'
);

$components = array(
    'frame' => 'Frame',
    'fork' => 'Fork',
    'handlebar' => 'Handlebar',
    'stem' => 'Stem',
    'headset' => 'Headset',
    'grips' => 'Grips',
    'saddle' => 'Saddle',
    'seatpost' => 'Seatpost',
    'wheelset' => 'Wheelset',
    'front_tire' => 'Front Tire',
    'rear_tire' => 'Rear Tire',
    'crankset' => 'Crankset',
    'bottom_bracket' => 'Bottom Bracket',
    'pedals' => 'Pedals',
    'chain' => 'Chain',
    'cassette' => 'Cassette / Rear Cog',
    'shifter' => 'Shifter',
    'front_derailleur' => 'Front Derailleur',
    'rear_derailleur' => 'Rear Derailleur',
    'front_brake' => 'Front Brake',
    'rear_brake' => 'Rear Brake',
    'motor' => 'Electric Motor',
    'battery' => 'Battery',
    'controller' => 'Controller / Display',
    'cargo_rack' => 'Cargo Rack'
);

if (!in_array($type, $allowedTypes, true)) {
    save_reply(array('success' => false, 'message' => 'Choose a valid bicycle type.'), 422);
}

if ($bicycleName === '') $bicycleName = $type;
if ($name === '') $name = $type . ' Build';

if (strlen($bicycleName) > 150 || strlen($name) > 150 || strlen($brand) > 120 || strlen($model) > 120) {
    save_reply(array('success' => false, 'message' => 'One of the bicycle details is too long.'), 422);
}

if ($budget === false || $budget < 0 || $budget > 999999999) {
    save_reply(array('success' => false, 'message' => 'Enter a valid target budget.'), 422);
}

if (!is_array($parts) || count($parts) > count($components)) {
    save_reply(array('success' => false, 'message' => 'Invalid list of selected parts.'), 422);
}

$pdo = require __DIR__ . '/../config/database.php';

try {
    $pdo->beginTransaction();

    if ($projectId > 0) {
        $stmt = $pdo->prepare('SELECT project_id, bicycle_id FROM projects WHERE project_id = ? AND user_id = ? FOR UPDATE');
        $stmt->execute(array($projectId, $userId));
        $existing = $stmt->fetch();

        if (!$existing) {
            $pdo->rollBack();
            save_reply(array('success' => false, 'message' => 'Build plan not found.'), 404);
        }

        if ($bicycleId > 0) {
            $stmt = $pdo->prepare('SELECT bicycle_id FROM bicycles WHERE bicycle_id = ? AND user_id = ? LIMIT 1');
            $stmt->execute(array($bicycleId, $userId));

            if (!$stmt->fetch()) {
                $pdo->rollBack();
                save_reply(array('success' => false, 'message' => 'The selected bicycle was not found in your account.'), 422);
            }
        } else {
            $bicycleId = (int)$existing['bicycle_id'];
        }

        if ($bicycleId <= 0) {
            $stmt = $pdo->prepare('INSERT INTO bicycles (user_id, bicycle_name, bicycle_type, brand, model) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(array(
                $userId,
                $bicycleName,
                $type,
                $brand !== '' ? $brand : null,
                $model !== '' ? $model : null
            ));
            $bicycleId = (int)$pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare('UPDATE bicycles SET bicycle_name = ?, bicycle_type = ?, brand = ?, model = ? WHERE bicycle_id = ? AND user_id = ?');
            $stmt->execute(array(
                $bicycleName,
                $type,
                $brand !== '' ? $brand : null,
                $model !== '' ? $model : null,
                $bicycleId,
                $userId
            ));
        }

        $stmt = $pdo->prepare('UPDATE projects SET bicycle_id = ?, project_name = ?, budget_amount = ? WHERE project_id = ? AND user_id = ?');
        $stmt->execute(array($bicycleId, $name, $budget, $projectId, $userId));
    } else {
        if ($bicycleId > 0) {
            $stmt = $pdo->prepare('SELECT bicycle_id FROM bicycles WHERE bicycle_id = ? AND user_id = ? LIMIT 1');
            $stmt->execute(array($bicycleId, $userId));

            if (!$stmt->fetch()) {
                $pdo->rollBack();
                save_reply(array('success' => false, 'message' => 'The selected bicycle was not found in your account.'), 422);
            }

            $stmt = $pdo->prepare('UPDATE bicycles SET bicycle_name = ?, bicycle_type = ?, brand = ?, model = ? WHERE bicycle_id = ? AND user_id = ?');
            $stmt->execute(array(
                $bicycleName,
                $type,
                $brand !== '' ? $brand : null,
                $model !== '' ? $model : null,
                $bicycleId,
                $userId
            ));
        } else {
            $stmt = $pdo->prepare('INSERT INTO bicycles (user_id, bicycle_name, bicycle_type, brand, model) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(array(
                $userId,
                $bicycleName,
                $type,
                $brand !== '' ? $brand : null,
                $model !== '' ? $model : null
            ));
            $bicycleId = (int)$pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO projects (user_id, bicycle_id, project_name, budget_amount, status) VALUES (?, ?, ?, ?, 'Planning')");
        $stmt->execute(array($userId, $bicycleId, $name, $budget));
        $projectId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('INSERT INTO builder_plans (project_id, bicycle_type, bicycle_brand, bicycle_model, build_name) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bicycle_type = VALUES(bicycle_type), bicycle_brand = VALUES(bicycle_brand), bicycle_model = VALUES(bicycle_model), build_name = VALUES(build_name)');
    $stmt->execute(array(
        $projectId,
        $type,
        $brand !== '' ? $brand : null,
        $model !== '' ? $model : null,
        $name
    ));

    $stmt = $pdo->prepare('DELETE FROM project_build_parts WHERE project_id = ?');
    $stmt->execute(array($projectId));

    $insert = $pdo->prepare('INSERT INTO project_build_parts (project_id, component_key, component_label, product_title, brand, price, image_url, product_url, source, provider_item_id, provider_sku_id, compatibility_json, compatibility_status, forced_incompatible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $catalog = $pdo->prepare('INSERT INTO parts_catalog (user_id, component_key, product_title, brand, price, image_url, product_url, source, provider_item_id, provider_sku_id, compatibility_json) SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM parts_catalog WHERE user_id = ? AND source = ? AND provider_item_id = ? AND provider_sku_id = ?)');

    $savedKeys = array();

    foreach ($parts as $part) {
        if (!is_array($part)) continue;

        $key = trim((string)(isset($part['component_key']) ? $part['component_key'] : ''));
        $title = trim((string)(isset($part['title']) ? $part['title'] : ''));

        if (!isset($components[$key]) || $title === '' || isset($savedKeys[$key])) continue;

        $savedKeys[$key] = true;

        $price = filter_var(isset($part['price']) ? $part['price'] : 0, FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0 || $price > 999999999) $price = 0;

        $compatibility = isset($part['compatibility']) ? $part['compatibility'] : array();
        if (!is_array($compatibility)) $compatibility = array();

        $compatibilityJson = json_encode($compatibility, JSON_UNESCAPED_UNICODE);
        if ($compatibilityJson === false || strlen($compatibilityJson) > 10000) $compatibilityJson = '{}';

        $status = (string)(isset($part['compatibility_status']) ? $part['compatibility_status'] : 'needs_review');
        if (!in_array($status, array('compatible', 'needs_review', 'incompatible'), true)) $status = 'needs_review';

        $source = substr(trim((string)(isset($part['source']) ? $part['source'] : 'Lazada')), 0, 50);
        if ($source === '') $source = 'Lazada';

        $itemId = substr(trim((string)(isset($part['item_id']) ? $part['item_id'] : '')), 0, 100);
        $skuId = substr(trim((string)(isset($part['sku_id']) ? $part['sku_id'] : '')), 0, 100);

        $image = clean_product_url(isset($part['image']) ? $part['image'] : '');
        $link = clean_product_url(isset($part['url']) ? $part['url'] : '');

        $brandValue = substr(trim((string)(isset($part['brand']) ? $part['brand'] : '')), 0, 120);
        if ($brandValue === '') $brandValue = null;

        $productTitle = substr($title, 0, 255);
        $label = $components[$key];
        $forced = !empty($part['forced_incompatible']) && $status === 'incompatible' ? 1 : 0;

        $insert->execute(array(
            $projectId,
            $key,
            $label,
            $productTitle,
            $brandValue,
            $price,
            $image,
            $link,
            $source,
            $itemId !== '' ? $itemId : null,
            $skuId !== '' ? $skuId : null,
            $compatibilityJson,
            $status,
            $forced
        ));

        if ($itemId !== '') {
            $catalog->execute(array(
                $userId,
                $key,
                $productTitle,
                $brandValue,
                $price,
                $image,
                $link,
                $source,
                $itemId,
                $skuId !== '' ? $skuId : null,
                $compatibilityJson,
                $userId,
                $source,
                $itemId,
                $skuId
            ));
        }
    }

    $pdo->commit();

    save_reply(array(
        'success' => true,
        'project_id' => $projectId,
        'bicycle_id' => $bicycleId,
        'message' => 'Your bicycle plan has been saved.'
    ));
} catch (Exception $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Builder save: ' . $error->getMessage());
    save_reply(array('success' => false, 'message' => 'Unable to save the plan. Check your database tables and try again.'), 500);
}
