<?php

function lazada_search_products(array $settings, string $keyword, int $page = 1): array
{
    if (empty($settings['enabled'])) {
        return ['success' => false, 'message' => 'Lazada integration is disabled.'];
    }

    $baseUrl = rtrim((string)($settings['base_url'] ?? ''), '/');
    $apiKey = trim((string)($settings['api_key'] ?? ''));

    if ($baseUrl === '' || $apiKey === '') {
        return [
            'success' => false,
            'message' => 'Scrappa is not configured. Add your Scrappa base URL and API key in config/marketplace.php.'
        ];
    }

    if (trim($keyword) === '') {
        return ['success' => false, 'message' => 'Enter a search keyword.'];
    }

    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL is not enabled in XAMPP.'];
    }

    $url = $baseUrl . '/lazada/search?' . http_build_query([
        'query' => trim($keyword),
        'country' => 'ph',
        'page' => max(1, $page)
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . $apiKey,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Marketplace connection failed: ' . $error];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['success' => false, 'message' => 'Scrappa returned an invalid response.'];
    }

    if ($status < 200 || $status >= 300 || empty($data['success'])) {
        return [
            'success' => false,
            'message' => $data['message'] ?? ('Scrappa request failed. HTTP ' . $status)
        ];
    }

    return [
        'success' => true,
        'query' => $data['data']['query'] ?? $keyword,
        'currency' => $data['data']['currency'] ?? 'PHP',
        'products' => $data['data']['results'] ?? []
    ];
}
