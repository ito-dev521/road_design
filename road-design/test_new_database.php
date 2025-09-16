<?php
/**
 * 新しいデータベース接続テストスクリプト
 */

// 新しいデータベース設定
$newDbConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'roadplan_road',
    'username' => 'roadplan_road',
    'password' => '8R23ENvs'
];

echo "新しいデータベース接続をテストします...\n";
echo "ホスト: " . $newDbConfig['host'] . "\n";
echo "データベース: " . $newDbConfig['database'] . "\n";
echo "ユーザー: " . $newDbConfig['username'] . "\n";
echo "ポート: " . $newDbConfig['port'] . "\n\n";

try {
    // データベース接続テスト
    $dsn = "mysql:host={$newDbConfig['host']};port={$newDbConfig['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $newDbConfig['username'], $newDbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ データベース接続成功！\n\n";
    
    // データベースの存在確認
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "現在接続中のデータベース: " . $result['current_db'] . "\n\n";
    
    // テーブル一覧を取得
    echo "テーブル一覧:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️ テーブルが見つかりません。データベースが空の可能性があります。\n";
        echo "データの復元が必要です。\n";
    } else {
        echo "✅ " . count($tables) . "個のテーブルが見つかりました:\n";
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "  - $table: $count レコード\n";
        }
        
        // 主要テーブルの詳細確認
        echo "\n主要テーブルの詳細:\n";
        $importantTables = ['users', 'projects', 'tasks', 'templates'];
        
        foreach ($importantTables as $table) {
            if (in_array($table, $tables)) {
                echo "\n=== $table テーブル ===\n";
                
                // テーブル構造を表示
                $stmt = $pdo->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll();
                echo "カラム一覧:\n";
                foreach ($columns as $column) {
                    echo "  - {$column['Field']} ({$column['Type']}) " . 
                         ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                         ($column['Key'] ? " [{$column['Key']}]" : '') . "\n";
                }
                
                // サンプルデータを表示（最初の3件）
                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 3");
                $samples = $stmt->fetchAll();
                if (!empty($samples)) {
                    echo "サンプルデータ (最初の3件):\n";
                    foreach ($samples as $i => $sample) {
                        echo "  " . ($i + 1) . ": " . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
                    }
                } else {
                    echo "データがありません\n";
                }
            } else {
                echo "⚠️ $table テーブルが見つかりません\n";
            }
        }
    }
    
    // 文字セットの確認
    echo "\n=== データベース設定 ===\n";
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set%'");
    $charsets = $stmt->fetchAll();
    foreach ($charsets as $charset) {
        echo "{$charset['Variable_name']}: {$charset['Value']}\n";
    }
    
    echo "\n✅ データベーステスト完了！\n";
    
} catch (PDOException $e) {
    echo "❌ データベース接続エラー: " . $e->getMessage() . "\n";
    echo "\n考えられる原因:\n";
    echo "1. データベースサーバーが起動していない\n";
    echo "2. ホスト名またはポート番号が間違っている\n";
    echo "3. ユーザー名またはパスワードが間違っている\n";
    echo "4. データベースが存在しない\n";
    echo "5. ユーザーにデータベースへのアクセス権限がない\n";
    exit(1);
}
?>
