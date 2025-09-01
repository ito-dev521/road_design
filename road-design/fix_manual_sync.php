<?php
// fix_manual_sync.php
require_once 'config.php';
require_once 'database.php';

echo "<h2>既存マニュアルの同期修正</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "✓ データベース接続成功<br><br>";
    
    // 現在のマニュアル一覧を取得
    $stmt = $pdo->query("SELECT id, task_name, file_name FROM manuals ORDER BY created_at DESC");
    $manuals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($manuals) == 0) {
        echo "マニュアルが登録されていません。<br>";
        echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
        exit;
    }
    
    echo "<h3>修正対象のマニュアル</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>タスク名</th><th>ファイル名</th></tr>";
    foreach ($manuals as $manual) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($manual['id']) . "</td>";
        echo "<td>" . htmlspecialchars($manual['task_name']) . "</td>";
        echo "<td>" . htmlspecialchars($manual['file_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 修正実行
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
        echo "<h3>同期修正を実行中...</h3>";
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($manuals as $manual) {
            $taskName = $manual['task_name'];
            
            // タスクテンプレートを検索
            $stmt = $pdo->prepare("SELECT id, has_manual FROM task_templates WHERE task_name = ?");
            $stmt->execute([$taskName]);
            $template = $stmt->fetch();
            
            if ($template) {
                                 if ($template['has_manual'] != '1') {
                     // マニュアルありをチェック（updated_atカラムがあるかチェック）
                     try {
                         $updateStmt = $pdo->prepare("UPDATE task_templates SET has_manual = 1, updated_at = NOW() WHERE id = ?");
                         $result = $updateStmt->execute([$template['id']]);
                     } catch (Exception $e) {
                         // updated_atカラムがない場合は、has_manualのみ更新
                         $updateStmt = $pdo->prepare("UPDATE task_templates SET has_manual = 1 WHERE id = ?");
                         $result = $updateStmt->execute([$template['id']]);
                     }
                    
                    if ($result) {
                        echo "✓ {$taskName} を修正しました<br>";
                        $successCount++;
                    } else {
                        echo "✗ {$taskName} の修正に失敗しました<br>";
                        $errorCount++;
                    }
                } else {
                    echo "- {$taskName} は既に正しく設定されています<br>";
                }
            } else {
                echo "✗ {$taskName} のタスクテンプレートが見つかりません<br>";
                $errorCount++;
            }
        }
        
        echo "<br><h3>修正結果</h3>";
        echo "成功: {$successCount}件<br>";
        echo "エラー: {$errorCount}件<br>";
        
        if ($successCount > 0) {
            echo "<br><div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;'>";
            echo "✓ 同期修正が完了しました。設定画面で確認してください。";
            echo "</div>";
        }
        
        echo "<br><a href='settings.html'>設定画面に戻る</a>";
        
    } else {
        echo "<h3>修正実行</h3>";
        echo "<p>上記のマニュアルに対応するタスクテンプレートの「マニュアルあり」をチェックします。</p>";
        echo "<form method='POST'>";
        echo "<input type='submit' name='fix' value='同期修正を実行' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "<br>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
}
?>
