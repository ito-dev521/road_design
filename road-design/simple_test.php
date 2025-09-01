<?php
// シンプルなテストファイル
echo "PHP is working!<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";

// 基本的な関数テスト
if (function_exists('json_encode')) {
    echo "✓ JSON functions available<br>";
} else {
    echo "✗ JSON functions not available<br>";
}

if (class_exists('PDO')) {
    echo "✓ PDO class available<br>";
} else {
    echo "✗ PDO class not available<br>";
}

if (function_exists('password_hash')) {
    echo "✓ Password functions available<br>";
} else {
    echo "✗ Password functions not available<br>";
}

echo "<br><a href='test.php'>詳細診断ページへ</a>";
echo "<br><a href='index.html'>メインページへ</a>";
?>
