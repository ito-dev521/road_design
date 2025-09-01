<?php
// フェーズ情報テストファイル
require_once 'config.php';
require_once 'database.php';

echo "<h1>フェーズ情報テスト</h1>";

try {
    $db = new Database();

    echo "<h2>データベース内のフェーズ情報</h2>";
    $phases = $db->getAllPhases();

    if ($phases && count($phases) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>フェーズ名</th><th>説明</th></tr>";

        foreach ($phases as $phase) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($phase['phase_name']) . "</td>";
            echo "<td>" . htmlspecialchars($phase['description'] ?: '説明なし') . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p style='color: orange;'>フェーズ情報が存在しません。データベースの初期化が必要かもしれません。</p>";

        // デフォルトのフェーズデータを挿入するテスト
        echo "<h3>デフォルトフェーズデータの作成テスト</h3>";
        $defaultPhases = [
            ['phase_name' => 'フェーズ1', 'description' => 'データ入力・整理段階'],
            ['phase_name' => 'フェーズ2', 'description' => '設計条件の整理'],
            ['phase_name' => 'フェーズ3', 'description' => '平面設計']
        ];

        foreach ($defaultPhases as $phase) {
            $result = $db->createPhase($phase['phase_name'], $phase['description']);
            if ($result) {
                echo "<p style='color: green;'>✓ {$phase['phase_name']} を作成しました</p>";
            } else {
                echo "<p style='color: red;'>✗ {$phase['phase_name']} の作成に失敗しました</p>";
            }
        }

        // 再取得して確認
        echo "<h3>作成後のフェーズ情報</h3>";
        $phases = $db->getAllPhases();
        if ($phases && count($phases) > 0) {
            echo "<ul>";
            foreach ($phases as $phase) {
                echo "<li>" . htmlspecialchars($phase['phase_name']) . ": " . htmlspecialchars($phase['description']) . "</li>";
            }
            echo "</ul>";
        }
    }

    echo "<h2>APIテスト</h2>";
    echo "<p>ブラウザのコンソールで以下のテストを実行してください：</p>";
    echo "<pre>
// フェーズ情報取得テスト
fetch('api.php?path=phases')
  .then(r => r.json())
  .then(data => console.log('Phases API:', data))
  .catch(err => console.error('API Error:', err));
</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>メインページに戻る</a> | <a href='debug.php'>デバッグページ</a></p>";
?>
