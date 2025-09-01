<?php
// debug_projects.php - プロジェクト読み込みエラーのデバッグ
echo "<h2>プロジェクト読み込みデバッグ</h2>";

// 設定ファイル読み込み
require_once 'config.php';
require_once 'database.php';

try {
    echo "<h3>1. データベース接続テスト</h3>";
    $db = new Database();
    $connection = $db->getConnection();
    
    if (!$connection) {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ データベース接続成功</p>";
    
    echo "<h3>2. projectsテーブル構造確認</h3>";
    $stmt = $connection->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. プロジェクトデータ確認</h3>";
    $stmt = $connection->query("SELECT COUNT(*) as count FROM projects");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>プロジェクト数: " . $count['count'] . "件</p>";
    
    if ($count['count'] > 0) {
        echo "<h4>プロジェクト一覧（最新5件）</h4>";
        $stmt = $connection->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>名前</th><th>クライアント</th><th>ステータス</th><th>作成日</th></tr>";
        foreach ($projects as $project) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($project['id']) . "</td>";
            echo "<td>" . htmlspecialchars($project['name']) . "</td>";
            echo "<td>" . htmlspecialchars($project['client_name'] ?? '未設定') . "</td>";
            echo "<td>" . htmlspecialchars($project['status'] ?? '未設定') . "</td>";
            echo "<td>" . htmlspecialchars($project['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>4. APIテスト</h3>";
    echo "<p>以下のURLでAPIをテストしてください：</p>";
    echo "<ul>";
    echo "<li><a href='api.php?path=projects' target='_blank'>プロジェクト一覧API</a></li>";
    echo "<li><a href='api.php?path=user/profile' target='_blank'>ユーザー情報API</a></li>";
    echo "</ul>";
    
    echo "<h3>5. エラーログ確認</h3>";
    $errorLog = error_get_last();
    if ($errorLog) {
        echo "<p style='color: red;'>最新のエラー:</p>";
        echo "<pre>" . htmlspecialchars(print_r($errorLog, true)) . "</pre>";
    } else {
        echo "<p style='color: green;'>エラーログなし</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>完了</h3>";
echo "<p><a href='index.html'>index.htmlに戻る</a></p>";
?>
