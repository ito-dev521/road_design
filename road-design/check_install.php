<?php
echo "<h1>道路詳細設計管理システム - インストール確認</h1>";

// 必要なファイルの存在確認
$required_files = [
    'config.php',
    'auth.php',
    'database.php',
    'api.php',
    'index.html',
    'login.html'
];

echo "<h2>必須ファイルチェック</h2>";
echo "<ul>";
$all_files_exist = true;
foreach ($required_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<li style='color: green;'>✓ $file (権限: $perms)</li>";
    } else {
        echo "<li style='color: red;'>✗ $file (ファイルが存在しません)</li>";
        $all_files_exist = false;
    }
}
echo "</ul>";

if (!$all_files_exist) {
    echo "<p style='color: red;'><strong>必須ファイルが不足しています。すべてのファイルをアップロードしてください。</strong></p>";
}

// データベース接続テスト
echo "<h2>データベース接続テスト</h2>";
try {
    require_once 'config.php';
    require_once 'database.php';

    $db = new Database();
    $connection = $db->getConnection();

    if ($connection) {
        echo "<p style='color: green;'>✓ データベース接続成功</p>";

        // テーブル存在確認
        $stmt = $connection->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<h3>作成済みテーブル (" . count($tables) . "個)</h3>";
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";

            // ユーザー数確認
            try {
                $stmt = $connection->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p>登録ユーザー数: " . $result['count'] . "人</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>ユーザー数取得失敗: " . $e->getMessage() . "</p>";
            }

        } else {
            echo "<p style='color: orange;'>テーブルが存在しません。install.phpを実行してください。</p>";
        }

    } else {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ データベースエラー: " . $e->getMessage() . "</p>";
}

// 次のステップの案内
echo "<h2>次のステップ</h2>";
echo "<ol>";
if (!$all_files_exist) {
    echo "<li style='color: red;'>すべてのファイルをアップロードしてください</li>";
} else {
    echo "<li style='color: green;'>✓ 必須ファイルはすべて揃っています</li>";
}

if (isset($tables) && count($tables) == 0) {
    echo "<li>データベーステーブルを作成してください: <a href='install.php'>install.php</a></li>";
} else {
    echo "<li style='color: green;'>✓ データベーステーブルは作成済みです</li>";
}

echo "<li>システムにアクセス: <a href='index.html'>メインページ</a> | <a href='login.html'>ログインページ</a></li>";
echo "<li>詳細診断: <a href='debug.php'>デバッグページ</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>サーバー情報:</strong> エックスサーバー (sv12546.xserver.jp)</p>";
echo "<p><strong>PHPバージョン:</strong> " . phpversion() . "</p>";
?>
