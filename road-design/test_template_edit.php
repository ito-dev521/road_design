<?php
// test_template_edit.php
if (!session_id()) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'manager';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>テンプレート編集APIテスト</h2>";

// 1. フェーズ一覧の取得テスト
echo "<h3>1. フェーズ一覧取得テスト</h3>";
$_GET['path'] = 'phases';
$_SERVER['REQUEST_METHOD'] = 'GET';

header('Content-Type: application/json');

ob_start();
include 'api.php';
$phases_output = ob_get_clean();

echo "<pre>フェーズAPIレスポンス:\n" . htmlspecialchars($phases_output) . "</pre>";

// 2. テンプレート詳細取得テスト（ID=1でテスト）
echo "<h3>2. テンプレート詳細取得テスト（ID=1）</h3>";
$_GET['path'] = 'admin/templates/1';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
include 'api.php';
$template_output = ob_get_clean();

echo "<pre>テンプレート詳細APIレスポンス:\n" . htmlspecialchars($template_output) . "</pre>";

// 3. データベース直接確認
echo "<h3>3. データベース直接確認</h3>";
try {
    require_once 'config.php';
    require_once 'database.php';
    
    $db = new Database();
    
    // フェーズテーブル確認
    echo "<h4>フェーズテーブル:</h4>";
    $phases = $db->getAllPhases();
    echo "<pre>" . print_r($phases, true) . "</pre>";
    
    // テンプレートテーブル確認
    echo "<h4>テンプレートテーブル（ID=1）:</h4>";
    $template = $db->getTaskTemplateById(1);
    echo "<pre>" . print_r($template, true) . "</pre>";
    
} catch (Exception $e) {
    echo "データベースエラー: " . $e->getMessage();
}
?>
