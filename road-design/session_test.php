<?php
echo "Testing session functionality...<br>";

// セッションを開始せずにテスト
echo "PHP Version: " . phpversion() . "<br>";

// セッション関連関数のテスト
if (function_exists('session_start')) {
    echo "✓ session_start function exists<br>";
} else {
    echo "✗ session_start function not found<br>";
}

if (function_exists('session_regenerate_id')) {
    echo "✓ session_regenerate_id function exists<br>";
} else {
    echo "✗ session_regenerate_id function not found<br>";
}

// セッション設定のテスト
$session_save_path = ini_get('session.save_path');
echo "Session save path: " . ($session_save_path ?: 'Not set') . "<br>";

if ($session_save_path && is_dir($session_save_path)) {
    echo "✓ Session save path exists<br>";
    if (is_writable($session_save_path)) {
        echo "✓ Session save path is writable<br>";
    } else {
        echo "✗ Session save path is not writable<br>";
    }
} else {
    echo "✗ Session save path does not exist<br>";
}

// エックスサーバー固有のセッションパスをテスト
$xserver_paths = [
    '/home/iistylelab/tmp',
    '/home/iistylelab/session',
    '/tmp',
    '/var/tmp'
];

echo "<br>X-Server session paths test:<br>";
foreach ($xserver_paths as $path) {
    if (is_dir($path)) {
        $writable = is_writable($path) ? '✓ Writable' : '✗ Not writable';
        echo "$path: Exists, $writable<br>";
    } else {
        echo "$path: Does not exist<br>";
    }
}

// 実際にセッションを開始してみる
echo "<br>Testing session start...<br>";
try {
    session_start();
    echo "✓ Session started successfully<br>";
    $_SESSION['test'] = 'Hello World';
    echo "✓ Session variable set<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "✗ Session error: " . $e->getMessage() . "<br>";
}
?>
