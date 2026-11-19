<?php
header('Content-Type: application/json; charset=utf-8');
$config = require __DIR__ . '/../config/marketplace.php';
require_once __DIR__ . '/providers/lazada.php';

$keyword = trim((string)($_GET['q'] ?? ''));
$brandFilter = trim((string)($_GET['brand'] ?? ''));
$maxBudget = null;
if (isset($_GET['max_budget']) && $_GET['max_budget'] !== '') {
    $maxBudget = filter_var($_GET['max_budget'], FILTER_VALIDATE_FLOAT);
}
$page = max(1, (int)($_GET['page'] ?? 1));

if (strlen($keyword) > 150 || strlen($brandFilter) > 100 || ($maxBudget !== null && ($maxBudget === false || $maxBudget < 0))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid search information.']);
    exit;
}
if ($keyword === '' && $brandFilter === '' && $maxBudget === null) {
    echo json_encode(['success' => false, 'message' => 'Enter a product name, brand, or maximum budget.']);
    exit;
}

function marketplace_value($value, string $default = ''): string {
    if (is_string($value) || is_numeric($value)) return trim((string)$value);
    if (is_array($value)) {
        foreach (['name','title','value','label'] as $key) if (!empty($value[$key])) return trim((string)$value[$key]);
    }
    return $default;
}
function marketplace_price($value): float {
    if (is_numeric($value)) return (float)$value;
    if (is_string($value)) {
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $value));
        return is_numeric($clean) ? (float)$clean : 0.0;
    }
    return 0.0;
}

try {
    $searchKeyword = $keyword !== '' ? $keyword : 'bicycle parts';
    if ($brandFilter !== '' && stripos($searchKeyword, $brandFilter) === false) $searchKeyword .= ' ' . $brandFilter;
    $result = lazada_search_products($config['lazada'] ?? [], $searchKeyword, $page);
    if (empty($result['success'])) {
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    $products = [];
    foreach (($result['products'] ?? []) as $item) {
        if (!is_array($item)) continue;
        $seller = $item['seller'] ?? [];
        $badges = $item['badges'] ?? [];
        $brand = marketplace_value($item['brand'] ?? '', 'No Brand');
        $title = marketplace_value($item['title'] ?? '', 'Unnamed product');
        $price = marketplace_price($item['price'] ?? 0);
        if ($maxBudget !== null && ($price <= 0 || $price > $maxBudget)) continue;
        if ($brandFilter !== '') {
            $needle = strtolower($brandFilter);
            if (strpos(strtolower($brand), $needle) === false && strpos(strtolower($title), $needle) === false) continue;
        }
        $compatibility = $item['compatibility'] ?? [];
        if (is_string($compatibility)) {
            $decoded = json_decode($compatibility, true);
            $compatibility = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($compatibility)) $compatibility = [];
        $products[] = [
            'item_id' => marketplace_value($item['item_id'] ?? ''),
            'sku_id' => marketplace_value($item['sku_id'] ?? ''),
            'title' => $title,
            'brand' => $brand,
            'image' => marketplace_value($item['image'] ?? ''),
            'price' => $price,
            'original_price' => isset($item['original_price']) ? marketplace_price($item['original_price']) : null,
            'discount_percent' => $item['discount_percent'] ?? null,
            'currency' => $item['currency'] ?? 'PHP',
            'rating' => $item['rating'] ?? null,
            'review_count' => (int)($item['review_count'] ?? 0),
            'sold_count' => (int)($item['sold_count'] ?? 0),
            'seller_name' => marketplace_value($seller, 'Unknown seller'),
            'location' => marketplace_value($item['location'] ?? ''),
            'in_stock' => (bool)($item['in_stock'] ?? false),
            'url' => marketplace_value($item['url'] ?? ''),
            'source' => 'Lazada',
            'lazmall' => (bool)($badges['lazmall'] ?? false),
            'free_shipping' => (bool)($badges['free_shipping'] ?? false),
            'flash_sale' => (bool)($badges['flash_sale'] ?? false),
            'is_sponsored' => (bool)($item['is_sponsored'] ?? false),
            'compatibility' => $compatibility
        ];
    }

    echo json_encode([
        'success' => true,
        'query' => $result['query'] ?? $searchKeyword,
        'currency' => $result['currency'] ?? 'PHP',
        'page' => $page,
        'count' => count($products),
        'products' => $products
    ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    error_log('Marketplace search error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while searching Lazada.']);
}
