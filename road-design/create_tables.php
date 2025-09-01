<?php
// データベーステーブル作成スクリプト
require_once 'config.php';

echo "<h1>道路詳細設計管理システム - データベーステーブル作成</h1>";

try {
    // データベース接続
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ データベースサーバーに接続しました。</p>";
    
    // データベース作成（存在しない場合）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ データベース '" . DB_NAME . "' を確認/作成しました。</p>";
    
    // データベース選択
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // SQLファイル読み込み
    $sqlFile = 'database_schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQLファイル '{$sqlFile}' が見つかりません。");
    }
    
    $sql = file_get_contents($sqlFile);
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h2>テーブル作成開始...</h2>";
    
    $createdTables = [];
    $insertedRecords = [];
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^\s*--/', $query)) {
            try {
                $pdo->exec($query);
                
                // CREATE TABLE文をチェック
                if (preg_match('/CREATE\s+TABLE\s+(\w+)/i', $query, $matches)) {
                    $tableName = $matches[1];
                    $createdTables[] = $tableName;
                    echo "<p style='color: blue;'>📋 テーブル '{$tableName}' を作成しました。</p>";
                }
                
                // INSERT文をチェック
                if (preg_match('/INSERT\s+INTO\s+(\w+)/i', $query, $matches)) {
                    $tableName = $matches[1];
                    if (!in_array($tableName, $insertedRecords)) {
                        $insertedRecords[] = $tableName;
                        echo "<p style='color: orange;'>📝 テーブル '{$tableName}' に初期データを挿入しました。</p>";
                    }
                }
                
            } catch (PDOException $e) {
                // テーブルが既に存在する場合はスキップ
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    if (preg_match('/CREATE\s+TABLE\s+(\w+)/i', $query, $matches)) {
                        echo "<p style='color: gray;'>⚠️ テーブル '{$matches[1]}' は既に存在します。</p>";
                    }
                } else {
                    throw $e;
                }
            }
        }
    }
    
    echo "<h2>データベース構造確認</h2>";
    
    // 作成されたテーブル一覧を表示
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>テーブル名</th><th>レコード数</th><th>ステータス</th></tr>";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $count = $stmt->fetchColumn();
        
        $status = in_array($table, $createdTables) ? '新規作成' : '既存';
        $statusColor = $status === '新規作成' ? 'green' : 'blue';
        
        echo "<tr>";
        echo "<td><strong>{$table}</strong></td>";
        echo "<td>{$count}</td>";
        echo "<td style='color: {$statusColor};'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // パスワードハッシュを更新（初期パスワードを正しくハッシュ化）
    echo "<h2>初期ユーザーパスワード設定</h2>";
    
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $techHash = password_hash('tech123', PASSWORD_DEFAULT);
    $staffHash = password_hash('staff123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$adminHash, 'admin@ii-stylelab.com']);
    $stmt->execute([$techHash, 'tech@ii-stylelab.com']);
    $stmt->execute([$staffHash, 'staff@ii-stylelab.com']);
    
    echo "<p style='color: green;'>✅ 初期ユーザーのパスワードを設定しました。</p>";
    
    echo "<h2>🎉 セットアップ完了！</h2>";
    echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>初期ログイン情報:</h3>";
    echo "<ul>";
    echo "<li><strong>管理者:</strong> admin@ii-stylelab.com / admin123</li>";
    echo "<li><strong>技術者:</strong> tech@ii-stylelab.com / tech123</li>";
    echo "<li><strong>一般スタッフ:</strong> staff@ii-stylelab.com / staff123</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='login.html' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>ログイン画面へ</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "❌ エラー: " . htmlspecialchars($e->getMessage());
    echo "</p>";
    
    // エラー詳細（デバッグ用）
    echo "<details>";
    echo "<summary>エラー詳細</summary>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</details>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

table {
    width: 100%;
    margin: 20px 0;
}

th, td {
    text-align: left;
    padding: 8px;
}

details {
    margin-top: 20px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

summary {
    cursor: pointer;
    font-weight: bold;
    color: #007bff;
}
</style>