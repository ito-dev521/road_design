<?php
echo "<h1>道路詳細設計管理システム - デバッグページ</h1>";
echo "<p>すべての500エラーの原因を特定するためのテストページです。</p>";

// テストファイルの一覧
$tests = [
    'basic_test.php' => '基本PHP機能テスト',
    'config_test.php' => '設定ファイルテスト',
    'session_test.php' => 'セッションテスト',
    'auth_test.php' => '認証システムテスト',
    'db_test.php' => 'データベース接続テスト',
    'api_test.php' => 'APIシステムテスト'
];

echo "<h2>テストファイル一覧</h2>";
echo "<ul>";
foreach ($tests as $file => $description) {
    if (file_exists($file)) {
        echo "<li><a href='$file'>$description ($file)</a></li>";
    } else {
        echo "<li style='color: red;'>$description ($file) - ファイルが存在しません</li>";
    }
}
echo "</ul>";

echo "<h2>サーバー情報</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "<li>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</li>";
echo "<li>Current Directory: " . __DIR__ . "</li>";
echo "<li>Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</li>";
echo "<li>User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</li>";
echo "</ul>";

echo "<h2>エックスサーバー固有情報</h2>";
echo "<ul>";
echo "<li>サーバー番号: sv12546</li>";
echo "<li>MySQLホスト: sv12546.xserver.jp</li>";
echo "<li>ホームディレクトリ: /home/iistylelab</li>";
echo "<li>PHPバージョン: 8.3.21 利用可能</li>";
echo "<li>MariaDBバージョン: 10.5.x</li>";
echo "</ul>";

echo "<h2>PHP拡張モジュール</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
echo "<ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color: green;'>✓ $ext</li>";
    } else {
        echo "<li style='color: red;'>✗ $ext (未インストール)</li>";
    }
}
echo "</ul>";

echo "<h2>ファイル権限チェック</h2>";
$files_to_check = ['config.php', 'auth.php', 'database.php', 'api.php'];
echo "<ul>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<li>$file: $perms</li>";
    } else {
        echo "<li style='color: red;'>$file: ファイルが存在しません</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.html'>← メインページに戻る</a></p>";
?>
