<?php
// プロジェクト表示テストファイル
require_once 'config.php';
require_once 'database.php';

echo "<h1>プロジェクト表示テスト</h1>";

try {
    $db = new Database();
    $projects = $db->getAllProjects();

    echo "<h2>取得したプロジェクトデータ</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>プロジェクトコード</th><th>プロジェクト名</th><th>発注者</th><th>表示形式</th></tr>";

    foreach ($projects as $project) {
        $displayName = $project['project_code']
            ? $project['project_code'] . ' ' . $project['name']
            : $project['name'];

        echo "<tr>";
        echo "<td>" . htmlspecialchars($project['id']) . "</td>";
        echo "<td>" . htmlspecialchars($project['project_code'] ?: 'なし') . "</td>";
        echo "<td>" . htmlspecialchars($project['name']) . "</td>";
        echo "<td>" . htmlspecialchars($project['client_name'] ?: '未設定') . "</td>";
        echo "<td><strong>" . htmlspecialchars($displayName) . "</strong></td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<h2>プルダウン表示テスト</h2>";
    echo "<select style='width: 400px; padding: 8px;'>";
    echo "<option value=''>プロジェクトを選択してください</option>";

    foreach ($projects as $project) {
        $displayName = $project['project_code']
            ? $project['project_code'] . ' ' . $project['name']
            : $project['name'];

        echo "<option value='" . htmlspecialchars($project['id']) . "'>" . htmlspecialchars($displayName) . "</option>";
    }

    echo "</select>";

    if (count($projects) === 0) {
        echo "<p style='color: orange;'>プロジェクトが存在しません。まずプロジェクトを作成してください。</p>";
        echo "<a href='index.html'>メインページに戻る</a>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>メインページに戻る</a> | <a href='debug.php'>デバッグページ</a></p>";
?>
