<?php
echo "<h2>プロジェクトタスク作成状況確認</h2>";

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
    
    // プロジェクト一覧を取得
    echo "<h3>プロジェクト一覧</h3>";
    $stmt = $connection->query("SELECT id, name, status, created_at FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "<h4>プロジェクト: " . htmlspecialchars($project['name']) . " (ID: {$project['id']})</h4>";
        
        // このプロジェクトのタスク数を確認
        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM tasks WHERE project_id = ?");
        $stmt->execute(array($project['id']));
        $taskCount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>タスク数: {$taskCount['count']}</p>";
        
        if ($taskCount['count'] == 0) {
            echo "<p style='color: orange;'>⚠ このプロジェクトにタスクが作成されていません</p>";
            
            // タスクテンプレートからタスクを作成する必要がある
            echo "<p><strong>タスク作成が必要です</strong></p>";
            
            // タスク作成ボタン
            echo "<form method='post' action='create_project_tasks.php'>";
            echo "<input type='hidden' name='project_id' value='{$project['id']}'>";
            echo "<input type='submit' value='タスクを作成' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>";
            echo "</form>";
            
        } else {
            echo "<p style='color: green;'>✓ タスクが作成されています</p>";
            
            // タスクの詳細を表示（最初の5件のみ）
            $stmt = $connection->prepare("
                SELECT t.id, t.phase_name, t.task_name, t.status, t.assigned_to, u.name as assigned_user_name
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                ORDER BY t.phase_name, t.task_order
                LIMIT 5
            ");
            $stmt->execute(array($project['id']));
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr><th>ID</th><th>フェーズ</th><th>タスク名</th><th>ステータス</th><th>担当者</th></tr>";
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['phase_name']}</td>";
                echo "<td>" . htmlspecialchars($task['task_name']) . "</td>";
                echo "<td>{$task['status']}</td>";
                
                $assignedName = '未割り当て';
                if (isset($task['assigned_user_name']) && $task['assigned_user_name']) {
                    $assignedName = $task['assigned_user_name'];
                }
                echo "<td>" . htmlspecialchars($assignedName) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($taskCount['count'] > 5) {
                echo "<p><em>... 他 " . ($taskCount['count'] - 5) . " 件のタスクがあります</em></p>";
            }
        }
        
        echo "<hr>";
    }
    
    // タスクテンプレートの確認
    echo "<h3>タスクテンプレート確認</h3>";
    $stmt = $connection->query("SELECT COUNT(*) as count FROM task_templates");
    $templateCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>テンプレート総数: {$templateCount['count']}</p>";
    
    // フェーズ別のテンプレート数
    $stmt = $connection->query("SELECT phase_name, COUNT(*) as count FROM task_templates GROUP BY phase_name ORDER BY phase_name");
    $phaseCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>フェーズ別テンプレート数</h4>";
    echo "<ul>";
    foreach ($phaseCounts as $phase) {
        echo "<li>{$phase['phase_name']}: {$phase['count']}件</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}
?>
