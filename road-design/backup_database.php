<?php
/**
 * データベースバックアップスクリプト
 * 道路詳細設計管理システム用
 */

// 設定ファイルを読み込み
require_once 'config.php';

// バックアップ設定
$backupDir = __DIR__ . '/backups/';
$backupFile = $backupDir . 'road_design_backup_' . date('Y-m-d_H-i-s') . '.sql';

// バックアップディレクトリを作成
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "データベースバックアップを開始します...\n";
echo "データベース: " . DB_NAME . "\n";
echo "ホスト: " . DB_HOST . "\n";
echo "バックアップファイル: " . $backupFile . "\n\n";

// mysqldumpコマンドを構築
$command = sprintf(
    'mysqldump -h %s -P %s -u %s -p%s --single-transaction --routines --triggers --add-drop-table --create-options --disable-keys --extended-insert --quick --set-charset %s > %s',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_PORT),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($backupFile)
);

echo "実行コマンド: " . $command . "\n\n";

// コマンドを実行
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ バックアップが正常に完了しました！\n";
    echo "ファイル: " . $backupFile . "\n";
    echo "サイズ: " . formatBytes(filesize($backupFile)) . "\n";
    
    // バックアップファイルの整合性をチェック
    if (checkBackupIntegrity($backupFile)) {
        echo "✅ バックアップファイルの整合性チェック完了\n";
    } else {
        echo "⚠️ バックアップファイルに問題がある可能性があります\n";
    }
} else {
    echo "❌ バックアップに失敗しました\n";
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

/**
 * バックアップファイルの整合性をチェック
 */
function checkBackupIntegrity($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    $content = file_get_contents($file);
    
    // 基本的なSQLファイルの構造をチェック
    $checks = [
        'CREATE TABLE' => strpos($content, 'CREATE TABLE') !== false,
        'INSERT INTO' => strpos($content, 'INSERT INTO') !== false,
        'DROP TABLE' => strpos($content, 'DROP TABLE') !== false,
        '/*!40101 SET' => strpos($content, '/*!40101 SET') !== false
    ];
    
    $passedChecks = array_filter($checks);
    return count($passedChecks) >= 3; // 最低3つのチェックをパス
}

echo "\n=== バックアップ完了 ===\n";
echo "次のステップ:\n";
echo "1. バックアップファイルを新しいサーバーに転送\n";
echo "2. 新しいサーバーでデータベースを作成\n";
echo "3. 以下のコマンドで復元:\n";
echo "   mysql -h [新しいホスト] -u [新しいユーザー] -p [新しいDB名] < " . basename($backupFile) . "\n";
?>
