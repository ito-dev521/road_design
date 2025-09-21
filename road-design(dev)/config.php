<?php
// Database configuration for X-Server MySQL
define('DB_HOST', 'localhost'); // X-Server MySQL host
define('DB_PORT', '3306'); // MySQL default port
define('DB_NAME', 'iistylelab_road');
define('DB_USER', 'iistylelab_road');
define('DB_PASS', 'K6RVCwzMDxtz5dn');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_URL', '/road-design/');
define('SITE_NAME', '道路詳細設計管理システム');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);

// Upload settings
define('UPLOAD_MAX_SIZE', 52428800); // 50MB (CADファイル対応)
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'dwg', 'dxf', 'dwf', 'step', 'stp', 'iges', 'igs', 'sat', 'x_t', 'x_b', 'prt', 'asm', 'sldprt', 'sldasm', 'ipt', 'iam', 'bfo', 'p21', 'sfc']);

// Timezone setting
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Tokyo');
}

// Error reporting (debug mode)
error_reporting(E_ALL);
if (function_exists('ini_set')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    // PHP settings (安全な値に設定)
    ini_set('memory_limit', '128M');
    ini_set('max_execution_time', 30);

    // Session settings (PHP8.3対応)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1); // PHP7.0+ 標準機能

    // PHP8.0+ のセキュリティ強化設定
    if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
        ini_set('session.cookie_samesite', 'Lax');
    }

    // エックスサーバー環境に適したセッションパスを設定
    $session_paths = [
        '/home/iistylelab/tmp',
        '/tmp',
        '/var/tmp',
        sys_get_temp_dir()
    ];
    foreach ($session_paths as $path) {
        if (is_dir($path) && is_writable($path)) {
            ini_set('session.save_path', $path);
            break;
        }
    }
}
?>