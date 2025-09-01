<?php
// test_manual_sync.php
require_once 'config.php';
require_once 'database.php';

echo "<h2>マニュアルとタスクテンプレートの同期状況確認</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "✓ データベース接続成功<br><br>";
    
    // 1. 現在のマニュアル一覧
    echo "<h3>1. 現在のマニュアル一覧</h3>";
    $stmt = $pdo->query("SELECT id, task_name, file_name, created_at FROM manuals ORDER BY created_at DESC");
    $manuals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($manuals) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>タスク名</th><th>ファイル名</th><th>作成日</th></tr>";
        foreach ($manuals as $manual) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($manual['id']) . "</td>";
            echo "<td>" . htmlspecialchars($manual['task_name']) . "</td>";
            echo "<td>" . htmlspecialchars($manual['file_name']) . "</td>";
            echo "<td>" . htmlspecialchars($manual['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "マニュアルが登録されていません。<br>";
    }
    
    // 2. 対応するタスクテンプレートの状況
    echo "<h3>2. 対応するタスクテンプレートの状況</h3>";
    if (count($manuals) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>タスク名</th><th>フェーズ</th><th>マニュアルあり</th><th>状態</th></tr>";
        
        foreach ($manuals as $manual) {
            $taskName = $manual['task_name'];
            
            // タスクテンプレートを検索
            $stmt = $pdo->prepare("SELECT phase_name, task_name, has_manual FROM task_templates WHERE task_name = ?");
            $stmt->execute([$taskName]);
            $template = $stmt->fetch();
            
            if ($template) {
                $status = $template['has_manual'] == '1' ? '✓ チェック済み' : '✗ 未チェック';
                $statusClass = $template['has_manual'] == '1' ? 'success' : 'error';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($taskName) . "</td>";
                echo "<td>" . htmlspecialchars($template['phase_name']) . "</td>";
                echo "<td>" . ($template['has_manual'] == '1' ? 'はい' : 'いいえ') . "</td>";
                echo "<td style='color: " . ($statusClass === 'success' ? 'green' : 'red') . ";'>" . $status . "</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($taskName) . "</td>";
                echo "<td colspan='2'>タスクテンプレートが見つかりません</td>";
                echo "<td style='color: orange;'>✗ テンプレートなし</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
    
    // 3. 手動同期テスト
    echo "<h3>3. 手動同期テスト</h3>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
        echo "手動同期を実行中...<br>";
        
        $syncCount = 0;
        foreach ($manuals as $manual) {
            $taskName = $manual['task_name'];
            
            // タスクテンプレートを更新
            $stmt = $pdo->prepare("UPDATE task_templates SET has_manual = 1, updated_at = NOW() WHERE task_name = ?");
            $result = $stmt->execute([$taskName]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo "✓ {$taskName} を同期しました<br>";
                $syncCount++;
            } else {
                echo "✗ {$taskName} の同期に失敗しました<br>";
            }
        }
        
        echo "<br>同期完了: {$syncCount}件<br>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    } else {
        echo "<form method='POST'>";
        echo "<input type='submit' name='sync' value='手動同期を実行' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
        echo "</form>";
    }
    
    // 4. デバッグ情報
    echo "<h3>4. デバッグ情報</h3>";
    echo "<p>マニュアル数: " . count($manuals) . "</p>";
    
    if (count($manuals) > 0) {
        $taskNames = array_column($manuals, 'task_name');
        echo "<p>マニュアルのタスク名: " . implode(', ', $taskNames) . "</p>";
        
        // タスクテンプレートの統計
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN has_manual = 1 THEN 1 ELSE 0 END) as has_manual_count FROM task_templates");
        $stats = $stmt->fetch();
        echo "<p>タスクテンプレート総数: " . $stats['total'] . "</p>";
        echo "<p>マニュアルありのテンプレート数: " . $stats['has_manual_count'] . "</p>";
    }
    
    echo "<h3>完了</h3>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "<br>";
    echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
}
?>
