
<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off';
    if (!$isSecure && isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        $isSecure = true;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_set_cookie_params(0, '/', '', $isSecure, true);
    session_start();
}

function url($path = '')
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

function h($value)
{
    return htmlspecialchars((string)(isset($value) ? $value : ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($amount)
{
    return '₱' . number_format((float)(isset($amount) ? $amount : 0), 2);
}

function logged_in()
{
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function require_login()
{
    if (!logged_in()) {
        redirect('login.php');
    }
}

function set_flash($type, $message)
{
    $_SESSION['flash_message'] = array(
        'type' => $type,
        'message' => $message
    );
}

function get_flash()
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $message;
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes(32, $strong);
            if ($bytes === false || !$strong) {
                throw new Exception('A secure random generator is unavailable.');
            }
            $_SESSION['csrf_token'] = bin2hex($bytes);
        } else {
            throw new Exception('A secure random generator is unavailable.');
        }
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) &&
        is_string($token) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

function current_user_id()
{
    return (int)(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
}
