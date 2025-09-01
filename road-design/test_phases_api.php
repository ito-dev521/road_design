<?php
echo "<h2>フェーズ管理APIテスト</h2>";

// 設定ファイル読み込み
require_once 'config.php';
require_once 'database.php';
require_once 'api.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    if (!$connection) {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ データベース接続成功</p>";
    
    // フェーズテーブルの確認
    echo "<h3>フェーズテーブル確認</h3>";
    $stmt = $connection->query("SHOW TABLES LIKE 'phases'");
    $phaseTable = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($phaseTable)) {
        echo "<p style='color: orange;'>⚠ phasesテーブルが存在しません</p>";
        
        // フェーズテーブルを作成
        echo "<h4>フェーズテーブルを作成します</h4>";
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
        
        // デフォルトフェーズを挿入
        $defaultPhases = [
            ['name' => 'フェーズ1', 'description' => '基本設計・調査', 'order_num' => 1],
            ['name' => 'フェーズ2', 'description' => '詳細設計', 'order_num' => 2],
            ['name' => 'フェーズ3', 'description' => '施工・監理', 'order_num' => 3]
        ];
        
        $stmt = $connection->prepare("INSERT INTO phases (name, description, order_num) VALUES (?, ?, ?)");
        foreach ($defaultPhases as $phase) {
            $stmt->execute(array($phase['name'], $phase['description'], $phase['order_num']));
        }
        echo "<p style='color: green;'>✓ デフォルトフェーズを挿入しました</p>";
        
    } else {
        echo "<p style='color: green;'>✓ phasesテーブルが存在します</p>";
    }
    
    // フェーズデータの確認
    echo "<h3>フェーズデータ確認</h3>";
    $stmt = $connection->query("SELECT id, name, description, order_num, is_active FROM phases ORDER BY order_num");
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($phases)) {
        echo "<p style='color: orange;'>⚠ フェーズデータが存在しません</p>";
    } else {
        echo "<p>フェーズ数: " . count($phases) . "</p>";
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
    }
    
    // タスクテンプレートのフェーズ確認
    echo "<h3>タスクテンプレートのフェーズ確認</h3>";
    $stmt = $connection->query("SELECT DISTINCT phase_name FROM task_templates ORDER BY phase_name");
    $templatePhases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>タスクテンプレートで使用されているフェーズ:</p>";
    echo "<ul>";
    foreach ($templatePhases as $phase) {
        echo "<li>" . htmlspecialchars($phase) . "</li>";
    }
    echo "</ul>";
    
    // APIコントローラーをテスト
    echo "<h3>フェーズ管理APIテスト</h3>";
    $api = new ApiController();
    
    // フェーズ一覧APIテスト
    echo "<h4>フェーズ一覧APIテスト</h4>";
    $_GET['path'] = 'phases';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    try {
        $result = $api->handleRequest();
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ APIエラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 直接フェーズデータを取得
    echo "<h4>直接フェーズデータ取得</h4>";
    $stmt = $connection->query("SELECT * FROM phases WHERE is_active = 1 ORDER BY order_num");
    $activePhases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>" . json_encode($activePhases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}
?>
