<?php
// データベース更新スクリプト
// タスクステータスに「要確認」を追加

require_once 'config.php';
require_once 'database.php';

try {
    echo "データベース更新を開始します...\n";
    
    // データベース接続
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "データベース接続成功\n";
    
    // 現在のテーブル構造を確認
    echo "\n現在のテーブル構造を確認中...\n";
    $stmt = $pdo->query("DESCRIBE tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "現在のstatusカラム: " . $column['Type'] . "\n";
            break;
        }
    }
    
    // 現在のstatus値を確認
    echo "\n現在のstatus値を確認中...\n";
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM tasks GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($statuses as $status) {
        echo "Status: {$status['status']}, Count: {$status['count']}\n";
    }
    
    // ENUMを更新
    echo "\nENUMを更新中...\n";
    $sql = "ALTER TABLE tasks 
            MODIFY COLUMN status ENUM('not_started', 'in_progress', 'completed', 'not_applicable', 'needs_confirmation', 'pending') 
            DEFAULT 'not_started' 
            COMMENT 'タスクステータス: not_started=未着手, in_progress=進行中, completed=完了, not_applicable=対象外, needs_confirmation=要確認, pending=保留中'";
    
    $pdo->exec($sql);
    echo "ENUM更新完了\n";
    
    // 更新後の確認
    echo "\n更新後の確認...\n";
    $stmt = $pdo->query("SELECT DISTINCT status, COUNT(*) as count FROM tasks GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($statuses as $status) {
        echo "Status: {$status['status']}, Count: {$status['count']}\n";
    }
    
    // 最終的なテーブル構造確認
    echo "\n最終的なテーブル構造:\n";
    $stmt = $pdo->query("DESCRIBE tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "Status column: " . $column['Type'] . "\n";
            break;
        }
    }
    
    echo "\nデータベース更新が正常に完了しました！\n";
    
} catch (Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "\n";
    echo "エラーコード: " . $e->getCode() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
?>
