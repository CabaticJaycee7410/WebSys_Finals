<?php

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/marketplace.php';

require_once __DIR__ . '/providers/lazada.php';

$keyword = trim((string)(isset($_GET['q']) ? $_GET['q'] : ''));
$brandFilter = trim((string)(isset($_GET['brand']) ? $_GET['brand'] : ''));
$maxBudget = null;

if (isset($_GET['max_budget']) && $_GET['max_budget'] !== '') {
    $maxBudget = filter_var($_GET['max_budget'], FILTER_VALIDATE_FLOAT);
}

$page = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));

if (
    strlen($keyword) > 150 ||
    strlen($brandFilter) > 100 ||
    ($maxBudget !== null && ($maxBudget === false || $maxBudget < 0))
) {
    http_response_code(422);
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid search information.'
    ));
    exit;
}

if ($keyword === '' && $brandFilter === '' && $maxBudget === null) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Enter a product name, brand, or maximum budget.'
    ));
    exit;
}

function marketplace_value($value, $default = '')
{
    if (is_string($value) || is_numeric($value)) {
        return trim((string)$value);
    }

    if (is_array($value)) {
        foreach (array('name', 'title', 'value', 'label') as $key) {
            if (isset($value[$key]) && (is_string($value[$key]) || is_numeric($value[$key]))) {
                return trim((string)$value[$key]);
            }
        }
    }

    return $default;
}

function marketplace_price($value)
{
    if (is_numeric($value)) {
        return (float)$value;
    }

    if (is_string($value)) {
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $value));
        return is_numeric($clean) ? (float)$clean : 0.0;
    }

    return 0.0;
}

try {
    $searchKeyword = $keyword !== '' ? $keyword : 'bicycle parts';

    if (
        $brandFilter !== '' &&
        stripos($searchKeyword, $brandFilter) === false
    ) {
        $searchKeyword .= ' ' . $brandFilter;
    }

    $settings = isset($config['lazada']) ? $config['lazada'] : array();

    $result = lazada_search_products(
        $settings,
        $searchKeyword,
        $page
    );

    if (empty($result['success'])) {
        echo json_encode(
            $result,
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    $products = array();

    foreach (isset($result['products']) ? $result['products'] : array() as $item) {
        if (!is_array($item)) {
            continue;
        }

        $seller = isset($item['seller']) ? $item['seller'] : array();
        $badges = isset($item['badges']) ? $item['badges'] : array();

        $brand = marketplace_value(
            isset($item['brand']) ? $item['brand'] : '',
            'No Brand'
        );

        $title = marketplace_value(
            isset($item['title']) ? $item['title'] : '',
            'Unnamed product'
        );

        $price = marketplace_price(
            isset($item['price']) ? $item['price'] : 0
        );

        if ($maxBudget !== null && ($price <= 0 || $price > $maxBudget)) {
            continue;
        }

        if ($brandFilter !== '') {
            $needle = strtolower($brandFilter);

            if (
                strpos(strtolower($brand), $needle) === false &&
                strpos(strtolower($title), $needle) === false
            ) {
                continue;
            }
        }

        $compatibility = isset($item['compatibility'])
            ? $item['compatibility']
            : array();

        if (is_string($compatibility)) {
            $decoded = json_decode($compatibility, true);
            $compatibility = is_array($decoded) ? $decoded : array();
        }

        if (!is_array($compatibility)) {
            $compatibility = array();
        }

        $products[] = array(
            'item_id' => marketplace_value(isset($item['item_id']) ? $item['item_id'] : ''),
            'sku_id' => marketplace_value(isset($item['sku_id']) ? $item['sku_id'] : ''),
            'title' => $title,
            'brand' => $brand,
            'image' => marketplace_value(isset($item['image']) ? $item['image'] : ''),
            'images' => isset($item['images']) && is_array($item['images']) ? $item['images'] : array(),
            'price' => $price,
            'original_price' => isset($item['original_price']) ? marketplace_price($item['original_price']) : null,
            'discount_percent' => isset($item['discount_percent']) ? $item['discount_percent'] : null,
            'currency' => isset($item['currency']) ? $item['currency'] : 'PHP',
            'rating' => isset($item['rating']) ? $item['rating'] : null,
            'review_count' => (int)(isset($item['review_count']) ? $item['review_count'] : 0),
            'sold_count' => (int)(isset($item['sold_count']) ? $item['sold_count'] : 0),
            'seller_name' => marketplace_value($seller, 'Unknown seller'),
            'seller_id' => marketplace_value(isset($item['seller_id']) ? $item['seller_id'] : ''),
            'shop_id' => marketplace_value(isset($item['shop_id']) ? $item['shop_id'] : ''),
            'location' => marketplace_value(isset($item['location']) ? $item['location'] : ''),
            'in_stock' => !empty($item['in_stock']),
            'url' => marketplace_value(isset($item['url']) ? $item['url'] : ''),
            'source' => 'Lazada',
            'lazmall' => !empty($badges['lazmall']),
            'free_shipping' => !empty($badges['free_shipping']),
            'flash_sale' => !empty($badges['flash_sale']),
            'is_sponsored' => !empty($item['is_sponsored']),
            'compatibility' => $compatibility
        );
    }

    echo json_encode(array(
        'success' => true,
        'query' => isset($result['query']) ? $result['query'] : $searchKeyword,
        'currency' => isset($result['currency']) ? $result['currency'] : 'PHP',
        'page' => $page,
        'count' => count($products),
        'products' => $products
    ), JSON_UNESCAPED_SLASHES);

} catch (Exception $error) {
    error_log('Marketplace search error: ' . $error->getMessage());

    http_response_code(500);

    echo json_encode(array(
        'success' => false,
        'message' => 'An error occurred while searching Lazada.'
    ));
}