<?php
// 個別エンドポイントテスト
// セッション開始（設定より先に）
if (!session_id()) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'manager';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// APIパス設定
$_GET['path'] = $_GET['test'] ?? 'users';
$_SERVER['REQUEST_METHOD'] = 'GET';

// コンテントタイプをJSONに設定
header('Content-Type: application/json');

echo "テスト対象: " . $_GET['path'] . "\n\n";

try {
    // APIファイルをインクルード
    ob_start(); // 出力バッファリング開始
    include 'api.php';
    $output = ob_get_clean(); // バッファの内容を取得してクリア
    
    echo "API出力:\n";
    echo $output;
    
} catch (Throwable $e) {
    ob_clean(); // バッファをクリア
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
