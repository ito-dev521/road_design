<?php
// create_sample_projects.php - サンプルプロジェクト作成
echo "<h2>サンプルプロジェクト作成</h2>";

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
    
    // 既存のプロジェクト数を確認
    $stmt = $connection->query("SELECT COUNT(*) as count FROM projects");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>現在のプロジェクト数: " . $count['count'] . "件</p>";
    
    if ($count['count'] > 0) {
        echo "<p>プロジェクトが既に存在します。サンプルプロジェクトは作成しません。</p>";
        echo "<p><a href='index.html'>index.htmlに戻る</a></p>";
        exit;
    }
    
    // サンプルプロジェクトを作成
    $sampleProjects = [
        [
            'name' => '国道123号線拡幅工事',
            'description' => '国道123号線の2車線から4車線への拡幅工事の詳細設計',
            'client_name' => '国土交通省',
            'project_code' => 'RD2024-001',
            'start_date' => '2024-01-15',
            'target_end_date' => '2024-12-31',
            'status' => 'planning'
        ],
        [
            'name' => '県道456号線橋梁補修',
            'description' => '県道456号線の老朽化した橋梁の補修工事設計',
            'client_name' => '県庁建設部',
            'project_code' => 'RD2024-002',
            'start_date' => '2024-02-01',
            'target_end_date' => '2024-08-31',
            'status' => 'in_progress'
        ],
        [
            'name' => '市道789号線交差点改良',
            'description' => '市道789号線の危険交差点の改良設計',
            'client_name' => '市役所都市整備課',
            'project_code' => 'RD2024-003',
            'start_date' => '2024-03-01',
            'target_end_date' => '2024-10-31',
            'status' => 'planning'
        ]
    ];
    
    $stmt = $connection->prepare("
        INSERT INTO projects (name, description, client_name, project_code, start_date, target_end_date, status, created_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $createdCount = 0;
    foreach ($sampleProjects as $project) {
        try {
            $stmt->execute([
                $project['name'],
                $project['description'],
                $project['client_name'],
                $project['project_code'],
                $project['start_date'],
                $project['target_end_date'],
                $project['status'],
                1 // 作成者ID（管理者）
            ]);
            $createdCount++;
            echo "<p style='color: green;'>✓ プロジェクト作成: {$project['name']}</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ プロジェクト作成失敗: {$project['name']} - {$e->getMessage()}</p>";
        }
    }
    
    echo "<h3>作成完了</h3>";
    echo "<p>{$createdCount}件のサンプルプロジェクトを作成しました。</p>";
    
    // 作成後のプロジェクト一覧を表示
    $stmt = $connection->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>作成されたプロジェクト一覧</h4>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>名前</th><th>クライアント</th><th>ステータス</th><th>開始日</th><th>終了予定日</th></tr>";
    foreach ($projects as $project) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($project['id']) . "</td>";
        echo "<td>" . htmlspecialchars($project['name']) . "</td>";
        echo "<td>" . htmlspecialchars($project['client_name']) . "</td>";
        echo "<td>" . htmlspecialchars($project['status']) . "</td>";
        echo "<td>" . htmlspecialchars($project['start_date']) . "</td>";
        echo "<td>" . htmlspecialchars($project['target_end_date']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='index.html'>index.htmlに戻る</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
