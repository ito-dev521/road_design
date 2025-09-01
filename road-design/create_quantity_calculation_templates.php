<?php
// create_quantity_calculation_templates.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>数量計算書作成フェーズのタスクテンプレート作成</h2>";
    
    // 既存の数量計算書作成フェーズを削除（もし存在する場合）
    $stmt = $pdo->prepare("DELETE FROM task_templates WHERE phase_name = '数量計算書作成'");
    $stmt->execute();
    echo "✓ 既存の数量計算書作成タスクテンプレートを削除しました<br>";
    
    $stmt = $pdo->prepare("DELETE FROM phases WHERE name = '数量計算書作成'");
    $stmt->execute();
    echo "✓ 既存の数量計算書作成フェーズを削除しました<br>";
    
    // 数量計算書作成フェーズを挿入
    $phaseInsertStmt = $pdo->prepare("
        INSERT INTO phases (name, description, order_num, is_active)
        VALUES (?, ?, ?, ?)
    ");
    
    $phaseData = ['数量計算書作成', '数量計算書作成作業', 4, 1];
    $phaseInsertStmt->execute($phaseData);
    echo "✓ 数量計算書作成フェーズを作成しました<br>";
    
    // タスクテンプレートデータを挿入
    $templateInsertStmt = $pdo->prepare("
        INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // 数量計算書作成のタスクテンプレート
    $templatesData = [
        ['数量計算書作成', '土工根拠図作成', '', 1, 0, 0],
        ['数量計算書作成', '平面図旗上げの数量集計', '平面図の旗上げから数量を集計して数量名人に入力', 2, 0, 0],
        ['数量計算書作成', '単位数量計算書作成', '', 3, 0, 0],
        ['数量計算書作成', '舗装数量根拠図作成', '横断図では計算できない端部舗装の根拠図を作成して、数量名人に反映', 4, 0, 0],
        ['数量計算書作成', '集水桝計算書作成', 'ロードブランのフォーマットで作成', 5, 0, 0],
        ['数量計算書作成', '数量計算書チェックシート作成', '図面および根拠図面との数値チェックや、計算式のチェック等', 6, 0, 0],
        ['数量計算書作成', '数量計算書ポイントチェック', '算出に方法に問題がないか、数量漏れの項目がないか、数量の大きいオーダーチェック', 7, 0, 0]
    ];
    
    $insertCount = 0;
    foreach ($templatesData as $data) {
        $templateInsertStmt->execute($data);
        $insertCount++;
    }
    
    echo "✓ {$insertCount}件の数量計算書作成タスクテンプレートを作成しました<br>";
    
    // 作成されたデータを確認
    echo "<h3>作成されたフェーズデータ:</h3>";
    $stmt = $pdo->query("SELECT id, name, description, order_num FROM phases WHERE name = '数量計算書作成' ORDER BY order_num");
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
    $stmt = $pdo->query("SELECT id, phase_name, task_name, content, task_order FROM task_templates WHERE phase_name = '数量計算書作成' ORDER BY task_order");
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
    echo "<p>数量計算書作成フェーズのデータ作成が完了しました。以下のURLで確認してください：</p>";
    echo "<ul>";
    echo "<li><a href='settings.html' target='_blank'>設定画面</a></li>";
    echo "<li><a href='index.html' target='_blank'>メイン画面</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
