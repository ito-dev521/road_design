<?php
// X-Server MySQL設定の確認用ファイル

echo "<h2>X-Server MySQL設定確認</h2>";

// 一般的なX-ServerのMySQLホスト名パターン
$possible_hosts = [
    'localhost',
    'mysql.iistylelab.com',
    'iistylelab.com',
    'sv1.iistylelab.com',
    'mysql.xserver.jp',
    'localhost:3306'
];

echo "<h3>接続テスト</h3>";

foreach ($possible_hosts as $host) {
    echo "<h4>ホスト: $host</h4>";
    
    try {
        $dsn = "mysql:host=$host;dbname=iistylelab_road;charset=utf8mb4";
        $pdo = new PDO($dsn, 'iistylelab_road', 'K6RVCwzMDxtz5dn');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color: green;'>✓ 接続成功: $host</p>";
        
        // データベース一覧を取得
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>利用可能なデータベース:</p>";
        echo "<ul>";
        foreach ($databases as $db) {
            echo "<li>" . htmlspecialchars($db) . "</li>";
        }
        echo "</ul>";
        
        break; // 成功したらループを抜ける
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ 接続失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<h3>推奨設定</h3>";
echo "<p>接続に成功したホスト名をconfig.phpのDB_HOSTに設定してください。</p>";
?>
