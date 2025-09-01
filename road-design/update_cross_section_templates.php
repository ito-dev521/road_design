<?php
// update_cross_section_templates.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>横断設計フェーズのタスクテンプレート更新</h2>";
    
    // 既存の横断設計タスクテンプレートを削除
    $stmt = $pdo->prepare("DELETE FROM task_templates WHERE phase_name = '横断設計'");
    $stmt->execute();
    echo "✓ 既存の横断設計タスクテンプレートを削除しました<br>";
    
    // タスクテンプレートデータを挿入
    $templateInsertStmt = $pdo->prepare("
        INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // 新しい横断設計のタスクテンプレート
    $templatesData = [
        ['横断設計', '横断図作成', 'STRAXでの横断計画作成および出力、平面図との整合、地下埋設物の反映など', 1, 0, 0],
        ['横断設計', '横断図作成チェックおよび修正', '計画が技術的に問題がないか確認を行う', 2, 0, 0],
        ['横断設計', '構造図作成', '必要構造物の収集、数量表の作成など', 3, 0, 0],
        ['横断設計', '構造図作成チェックおよび修正', '計画が技術的に問題がないか確認を行う', 4, 0, 0]
    ];
    
    $insertCount = 0;
    foreach ($templatesData as $data) {
        $templateInsertStmt->execute($data);
        $insertCount++;
    }
    
    echo "✓ {$insertCount}件の横断設計タスクテンプレートを更新しました<br>";
    
    // 作成されたデータを確認
    echo "<h3>更新されたタスクテンプレートデータ:</h3>";
    $stmt = $pdo->query("SELECT id, phase_name, task_name, content, task_order FROM task_templates WHERE phase_name = '横断設計' ORDER BY task_order");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>フェーズ名</th><th>タスク名</th><th>内容</th><th>順序</th></tr>";
    foreach ($templates as $template) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($template['id']) . "</td>";
        echo "<td>" . htmlspecialchars($template['phase_name']) . "</td>";
        echo "<td>" . htmlspecialchars($template['task_name']) . "</td>";
        echo "<td>" . htmlspecialchars(mb_substr($template['content'], 0, 50)) . (mb_strlen($template['content']) > 50 ? '...' : '') . "</td>";
        echo "<td>" . htmlspecialchars($template['task_order']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>完了</h3>";
    echo "<p>横断設計フェーズのタスクテンプレート更新が完了しました。以下のURLで確認してください：</p>";
    echo "<ul>";
    echo "<li><a href='settings.html' target='_blank'>設定画面</a></li>";
    echo "<li><a href='index.html' target='_blank'>メイン画面</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
