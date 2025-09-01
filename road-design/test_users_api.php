<?php
echo "<h2>ユーザー管理APIテスト</h2>";
require_once 'config.php';
require_once 'database.php';
require_once 'api.php';

try {
    echo "<h3>1. データベース接続テスト</h3>";
    $db = new Database();
    $connection = $db->getConnection();
    if (!$connection) { 
        echo "<p style='color: red;'>✗ データベース接続失敗</p>"; 
        exit; 
    }
    echo "<p style='color: green;'>✓ データベース接続成功</p>";

    echo "<h3>2. ユーザーテーブル確認</h3>";
    $stmt = $connection->query("SHOW TABLES LIKE 'users'");
    $userTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($userTable)) {
        echo "<p style='color: red;'>✗ usersテーブルが存在しません</p>";
        exit;
    }
    echo "<p style='color: green;'>✓ usersテーブルが存在します</p>";

    echo "<h3>3. ユーザーデータ確認</h3>";
    $stmt = $connection->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>ユーザー数: {$count}件</p>";

    if ($count > 0) {
        $stmt = $connection->query("SELECT id, name, email, role, is_active FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>名前</th><th>メール</th><th>役割</th><th>有効</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['is_active'] ? '有効' : '無効') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h3>4. データベースクラスのgetAllUsers()テスト</h3>";
    $users = $db->getAllUsers();
    if ($users === false) {
        echo "<p style='color: red;'>✗ getAllUsers()が失敗しました</p>";
    } else {
        echo "<p style='color: green;'>✓ getAllUsers()が成功しました</p>";
        echo "<p>取得したユーザー数: " . count($users) . "件</p>";
        if (count($users) > 0) {
            echo "<pre>" . json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
    }

    echo "<h3>5. APIコントローラーのgetUsers()テスト</h3>";
    $api = new ApiController();
    $_GET['path'] = 'users';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // セッション開始（認証が必要なため）
    session_start();
    
    // テスト用にログイン状態をシミュレート（実際の環境では適切な認証が必要）
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'manager';
    
    $result = $api->handleRequest();
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}
?>
