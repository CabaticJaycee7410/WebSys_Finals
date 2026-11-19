<?php
require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    $domain = isset($params['domain']) ? $params['domain'] : '';
    setcookie(session_name(), '', time() - 42000, $params['path'], $domain, $params['secure'], $params['httponly']);
}

session_destroy();
header('Location: ' . url('login.php'));
exit;