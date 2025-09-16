<?php
/**
 * 新しいデータベース設定にconfig.phpを更新するスクリプト
 */

// 新しいデータベース設定
$newDbConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'roadplan_road',
    'username' => 'roadplan_road',
    'password' => '8R23ENvs'
];

$configFile = __DIR__ . '/config.php';

echo "config.phpを新しいデータベース設定に更新します...\n";
echo "新しい設定:\n";
echo "  ホスト: " . $newDbConfig['host'] . "\n";
echo "  データベース: " . $newDbConfig['database'] . "\n";
echo "  ユーザー: " . $newDbConfig['username'] . "\n";
echo "  ポート: " . $newDbConfig['port'] . "\n\n";

// 現在のconfig.phpを読み込み
if (!file_exists($configFile)) {
    echo "❌ config.phpが見つかりません\n";
    exit(1);
}

$configContent = file_get_contents($configFile);

// 現在の設定を表示
echo "現在の設定:\n";
preg_match("/define\('DB_HOST',\s*'([^']+)'\);/", $configContent, $matches);
echo "  ホスト: " . ($matches[1] ?? '不明') . "\n";

preg_match("/define\('DB_NAME',\s*'([^']+)'\);/", $configContent, $matches);
echo "  データベース: " . ($matches[1] ?? '不明') . "\n";

preg_match("/define\('DB_USER',\s*'([^']+)'\);/", $configContent, $matches);
echo "  ユーザー: " . ($matches[1] ?? '不明') . "\n";

preg_match("/define\('DB_PORT',\s*'([^']+)'\);/", $configContent, $matches);
echo "  ポート: " . ($matches[1] ?? '不明') . "\n\n";

// 設定を更新
$configContent = preg_replace(
    "/define\('DB_HOST',\s*'[^']+'\);/",
    "define('DB_HOST', '" . $newDbConfig['host'] . "');",
    $configContent
);

$configContent = preg_replace(
    "/define\('DB_PORT',\s*'[^']+'\);/",
    "define('DB_PORT', '" . $newDbConfig['port'] . "');",
    $configContent
);

$configContent = preg_replace(
    "/define\('DB_NAME',\s*'[^']+'\);/",
    "define('DB_NAME', '" . $newDbConfig['database'] . "');",
    $configContent
);

$configContent = preg_replace(
    "/define\('DB_USER',\s*'[^']+'\);/",
    "define('DB_USER', '" . $newDbConfig['username'] . "');",
    $configContent
);

$configContent = preg_replace(
    "/define\('DB_PASS',\s*'[^']+'\);/",
    "define('DB_PASS', '" . $newDbConfig['password'] . "');",
    $configContent
);

// バックアップを作成
$backupFile = $configFile . '.backup.' . date('Y-m-d_H-i-s');
if (copy($configFile, $backupFile)) {
    echo "✅ 現在のconfig.phpをバックアップしました: " . basename($backupFile) . "\n";
} else {
    echo "⚠️ バックアップの作成に失敗しました\n";
}

// 新しい設定でファイルを更新
if (file_put_contents($configFile, $configContent)) {
    echo "✅ config.phpが正常に更新されました\n\n";
    
    // 更新後の設定を確認
    echo "更新後の設定:\n";
    preg_match("/define\('DB_HOST',\s*'([^']+)'\);/", $configContent, $matches);
    echo "  ホスト: " . ($matches[1] ?? '不明') . "\n";

    preg_match("/define\('DB_NAME',\s*'([^']+)'\);/", $configContent, $matches);
    echo "  データベース: " . ($matches[1] ?? '不明') . "\n";

    preg_match("/define\('DB_USER',\s*'([^']+)'\);/", $configContent, $matches);
    echo "  ユーザー: " . ($matches[1] ?? '不明') . "\n";

    preg_match("/define\('DB_PORT',\s*'[^']+'\);/", $configContent, $matches);
    echo "  ポート: " . ($matches[1] ?? '不明') . "\n\n";
    
    echo "✅ 設定更新完了！\n";
    echo "次のステップ:\n";
    echo "1. データベース接続をテスト\n";
    echo "2. アプリケーションの動作確認\n";
    
} else {
    echo "❌ config.phpの更新に失敗しました\n";
    exit(1);
}
?>
