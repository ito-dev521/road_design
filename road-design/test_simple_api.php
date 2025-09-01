<?php
// 簡単なAPIテスト
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Content-Type: text/plain\n\n";
echo "=== API テスト ===\n\n";

// 必要なファイルをインクルード
try {
    require_once 'config.php';
    echo "✓ config.php 読み込み成功\n";
} catch (Exception $e) {
    echo "✗ config.php エラー: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once 'auth.php';
    echo "✓ auth.php 読み込み成功\n";
} catch (Exception $e) {
    echo "✗ auth.php エラー: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once 'database.php';
    echo "✓ database.php 読み込み成功\n";
} catch (Exception $e) {
    echo "✗ database.php エラー: " . $e->getMessage() . "\n";
    exit;
}

// データベース接続テスト
try {
    $db = new Database();
    $connection = $db->getConnection();
    if ($connection) {
        echo "✓ データベース接続成功\n";
    } else {
        echo "✗ データベース接続失敗\n";
    }
} catch (Exception $e) {
    echo "✗ データベース接続エラー: " . $e->getMessage() . "\n";
}

// APIコントローラークラスをテスト
try {
    // セッションをシミュレート
    if (!session_id()) {
        session_start();
    }
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'manager';
    
    // ApiControllerクラスのインスタンス化テスト
    include_once 'api.php';
    
    echo "✓ api.php 読み込み成功\n";
    
    $api = new ApiController();
    echo "✓ ApiController インスタンス化成功\n";
    
} catch (Exception $e) {
    echo "✗ ApiController エラー: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行: " . $e->getLine() . "\n";
} catch (Throwable $e) {
    echo "✗ 致命的エラー: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行: " . $e->getLine() . "\n";
}

echo "\nテスト完了\n";
?>
