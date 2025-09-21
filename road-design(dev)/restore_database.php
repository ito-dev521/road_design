<?php
/**
 * データベース復元スクリプト
 * 道路詳細設計管理システム用
 */

// 新しいデータベース設定（復元先）
$newDbConfig = [
    'host' => 'localhost',        // 新しいサーバーのホスト
    'port' => '3306',            // 新しいサーバーのポート
    'database' => 'roadplan_road', // 新しいデータベース名
    'username' => 'roadplan_road',    // 新しいユーザー名
    'password' => '8R23ENvs' // 新しいパスワード
];

// バックアップファイルのパス
$backupFile = __DIR__ . '/backups/road_design_backup_latest.sql';

echo "データベース復元を開始します...\n";
echo "復元先データベース: " . $newDbConfig['database'] . "\n";
echo "復元先ホスト: " . $newDbConfig['host'] . "\n";
echo "バックアップファイル: " . $backupFile . "\n\n";

// バックアップファイルの存在確認
if (!file_exists($backupFile)) {
    echo "❌ バックアップファイルが見つかりません: " . $backupFile . "\n";
    echo "利用可能なバックアップファイル:\n";
    $backupDir = dirname($backupFile);
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/*.sql');
        foreach ($files as $file) {
            echo "  - " . basename($file) . " (" . formatBytes(filesize($file)) . ")\n";
        }
    }
    exit(1);
}

echo "✅ バックアップファイルが見つかりました\n";
echo "サイズ: " . formatBytes(filesize($backupFile)) . "\n\n";

// データベース接続テスト
echo "データベース接続をテスト中...\n";
try {
    $dsn = "mysql:host={$newDbConfig['host']};port={$newDbConfig['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $newDbConfig['username'], $newDbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ データベース接続成功\n";
} catch (PDOException $e) {
    echo "❌ データベース接続失敗: " . $e->getMessage() . "\n";
    exit(1);
}

// データベースの存在確認と作成
echo "データベースの存在確認中...\n";
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$newDbConfig['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ データベース準備完了\n";
} catch (PDOException $e) {
    echo "❌ データベース作成失敗: " . $e->getMessage() . "\n";
    exit(1);
}

// 復元コマンドを構築
$command = sprintf(
    'mysql -h %s -P %s -u %s -p%s %s < %s',
    escapeshellarg($newDbConfig['host']),
    escapeshellarg($newDbConfig['port']),
    escapeshellarg($newDbConfig['username']),
    escapeshellarg($newDbConfig['password']),
    escapeshellarg($newDbConfig['database']),
    escapeshellarg($backupFile)
);

echo "復元コマンドを実行中...\n";
echo "コマンド: " . $command . "\n\n";

// コマンドを実行
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ データベース復元が正常に完了しました！\n\n";
    
    // 復元結果の確認
    echo "復元結果を確認中...\n";
    try {
        $pdo->exec("USE `{$newDbConfig['database']}`");
        
        // テーブル一覧を取得
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "復元されたテーブル数: " . count($tables) . "\n";
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "  - $table: $count レコード\n";
        }
        
        echo "\n✅ 復元確認完了\n";
        
    } catch (PDOException $e) {
        echo "⚠️ 復元確認中にエラー: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ データベース復元に失敗しました\n";
    echo "エラーコード: " . $returnCode . "\n";
    echo "出力: " . implode("\n", $output) . "\n";
}

/**
 * ファイルサイズをフォーマット
 */
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

echo "\n=== 復元完了 ===\n";
echo "次のステップ:\n";
echo "1. config.phpのデータベース設定を更新\n";
echo "2. アプリケーションの動作確認\n";
echo "3. 必要に応じてユーザー権限の設定\n";
?>
