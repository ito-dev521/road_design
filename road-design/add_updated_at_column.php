<?php
// add_updated_at_column.php
require_once 'config.php';
require_once 'database.php';

echo "<h2>task_templatesテーブルにupdated_atカラムを追加</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "✓ データベース接続成功<br>";
    
    // 現在のテーブル構造を確認
    echo "<h3>1. 現在のテーブル構造</h3>";
    $stmt = $pdo->query("DESCRIBE task_templates");
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
    
    // updated_atカラムが存在するかチェック
    $updatedAtExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'updated_at') {
            $updatedAtExists = true;
            break;
        }
    }
    
    if ($updatedAtExists) {
        echo "✓ updated_atカラムは既に存在します<br>";
    } else {
        echo "<h3>2. updated_atカラムを追加</h3>";
        
        // updated_atカラムを追加
        $sql = "ALTER TABLE task_templates ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute()) {
            echo "✓ updated_atカラムを追加しました<br>";
            
            // 更新後のテーブル構造を確認
            echo "<h3>3. 更新後のテーブル構造</h3>";
            $stmt = $pdo->query("DESCRIBE task_templates");
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
            echo "✗ updated_atカラムの追加に失敗しました<br>";
        }
    }
    
    echo "<h3>完了</h3>";
    echo "<p>task_templatesテーブルの構造修正が完了しました。</p>";
    echo "<p><a href='fix_manual_sync.php'>既存マニュアルの同期修正を実行</a></p>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "<br>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
}
?>
