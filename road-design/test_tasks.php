<?php
echo "<h2>タスクデータ取得テスト</h2>";

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
    
    if (empty($projects)) {
        echo "<p style='color: orange;'>⚠ プロジェクトが存在しません</p>";
    } else {
        echo "<ul>";
        foreach ($projects as $project) {
            echo "<li>ID: {$project['id']}, 名前: " . htmlspecialchars($project['name']) . ", ステータス: {$project['status']}</li>";
        }
        echo "</ul>";
    }
    
    // タスクテンプレート一覧を取得
    echo "<h3>タスクテンプレート一覧</h3>";
    $stmt = $connection->query("SELECT id, phase_name, task_name, task_order FROM task_templates ORDER BY phase_name, task_order");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($templates)) {
        echo "<p style='color: orange;'>⚠ タスクテンプレートが存在しません</p>";
    } else {
        echo "<p>テンプレート数: " . count($templates) . "</p>";
        echo "<ul>";
        foreach ($templates as $template) {
            echo "<li>フェーズ: {$template['phase_name']}, タスク: " . htmlspecialchars($template['task_name']) . "</li>";
        }
        echo "</ul>";
    }
    
    // プロジェクトのタスク一覧を取得
    if (!empty($projects)) {
        $projectId = $projects[0]['id'];
        echo "<h3>プロジェクトID {$projectId} のタスク一覧</h3>";
        
        $stmt = $connection->prepare("
            SELECT t.id, t.phase_name, t.task_name, t.status, t.assigned_to, t.planned_date, u.name as assigned_user_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = ?
            ORDER BY t.phase_name, t.task_order
        ");
        $stmt->execute([$projectId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tasks)) {
            echo "<p style='color: orange;'>⚠ このプロジェクトにタスクが存在しません</p>";
            
            // タスクが存在しない場合、テンプレートからタスクを作成する必要があるかチェック
            echo "<h4>タスク作成の確認</h4>";
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM tasks WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $taskCount = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>プロジェクト {$projectId} のタスク数: {$taskCount['count']}</p>";
            
        } else {
            echo "<p>タスク数: " . count($tasks) . "</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>フェーズ</th><th>タスク名</th><th>ステータス</th><th>担当者</th><th>予定日</th></tr>";
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['id']}</td>";
                echo "<td>{$task['phase_name']}</td>";
                echo "<td>" . htmlspecialchars($task['task_name']) . "</td>";
                echo "<td>{$task['status']}</td>";
                echo "<td>" . htmlspecialchars(isset($task['assigned_user_name']) ? $task['assigned_user_name'] : '未割り当て') . "</td>";
                echo "<td>" . (isset($task['planned_date']) ? $task['planned_date'] : '未設定') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // APIエンドポイントのテスト
    echo "<h3>APIエンドポイントテスト</h3>";
    echo "<p>以下のURLでAPIテストを実行してください：</p>";
    echo "<ul>";
    echo "<li><a href='api.php?path=projects' target='_blank'>プロジェクト一覧API</a></li>";
    if (!empty($projects)) {
        echo "<li><a href='api.php?path=projects/{$projectId}' target='_blank'>プロジェクト詳細API</a></li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}
?>
