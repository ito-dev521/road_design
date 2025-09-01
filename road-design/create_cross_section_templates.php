<?php
// create_cross_section_templates.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>横断設計フェーズのタスクテンプレート作成</h2>";
    
    // 既存の横断設計フェーズを削除（もし存在する場合）
    $stmt = $pdo->prepare("DELETE FROM task_templates WHERE phase_name = '横断設計'");
    $stmt->execute();
    echo "✓ 既存の横断設計タスクテンプレートを削除しました<br>";
    
    $stmt = $pdo->prepare("DELETE FROM phases WHERE name = '横断設計'");
    $stmt->execute();
    echo "✓ 既存の横断設計フェーズを削除しました<br>";
    
    // 横断設計フェーズを挿入
    $phaseInsertStmt = $pdo->prepare("
        INSERT INTO phases (name, description, order_num, is_active)
        VALUES (?, ?, ?, ?)
    ");
    
    $phaseData = ['横断設計', '横断設計作業', 3, 1];
    $phaseInsertStmt->execute($phaseData);
    echo "✓ 横断設計フェーズを作成しました<br>";
    
    // タスクテンプレートデータを挿入
    $templateInsertStmt = $pdo->prepare("
        INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // 横断設計のタスクテンプレート
    $templatesData = [
        ['横断設計', 'STRAXで横断計画復元', '', 1, 0, 0],
        ['横断設計', '横断図作成ご平面プロットで整合性確認', '横断図に法尻等までの距離を計測して、平面図にブロットする', 2, 0, 0],
        ['横断設計', 'STRAXの横断設計に幅杭を設置', '', 3, 0, 0],
        ['横断設計', '地形読み込み後不備がないかチェックする', '記号の文字サイズや向きがおかしくないか確認する', 4, 0, 0]
    ];
    
    $insertCount = 0;
    foreach ($templatesData as $data) {
        $templateInsertStmt->execute($data);
        $insertCount++;
    }
    
    echo "✓ {$insertCount}件の横断設計タスクテンプレートを作成しました<br>";
    
    // 作成されたデータを確認
    echo "<h3>作成されたフェーズデータ:</h3>";
    $stmt = $pdo->query("SELECT id, name, description, order_num FROM phases WHERE name = '横断設計' ORDER BY order_num");
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>フェーズ名</th><th>説明</th><th>順序</th></tr>";
    foreach ($phases as $phase) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($phase['id']) . "</td>";
        echo "<td>" . htmlspecialchars($phase['name']) . "</td>";
        echo "<td>" . htmlspecialchars($phase['description']) . "</td>";
        echo "<td>" . htmlspecialchars($phase['order_num']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>作成されたタスクテンプレートデータ:</h3>";
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
    echo "<p>横断設計フェーズのデータ作成が完了しました。以下のURLで確認してください：</p>";
    echo "<ul>";
    echo "<li><a href='settings.html' target='_blank'>設定画面</a></li>";
    echo "<li><a href='index.html' target='_blank'>メイン画面</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
