<?php
echo "<h2>APIエンドポイントテスト</h2>";

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
    
    // APIコントローラーをテスト
    $api = new ApiController();
    
    // プロジェクト一覧APIテスト
    echo "<h3>プロジェクト一覧APIテスト</h3>";
    $_GET['path'] = 'projects';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $result = $api->handleRequest();
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    // プロジェクト詳細APIテスト（ID: 3）
    echo "<h3>プロジェクト詳細APIテスト (ID: 3)</h3>";
    $_GET['path'] = 'projects/3';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $result = $api->handleRequest();
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    // 直接データベースからタスクを取得して比較
    echo "<h3>直接データベース取得テスト</h3>";
    $stmt = $connection->prepare("
        SELECT t.id, t.phase_name, t.task_name, t.status, t.assigned_to, t.planned_date, u.name as assigned_user_name
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.project_id = 3
        ORDER BY t.phase_name, t.task_order
        LIMIT 10
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>プロジェクト3のタスク（最初の10件）</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
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
    
    // フェーズ別タスク数
    echo "<h4>フェーズ別タスク数</h4>";
    $stmt = $connection->prepare("
        SELECT phase_name, COUNT(*) as count, 
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
               SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
               SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) as not_started
        FROM tasks 
        WHERE project_id = 3
        GROUP BY phase_name
        ORDER BY phase_name
    ");
    $stmt->execute();
    $phaseStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>フェーズ</th><th>総数</th><th>完了</th><th>進行中</th><th>未開始</th></tr>";
    foreach ($phaseStats as $stat) {
        echo "<tr>";
        echo "<td>{$stat['phase_name']}</td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td>{$stat['completed']}</td>";
        echo "<td>{$stat['in_progress']}</td>";
        echo "<td>{$stat['not_started']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}
?>
