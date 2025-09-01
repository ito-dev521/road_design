<?php
// デバッグ用：各APIエンドポイントをテスト
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== API デバッグテスト ===\n\n";

// 各APIエンドポイントをテスト
$endpoints = [
    'users' => 'api.php?path=users',
    'phases' => 'api.php?path=phases', 
    'templates' => 'api.php?path=templates',
    'manuals' => 'api.php?path=manuals'
];

foreach ($endpoints as $name => $url) {
    echo "=== {$name} APIテスト ===\n";
    echo "URL: {$url}\n";
    
    // output buffering を使用してエラーをキャッチ
    ob_start();
    
    try {
        // $_GET をシミュレート
        $_GET['path'] = str_replace('api.php?path=', '', $url);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // セッション開始（認証エラーを避けるため）
        if (!session_id()) {
            session_start();
        }
        $_SESSION['user_id'] = 1; // テスト用
        $_SESSION['user_role'] = 'manager';
        
        // api.phpを実行
        include 'api.php';
        
    } catch (Throwable $e) {
        echo "エラー: " . $e->getMessage() . "\n";
        echo "ファイル: " . $e->getFile() . "\n";
        echo "行: " . $e->getLine() . "\n";
        echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    }
    
    $output = ob_get_clean();
    echo "出力:\n{$output}\n";
    echo "出力の長さ: " . strlen($output) . " bytes\n";
    
    // 先頭100文字を表示（HTMLが返されているかチェック）
    echo "出力の先頭: " . substr($output, 0, 100) . "\n";
    echo "\n" . str_repeat('-', 50) . "\n\n";
}
?>
