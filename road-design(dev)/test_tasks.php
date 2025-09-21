<?php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    if (!$connection) {
        echo "データベース接続エラー\n";
        exit;
    }
    
    echo "=== データベース接続成功 ===\n\n";
    
    // プロジェクト一覧を取得
    $stmt = $connection->prepare("SELECT id, name FROM projects");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "プロジェクト一覧:\n";
    foreach ($projects as $project) {
        echo "- ID: {$project['id']}, 名前: {$project['name']}\n";
    }
    echo "\n";
    
    // 各プロジェクトのタスク数を確認
    foreach ($projects as $project) {
        $stmt = $connection->prepare("SELECT COUNT(*) as count FROM tasks WHERE project_id = ?");
        $stmt->execute([$project['id']]);
        $taskCount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "プロジェクト {$project['name']} (ID: {$project['id']}) のタスク数: {$taskCount['count']}\n";
        
        if ($taskCount['count'] > 0) {
            // タスクの詳細を表示
            $stmt = $connection->prepare("
                SELECT t.*, t.task_name as name, u.name as assigned_user_name, tt.name as template_name, tt.content as template_content, 
                       tt.is_technical_work, tt.has_manual, tt.phase_id, tt.task_order, p.name as phase_name
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN task_templates tt ON t.template_id = tt.id
                LEFT JOIN phases p ON tt.phase_id = p.id
                WHERE t.project_id = ?
                ORDER BY tt.phase_id, tt.task_order
                LIMIT 3
            ");
            $stmt->execute([$project['id']]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "  最初の3つのタスク:\n";
            foreach ($tasks as $task) {
                echo "    - ID: {$task['id']}, 名前: {$task['name']}, テンプレートID: {$task['template_id']}, フェーズ: {$task['phase_name']}\n";
            }
        }
        echo "\n";
    }
    
    // タスクテンプレートの確認
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM task_templates");
    $stmt->execute();
    $templateCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "タスクテンプレート数: {$templateCount['count']}\n";
    
    // フェーズの確認
    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM phases");
    $stmt->execute();
    $phaseCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "フェーズ数: {$phaseCount['count']}\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>
