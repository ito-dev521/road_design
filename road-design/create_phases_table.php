<?php
echo "<h2>フェーズテーブル作成スクリプト</h2>";

// 設定ファイル読み込み
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    if (!$connection) {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ データベース接続成功</p>";
    
    // phasesテーブルの存在確認
    echo "<h3>phasesテーブル確認</h3>";
    $stmt = $connection->query("SHOW TABLES LIKE 'phases'");
    $phaseTable = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($phaseTable)) {
        echo "<p style='color: orange;'>⚠ phasesテーブルが存在しません。作成します...</p>";
        
        // phasesテーブルを作成
        $createTableSQL = "
        CREATE TABLE phases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            order_num INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order (order_num),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $connection->exec($createTableSQL);
        echo "<p style='color: green;'>✓ phasesテーブルを作成しました</p>";
        
        // デフォルトフェーズデータを挿入
        $defaultPhases = [
            ['name' => 'フェーズ1', 'description' => '基本設計・調査', 'order_num' => 1],
            ['name' => 'フェーズ2', 'description' => '詳細設計', 'order_num' => 2],
            ['name' => 'フェーズ3', 'description' => '施工・監理', 'order_num' => 3]
        ];
        
        $stmt = $connection->prepare("INSERT INTO phases (name, description, order_num) VALUES (?, ?, ?)");
        foreach ($defaultPhases as $phase) {
            $stmt->execute(array($phase['name'], $phase['description'], $phase['order_num']));
        }
        echo "<p style='color: green;'>✓ デフォルトフェーズデータを挿入しました</p>";
        
    } else {
        echo "<p style='color: green;'>✓ phasesテーブルは既に存在します</p>";
        
        // 既存データの確認
        $stmt = $connection->query("SELECT COUNT(*) as count FROM phases");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count == 0) {
            echo "<p style='color: orange;'>⚠ phasesテーブルにデータがありません。デフォルトデータを挿入します...</p>";
            
            $defaultPhases = [
                ['name' => 'フェーズ1', 'description' => '基本設計・調査', 'order_num' => 1],
                ['name' => 'フェーズ2', 'description' => '詳細設計', 'order_num' => 2],
                ['name' => 'フェーズ3', 'description' => '施工・監理', 'order_num' => 3]
            ];
            
            $stmt = $connection->prepare("INSERT INTO phases (name, description, order_num) VALUES (?, ?, ?)");
            foreach ($defaultPhases as $phase) {
                $stmt->execute(array($phase['name'], $phase['description'], $phase['order_num']));
            }
            echo "<p style='color: green;'>✓ デフォルトフェーズデータを挿入しました</p>";
        } else {
            echo "<p style='color: green;'>✓ phasesテーブルに {$count} 件のデータが存在します</p>";
        }
    }
    
    // 最終確認
    echo "<h3>最終確認</h3>";
    $stmt = $connection->query("SELECT id, name, description, order_num, is_active FROM phases ORDER BY order_num");
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>名前</th><th>説明</th><th>順序</th><th>有効</th></tr>";
    foreach ($phases as $phase) {
        echo "<tr>";
        echo "<td>{$phase['id']}</td>";
        echo "<td>" . htmlspecialchars($phase['name']) . "</td>";
        echo "<td>" . htmlspecialchars($phase['description']) . "</td>";
        echo "<td>{$phase['order_num']}</td>";
        echo "<td>" . ($phase['is_active'] ? '有効' : '無効') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 フェーズテーブルの設定が完了しました！</p>";
    echo "<p>これで設定画面のフェーズ管理が正常に動作するはずです。</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}
?>
