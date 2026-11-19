<?php

function lazada_search_products($settings, $keyword, $page = 1)
{
    if (empty($settings['enabled'])) {
        return array(
            'success' => false,
            'message' => 'Lazada integration is disabled.'
        );
    }

    $baseUrl = rtrim(trim(isset($settings['base_url']) ? $settings['base_url'] : ''), '/');
    $apiKey = trim(isset($settings['api_key']) ? $settings['api_key'] : '');

    if ($baseUrl === '' || $apiKey === '') {
        return array(
            'success' => false,
            'message' => 'Scrappa is not configured. Check config/marketplace.php.'
        );
    }

    $keyword = trim($keyword);

    if ($keyword === '') {
        return array(
            'success' => false,
            'message' => 'Enter a search keyword.'
        );
    }

    if (!function_exists('curl_init')) {
        return array(
            'success' => false,
            'message' => 'PHP cURL is not enabled in XAMPP.'
        );
    }

    if (preg_match('#/api$#i', $baseUrl)) {
        $endpoint = $baseUrl . '/lazada/search';
    } else {
        $endpoint = $baseUrl . '/api/lazada/search';
    }

    $url = $endpoint . '?' . http_build_query(array(
        'query' => $keyword,
        'country' => 'ph',
        'page' => max(1, (int)$page)
    ));

    $ch = curl_init($url);

    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'X-API-KEY: ' . $apiKey,
            'Accept: application/json'
        ),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true
    ));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false) {
        return array(
            'success' => false,
            'message' => 'Marketplace connection failed: ' . $error
        );
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        return array(
            'success' => false,
            'message' => 'Scrappa returned an invalid JSON response.'
        );
    }

    if ($status < 200 || $status >= 300 || empty($data['success'])) {
        return array(
            'success' => false,
            'message' => isset($data['message'])
                ? $data['message']
                : 'Scrappa request failed. HTTP ' . $status
        );
    }

    $responseData = isset($data['data']) ? $data['data'] : array();
    $results = isset($responseData['results']) ? $responseData['results'] : array();

    if (!is_array($results)) {
        $results = array();
    }

    $products = array();

    foreach ($results as $product) {
        if (!is_array($product)) {
            continue;
        }

        $brand = isset($product['brand']) ? $product['brand'] : array();
        $seller = isset($product['seller']) ? $product['seller'] : array();
        $badges = isset($product['badges']) ? $product['badges'] : array();

        $products[] = array(
            'item_id' => isset($product['item_id']) ? $product['item_id'] : null,
            'sku_id' => isset($product['sku_id']) ? $product['sku_id'] : null,
            'title' => isset($product['title']) ? $product['title'] : 'Unnamed Product',
            'image' => isset($product['image']) ? $product['image'] : null,
            'images' => isset($product['images']) && is_array($product['images']) ? $product['images'] : array(),
            'price' => isset($product['price']) ? $product['price'] : 0,
            'original_price' => isset($product['original_price']) ? $product['original_price'] : null,
            'discount_percent' => isset($product['discount_percent']) ? $product['discount_percent'] : null,
            'currency' => isset($product['currency'])
                ? $product['currency']
                : (isset($responseData['currency']) ? $responseData['currency'] : 'PHP'),
            'rating' => isset($product['rating']) ? $product['rating'] : null,
            'review_count' => isset($product['review_count']) ? $product['review_count'] : null,
            'sold_count' => isset($product['sold_count']) ? $product['sold_count'] : null,
            'seller' => $seller,
            'seller_name' => isset($seller['name']) ? $seller['name'] : null,
            'seller_id' => isset($seller['id']) ? $seller['id'] : null,
            'brand' => $brand,
            'brand_name' => isset($brand['name']) ? $brand['name'] : null,
            'brand_id' => isset($brand['id']) ? $brand['id'] : null,
            'shop_id' => isset($product['shop_id']) ? $product['shop_id'] : null,
            'location' => isset($product['location']) ? $product['location'] : null,
            'categories' => isset($product['categories']) ? $product['categories'] : array(),
            'in_stock' => isset($product['in_stock']) ? $product['in_stock'] : null,
            'url' => isset($product['url']) ? $product['url'] : null,
            'badges' => $badges,
            'is_sponsored' => isset($product['is_sponsored']) ? $product['is_sponsored'] : false,
            'compatibility' => isset($product['compatibility']) ? $product['compatibility'] : null
        );
    }

    return array(
        'success' => true,
        'query' => isset($responseData['query']) ? $responseData['query'] : $keyword,
        'country' => isset($responseData['country']) ? $responseData['country'] : 'ph',
        'currency' => isset($responseData['currency']) ? $responseData['currency'] : 'PHP',
        'category' => isset($responseData['category']) ? $responseData['category'] : null,
        'products' => $products,
        'total_results' => count($products),
        'page' => max(1, (int)$page)
    );
}