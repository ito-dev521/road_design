<?php
// add_task_name_column.php
require_once 'config.php';
require_once 'database.php';

echo "<h2>manualsテーブルにtask_nameカラムを追加</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "✓ データベース接続成功<br>";
    
    // 現在のテーブル構造を確認
    echo "<h3>1. 現在のテーブル構造</h3>";
    $stmt = $pdo->query("DESCRIBE manuals");
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
    
    // task_nameカラムが存在するかチェック
    $taskNameExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'task_name') {
            $taskNameExists = true;
            break;
        }
    }
    
    if ($taskNameExists) {
        echo "✓ task_nameカラムは既に存在します<br>";
    } else {
        echo "<h3>2. task_nameカラムを追加</h3>";
        
        // task_nameカラムを追加
        $sql = "ALTER TABLE manuals ADD COLUMN task_name VARCHAR(255) NOT NULL DEFAULT '' AFTER id";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute()) {
            echo "✓ task_nameカラムを追加しました<br>";
            
            // 更新後のテーブル構造を確認
            echo "<h3>3. 更新後のテーブル構造</h3>";
            $stmt = $pdo->query("DESCRIBE manuals");
            $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th></tr>";
            foreach ($updatedColumns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "✗ task_nameカラムの追加に失敗しました<br>";
        }
    }
    
    // original_nameカラムも存在するかチェック
    $originalNameExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'original_name') {
            $originalNameExists = true;
            break;
        }
    }
    
    if (!$originalNameExists) {
        echo "<h3>4. original_nameカラムを追加</h3>";
        
        // original_nameカラムを追加
        $sql = "ALTER TABLE manuals ADD COLUMN original_name VARCHAR(255) NOT NULL DEFAULT '' AFTER file_name";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute()) {
            echo "✓ original_nameカラムを追加しました<br>";
        } else {
            echo "✗ original_nameカラムの追加に失敗しました<br>";
        }
    } else {
        echo "✓ original_nameカラムは既に存在します<br>";
    }
    
    echo "<h3>完了</h3>";
    echo "<p>manualsテーブルの構造修正が完了しました。</p>";
    echo "<p><a href='test_manual_upload.php'>マニュアルアップロードテストを実行</a></p>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "<br>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
}
?>
