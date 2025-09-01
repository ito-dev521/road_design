<?php
// create_design_drawing_templates.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>設計図作成フェーズのタスクテンプレート作成</h2>";
    
    // 既存の設計図作成フェーズを削除（もし存在する場合）
    $stmt = $pdo->prepare("DELETE FROM task_templates WHERE phase_name = '設計図作成'");
    $stmt->execute();
    echo "✓ 既存の設計図作成タスクテンプレートを削除しました<br>";
    
    $stmt = $pdo->prepare("DELETE FROM phases WHERE name = '設計図作成'");
    $stmt->execute();
    echo "✓ 既存の設計図作成フェーズを削除しました<br>";
    
    // 設計図作成フェーズを挿入
    $phaseInsertStmt = $pdo->prepare("
        INSERT INTO phases (name, description, order_num, is_active)
        VALUES (?, ?, ?, ?)
    ");
    
    $phaseData = ['設計図作成', '設計図作成作業', 5, 1];
    $phaseInsertStmt->execute($phaseData);
    echo "✓ 設計図作成フェーズを作成しました<br>";
    
    // タスクテンプレートデータを挿入
    $templateInsertStmt = $pdo->prepare("
        INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // 設計図作成のタスクテンプレート
    $templatesData = [
        ['設計図作成', '図面目録作成', '', 1, 0, 0],
        ['設計図作成', '平面図作成', '平面計画の作成、起点終点の旗上げ、数量旗上げなど', 2, 0, 0],
        ['設計図作成', '平面図チェックおよび修正', '計画が技術的に問題がないか確認を行う', 3, 0, 0],
        ['設計図作成', '縦断図作成', 'STRAXでの縦断計画作成および出力、起点終点旗上げ、交差道路・横断構造物の旗上げなど', 4, 0, 0],
        ['設計図作成', '縦断図チェックおよび修正', '計画が技術的に問題がないか確認を行う', 5, 0, 0],
        ['設計図作成', '標準横断図作成', '横断図の抜き出し、構造物の旗揚げ、設計規格表の作成など', 6, 0, 0],
        ['設計図作成', '標準横断図チェックおよび修正', '計画が技術的に問題がないか確認を行う', 7, 0, 0],
        ['設計図作成', '横断図作成', 'STRAXでの横断計画作成および出力、平面図との整合、地下埋設物の反映など', 8, 0, 0],
        ['設計図作成', '横断図作成チェックおよび修正', '計画が技術的に問題がないか確認を行う', 9, 0, 0],
        ['設計図作成', '構造図作成', '必要構造物の収集、数量表の作成など', 10, 0, 0],
        ['設計図作成', '構造図作成チェックおよび修正', '計画が技術的に問題がないか確認を行う', 11, 0, 0],
        ['設計図作成', '地下埋設物平面図', '地下埋設物の反映、凡例の作成など', 12, 0, 0],
        ['設計図作成', '地下埋設物平面図チェックおよび修正', '地下埋設資料との整合確認など', 13, 0, 0],
        ['設計図作成', '排水系統図', '接続高の旗揚げ作成など', 14, 0, 0],
        ['設計図作成', '排水系統図チェックおよび修正', '接続高に問題ないか確認を行う', 15, 0, 0],
        ['設計図作成', '設計図一式チェックシート作成(数字チェック)', '設計図について数字や計算式等のチェックを行う', 16, 0, 0],
        ['設計図作成', '設計図一式チェックシート作成(体裁チェック)', '漏れた項目はないか、不要なものが表示されていないか、枠線などの見栄えは大丈夫か、チェックを行う', 17, 0, 0]
    ];
    
    $insertCount = 0;
    foreach ($templatesData as $data) {
        $templateInsertStmt->execute($data);
        $insertCount++;
    }
    
    echo "✓ {$insertCount}件の設計図作成タスクテンプレートを作成しました<br>";
    
    // 作成されたデータを確認
    echo "<h3>作成されたフェーズデータ:</h3>";
    $stmt = $pdo->query("SELECT id, name, description, order_num FROM phases WHERE name = '設計図作成' ORDER BY order_num");
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
    $stmt = $pdo->query("SELECT id, phase_name, task_name, content, task_order FROM task_templates WHERE phase_name = '設計図作成' ORDER BY task_order");
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
    echo "<p>設計図作成フェーズのデータ作成が完了しました。以下のURLで確認してください：</p>";
    echo "<ul>";
    echo "<li><a href='settings.html' target='_blank'>設定画面</a></li>";
    echo "<li><a href='index.html' target='_blank'>メイン画面</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
