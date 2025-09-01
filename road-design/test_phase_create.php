<?php
// フェーズ作成テスト
ini_set('display_errors', 1);
error_reporting(E_ALL);

// セッション開始
if (!session_id()) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'manager';

echo "Content-Type: text/plain\n\n";
echo "=== フェーズ作成テスト ===\n\n";

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
    
    // phasesテーブル存在確認
    echo "=== phasesテーブル存在確認 ===\n";
    $stmt = $connection->prepare("SHOW TABLES LIKE 'phases'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✓ phasesテーブルが存在します\n";
        
        // テーブル構造確認
        echo "\n=== テーブル構造 ===\n";
        $stmt = $connection->prepare("DESCRIBE phases");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
        
        // createPhaseメソッドテスト
        echo "\n=== createPhaseメソッドテスト ===\n";
        $testName = 'テストフェーズ' . date('His');
        $testDescription = 'テスト用のフェーズです';
        $testOrderNum = 99;
        
        echo "テストデータ:\n";
        echo "- 名前: {$testName}\n";
        echo "- 説明: {$testDescription}\n";
        echo "- 順序: {$testOrderNum}\n\n";
        
        $result = $db->createPhase($testName, $testDescription, $testOrderNum);
        if ($result) {
            echo "✓ createPhase() 成功\n";
            
            // 作成されたデータを確認
            $stmt = $connection->prepare("SELECT * FROM phases WHERE name = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$testName]);
            $createdPhase = $stmt->fetch();
            
            if ($createdPhase) {
                echo "作成されたフェーズ:\n";
                print_r($createdPhase);
                
                // テストデータを削除
                $stmt = $connection->prepare("DELETE FROM phases WHERE id = ?");
                $stmt->execute([$createdPhase['id']]);
                echo "\n✓ テストデータを削除しました\n";
            }
        } else {
            echo "✗ createPhase() エラー\n";
            
            // 直接SQLでテスト
            echo "\n=== 直接SQL挿入テスト ===\n";
            try {
                $stmt = $connection->prepare("
                    INSERT INTO phases (name, description, order_num, is_active)
                    VALUES (?, ?, ?, 1)
                ");
                $directResult = $stmt->execute([$testName, $testDescription, $testOrderNum]);
                
                if ($directResult) {
                    echo "✓ 直接SQL挿入成功\n";
                    $insertId = $connection->lastInsertId();
                    echo "挿入ID: {$insertId}\n";
                    
                    // テストデータを削除
                    $stmt = $connection->prepare("DELETE FROM phases WHERE id = ?");
                    $stmt->execute([$insertId]);
                    echo "✓ テストデータを削除しました\n";
                } else {
                    echo "✗ 直接SQL挿入失敗\n";
                }
            } catch (Exception $e) {
                echo "✗ 直接SQL挿入エラー: " . $e->getMessage() . "\n";
            }
        }
        
        // API経由でのテスト
        echo "\n=== API経由テスト ===\n";
        
        // $_POST をシミュレート
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['path'] = 'phases';
        
        $testData = [
            'name' => 'APIテストフェーズ' . date('His'),
            'description' => 'API経由のテストフェーズ',
            'order_num' => 98,
            'is_active' => '1'
        ];
        
        // JSONデータをシミュレート
        $jsonData = json_encode($testData);
        file_put_contents('php://input', $jsonData);
        
        ob_start();
        try {
            include 'api.php';
            $apiOutput = ob_get_clean();
            echo "API出力:\n{$apiOutput}\n";
        } catch (Exception $e) {
            ob_clean();
            echo "✗ API エラー: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗ phasesテーブルが存在しません\n";
    }
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行: " . $e->getLine() . "\n";
}

echo "\nテスト完了\n";
?>
