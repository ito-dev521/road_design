<?php
echo "<h2>データベース接続テスト</h2>";

// 設定ファイル読み込み
require_once 'config.php';

echo "<h3>設定情報</h3>";
echo "<ul>";
echo "<li>ホスト: " . DB_HOST . "</li>";
echo "<li>ポート: " . (defined('DB_PORT') ? DB_PORT : '3306') . "</li>";
echo "<li>データベース: " . DB_NAME . "</li>";
echo "<li>ユーザー: " . DB_USER . "</li>";
echo "<li>文字セット: " . DB_CHARSET . "</li>";
echo "</ul>";

// 接続テスト
try {
    echo "<h3>接続テスト</h3>";
    
    // PDO接続
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    if (defined('DB_PORT')) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }
    
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
    
    // ユーザーテーブルの確認
    if (in_array('users', $tables)) {
        echo "<h3>ユーザーテーブル確認</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>ユーザー数: " . $count['count'] . "</p>";
        
        if ($count['count'] > 0) {
            $stmt = $pdo->query("SELECT id, email, name, role FROM users LIMIT 5");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>ユーザー一覧（最初の5件）</h4>";
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>ID: " . $user['id'] . ", Email: " . htmlspecialchars($user['email']) . ", Name: " . htmlspecialchars($user['name']) . ", Role: " . $user['role'] . "</li>";
            }
            echo "</ul>";
        }
    }
    
    // プロジェクトテーブルの確認
    if (in_array('projects', $tables)) {
        echo "<h3>プロジェクトテーブル確認</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM projects");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>プロジェクト数: " . $count['count'] . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>エラーコード: " . $e->getCode() . "</p>";
    
    // 一般的な解決策を提案
    echo "<h3>考えられる原因と解決策</h3>";
    echo "<ul>";
    echo "<li><strong>データベースが存在しない</strong>: install.phpを実行してください</li>";
    echo "<li><strong>ホスト名が間違っている</strong>: X-Serverの正しいMySQLホスト名を確認してください</li>";
    echo "<li><strong>ユーザー名・パスワードが間違っている</strong>: X-Serverのデータベース設定を確認してください</li>";
    echo "<li><strong>MySQLサービスが停止している</strong>: X-Serverの管理画面で確認してください</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ その他のエラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
