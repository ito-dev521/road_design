<?php
// update_task_templates.php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>タスクテンプレートデータの更新</h2>";
    
    // 既存データを削除
    $stmt = $pdo->prepare("DELETE FROM task_templates");
    $stmt->execute();
    echo "✓ 既存のタスクテンプレートデータを削除しました<br>";
    
    // 新しいデータを挿入
    $insertStmt = $pdo->prepare("
        INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    // フェーズ1のデータ
    $phase1Data = [
        ['設計条件の整理', '支給データCADデータ変換', '支給されたCADデータをV-naslに変換する。変換後のデータは中身が分かるように図面種類の名称をつけること', 1, 0, 0],
        ['設計条件の整理', '変換データの印刷等', '支給された図面がDocuまたはPDFに出力されたデータを準備する。(既往成果にあればそれを使いなければ出力する)', 2, 0, 0],
        ['設計条件の整理', '平面図データ整理', '平面図について、レイアウト、測量座標設定を行う。', 3, 0, 0],
        ['設計条件の整理', 'STRAXデータフォルダ作成', 'STRAXのデータを作成する。作成したフォルダには部品等のデータをコピーしてデータの上書きを行うこと。', 4, 0, 0],
        ['設計条件の整理', '平面地形高さ付', '平面図に高さをつける範囲を明記して、高さ付の依頼を行う高さをつける平面図は地形だけ表示させて、MAPデータに変換すること。また、依頼したデータを受け取りSTRAXのデータにコピーすること', 5, 0, 0],
        ['設計条件の整理', '測量地形読込', '実測の測量地形をSTRAXに読み込む', 6, 0, 0],
        ['設計条件の整理', '設計基準整理', '社内統一フォーマットに基準を入力する(道路等級、設計速度、計画交通量などを確認)', 7, 0, 0],
        ['設計条件の整理', '流量計算書の既往成果抜出し', '既往成果の報告書を確認して流量計算書の抜粋、オリジナルデータ準備(Excel、流域図(CADデータ))', 8, 0, 0],
        ['設計条件の整理', '路線の統一基準などの抜出し', '報告書を確認して路線の統一事項が無いか確認(PDFとオリジナルデータがあれば抜き出す)', 9, 0, 0],
        ['設計条件の整理', '舗装構成の既往成果抜出し', '舗装構成の根拠資料の抜出し無ければ問い合わせを行う', 10, 0, 0],
        ['設計条件の整理', '既往成果で検討した内容を抜き出して整理する', '検討した項目とその資料をフォルダに抜き出してまとめておく', 11, 0, 0],
        ['設計条件の整理', '幅杭計算書既往成果抜出し', '報告書に幅杭計算書があれば抜き出す', 12, 0, 0],
        ['設計条件の整理', '中心線計算書既往成果抜出し', '報告書に中心線計算書があれば抜き出す', 13, 0, 0],
        ['設計条件の整理', '標準横断図作成', '幅員構成、路肩部の構造、舗装構成、法尻の構造、水平排水層・基盤排水層の有無、側溝の構造などを確認して作成。ただし、常に条件が変わるのでその都度修正を行うこと', 14, 0, 0],
        ['設計条件の整理', '幅員構成を確認', '既往成果より幅員構成を確認する。確認した幅員構成を標準横断図に反映し、標準横断図で元請に確認する。', 15, 0, 0],
        ['設計条件の整理', '路肩の構造を確認', '既往成果または統一基準等より幅路肩の構造を確認する。情報がない場合は一般的な構造を元請に提示して構造を確認すること。', 16, 0, 0],
        ['設計条件の整理', '舗装構成を確認', '既往成果より舗装構成を確認する。情報がない場合は元請に問い合わせること。', 17, 0, 0],
        ['設計条件の整理', '法尻の構造を確認', '既往成果または統一基準等より法尻の構造を確認する。情報がない場合は一般的な構造を元請に提示して構造を確認すること。', 18, 0, 0],
        ['設計条件の整理', '水平排水層・基盤排水層の確認', '水平排水層・基盤排水層を設置するか元請に確認する。必要であればその構造も元請に確認すること。', 19, 0, 0],
        ['設計条件の整理', '側溝の構造を確認', '使用する側溝の構造を確認する。(標準図集、メーカー製品など確認する)', 20, 0, 0]
    ];
    
    $insertCount = 0;
    foreach ($phase1Data as $data) {
        $insertStmt->execute($data);
        $insertCount++;
    }
    
    echo "✓ {$insertCount}件のタスクテンプレートを作成しました<br>";
    
    // 作成されたデータを確認
    echo "<h3>作成されたデータ:</h3>";
    $stmt = $pdo->query("SELECT id, phase_name, task_name, task_order FROM task_templates ORDER BY task_order");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>フェーズ名</th><th>タスク名</th><th>順序</th></tr>";
    foreach ($templates as $template) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($template['id']) . "</td>";
        echo "<td>" . htmlspecialchars($template['phase_name']) . "</td>";
        echo "<td>" . htmlspecialchars($template['task_name']) . "</td>";
        echo "<td>" . htmlspecialchars($template['task_order']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
