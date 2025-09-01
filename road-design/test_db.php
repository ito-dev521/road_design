<?php
echo "<h2>データベース接続テスト</h2>";

// 設定ファイル読み込み
require_once 'config.php';

echo "<h3>設定情報</h3>";
echo "<ul>";
echo "<li>ホスト: " . DB_HOST . "</li>";
echo "<li>データベース: " . DB_NAME . "</li>";
echo "<li>ユーザー: " . DB_USER . "</li>";
echo "<li>文字セット: " . DB_CHARSET . "</li>";
echo "</ul>";

// 接続テスト
try {
    echo "<h3>接続テスト</h3>";
    
    // PDO接続
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    echo "<p>DSN: " . htmlspecialchars($dsn) . "</p>";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ データベース接続成功</p>";
    
    // バージョン確認
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>MySQL/MariaDB バージョン: " . $version['version'] . "</p>";
    
    // テーブル一覧確認
    echo "<h3>テーブル一覧</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠ テーブルが存在しません</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>エラーコード: " . $e->getCode() . "</p>";
}
?>
