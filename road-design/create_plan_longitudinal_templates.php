<?php
// create_plan_longitudinal_templates.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>平面縦断設計フェーズのタスクテンプレート作成</h2>";
    
    // 既存の平面縦断設計フェーズを削除（もし存在する場合）
    $stmt = $pdo->prepare("DELETE FROM task_templates WHERE phase_name = '平面縦断設計'");
    $stmt->execute();
    echo "✓ 既存の平面縦断設計タスクテンプレートを削除しました<br>";
    
    $stmt = $pdo->prepare("DELETE FROM phases WHERE name = '平面縦断設計'");
    $stmt->execute();
    echo "✓ 既存の平面縦断設計フェーズを削除しました<br>";
    
    // 平面縦断設計フェーズを挿入
    $phaseInsertStmt = $pdo->prepare("
        INSERT INTO phases (name, description, order_num, is_active)
        VALUES (?, ?, ?, ?)
    ");
    
    $phaseData = ['平面縦断設計', '平面縦断設計作業', 2, 1];
    $phaseInsertStmt->execute($phaseData);
    echo "✓ 平面縦断設計フェーズを作成しました<br>";
    
    // タスクテンプレートデータを挿入
    $templateInsertStmt = $pdo->prepare("
        INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // 平面縦断設計のタスクテンプレート
    $templatesData = [
        ['平面縦断設計', '中心線復元', '中心線データ作成', 1, 0, 0],
        ['平面縦断設計', '中心線計算書作成', '復元した中心線で中心線計算書を作成。作成した計算書と既往成果の中心線計算書が一致しているかチェック(※必要な箇所のみチェック)', 2, 0, 0],
        ['平面縦断設計', 'STRAXに縦断線形入力', '既往成果の縦断図より縦断線形を入力する', 3, 0, 0],
        ['平面縦断設計', 'STRAXに片勾配を設定', '既往成果の縦断図より片勾配の入力を行う(規格もチェックする事)', 4, 0, 0],
        ['平面縦断設計', 'STRAXに拡幅等のすり付け設定', '既往成果の縦断図より拡幅の入力および交差点等の幅員すりつけ設定を行う', 5, 0, 0],
        ['平面縦断設計', '縦断図作成', 'STRAXで縦断図を作成して出力する(STRAXで旗上げ等の設定も行う。また既往成果の必要コメントは追加する)', 6, 0, 0],
        ['平面縦断設計', '縦断線形計算書作成', 'Excelの縦断線形書式に縦断線形データを入力する', 7, 0, 0],
        ['平面縦断設計', '幅杭を平面図に設置', '平面図の幅杭をV-nasの幅杭で復元(復元データは非表示または、別データとして保存する)', 8, 0, 0],
        ['平面縦断設計', '作図した幅杭から幅杭計算書を作成', '平面図にV-nasで作成した幅杭の幅杭計算書を作成し、既往成果の幅杭計算書と合っているかチェックを行う。', 9, 0, 0]
    ];
    
    $insertCount = 0;
    foreach ($templatesData as $data) {
        $templateInsertStmt->execute($data);
        $insertCount++;
    }
    
    echo "✓ {$insertCount}件の平面縦断設計タスクテンプレートを作成しました<br>";
    
    // 作成されたデータを確認
    echo "<h3>作成されたフェーズデータ:</h3>";
    $stmt = $pdo->query("SELECT id, name, description, order_num FROM phases WHERE name = '平面縦断設計' ORDER BY order_num");
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
    $stmt = $pdo->query("SELECT id, phase_name, task_name, content, task_order FROM task_templates WHERE phase_name = '平面縦断設計' ORDER BY task_order");
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
    echo "<p>平面縦断設計フェーズのデータ作成が完了しました。以下のURLで確認してください：</p>";
    echo "<ul>";
    echo "<li><a href='settings.html' target='_blank'>設定画面</a></li>";
    echo "<li><a href='index.html' target='_blank'>メイン画面</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
