<?php
// add_content_column.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>task_templatesテーブルにcontentカラムを追加</h2>";
    
    // カラムが存在するかチェック
    $stmt = $pdo->prepare("SHOW COLUMNS FROM task_templates LIKE 'content'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✓ contentカラムは既に存在します<br>";
    } else {
        // contentカラムを追加
        $sql = "ALTER TABLE task_templates ADD COLUMN content TEXT AFTER task_name";
        $pdo->exec($sql);
        echo "✓ contentカラムを追加しました<br>";
    }
    
    // テーブル構造を確認
    echo "<h3>現在のテーブル構造:</h3>";
    $stmt = $pdo->query("DESCRIBE task_templates");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
