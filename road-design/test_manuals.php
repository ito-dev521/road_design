<?php
// マニュアルテーブルテスト
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Content-Type: text/plain\n\n";
echo "=== マニュアルテーブルテスト ===\n\n";

try {
    require_once 'config.php';
    require_once 'database.php';
    
    $db = new Database();
    $connection = $db->getConnection();
    
    if (!$connection) {
        echo "✗ データベース接続失敗\n";
        exit;
    }
    
    echo "✓ データベース接続成功\n\n";
    
    // テーブル存在確認
    echo "=== テーブル存在確認 ===\n";
    $stmt = $connection->prepare("SHOW TABLES LIKE 'manuals'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✓ manualsテーブルが存在します\n";
        
        // テーブル構造確認
        echo "\n=== テーブル構造 ===\n";
        $stmt = $connection->prepare("DESCRIBE manuals");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
        
        // レコード数確認
        echo "\n=== レコード数 ===\n";
        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM manuals");
        $stmt->execute();
        $count = $stmt->fetch();
        echo "レコード数: {$count['count']}件\n";
        
        // getAllManualsメソッドテスト
        echo "\n=== getAllManualsメソッドテスト ===\n";
        $manuals = $db->getAllManuals();
        if ($manuals === false) {
            echo "✗ getAllManuals() エラー\n";
            
            // 直接SQLを実行してテスト
            echo "\n=== 直接SQL実行テスト ===\n";
            try {
                $stmt = $connection->prepare("
                    SELECT id, file_name, description, file_size, file_path, created_at
                    FROM manuals
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
                $directResult = $stmt->fetchAll();
                echo "✓ 直接SQL実行成功: " . count($directResult) . "件\n";
                
                if (count($directResult) > 0) {
                    echo "サンプルデータ:\n";
                    print_r($directResult[0]);
                }
            } catch (Exception $e) {
                echo "✗ 直接SQL実行エラー: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ getAllManuals() 成功: " . count($manuals) . "件\n";
            if (count($manuals) > 0) {
                echo "サンプルデータ:\n";
                print_r($manuals[0]);
            }
        }
        
    } else {
        echo "✗ manualsテーブルが存在しません\n";
        echo "\n=== テーブル作成 ===\n";
        
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS manuals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            description TEXT,
            file_size INT,
            file_path VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $connection->exec($createTableSQL);
            echo "✓ manualsテーブルを作成しました\n";
        } catch (Exception $e) {
            echo "✗ テーブル作成エラー: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行: " . $e->getLine() . "\n";
}

echo "\nテスト完了\n";
?>
