<?php
require_once 'config.php';

// セキュリティ: インストール後はこのファイルを削除すること
if (file_exists('INSTALLATION_COMPLETE.txt')) {
    die('Installation already completed. Please delete this file for security.');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // データベース接続テスト
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // データベース作成（存在しない場合）
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // SQLファイル読み込み・実行
        $sql = file_get_contents('database_schema.sql');
        if ($sql === false) {
            throw new Exception('database_schema.sql file not found');
        }
        
        // SQLを個別のクエリに分割して実行
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^\s*--/', $query)) {
                $pdo->exec($query);
            }
        }
        
        // パスワードハッシュを更新（デフォルトパスワードをハッシュ化）
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $techHash = password_hash('tech123', PASSWORD_DEFAULT);
        $staffHash = password_hash('staff123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$adminHash, 'admin@ii-stylelab.com']);
        $stmt->execute([$techHash, 'tech@ii-stylelab.com']);
        $stmt->execute([$staffHash, 'staff@ii-stylelab.com']);
        
        // インストール完了マーク
        file_put_contents('INSTALLATION_COMPLETE.txt', date('Y-m-d H:i:s'));
        
        $message = 'データベースのセットアップが完了しました！<br><br>';
        $message .= '<strong>初期ログイン情報:</strong><br>';
        $message .= '管理者: admin@ii-stylelab.com / admin123<br>';
        $message .= '技術者: tech@ii-stylelab.com / tech123<br>';
        $message .= '一般スタッフ: staff@ii-stylelab.com / staff123<br><br>';
        $message .= '<strong>セキュリティのため、このinstall.phpファイルを削除してください。</strong><br>';
        $message .= '<a href="login.html" class="btn">ログイン画面へ</a>';
        
    } catch (Exception $e) {
        $error = 'インストールエラー: ' . $e->getMessage();
        error_log("Installation error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>道路詳細設計管理システム - インストール</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        
        .status {
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            margin-bottom: 30px;
        }
        
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .config-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .config-info h3 {
            margin-top: 0;
            color: #495057;
        }
        
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 2px;
            font-family: 'Courier New', monospace;
        }
        
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        li {
            margin: 5px 0;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛣️ 道路詳細設計管理システム</h1>
        <h2 class="text-center">データベースセットアップ</h2>
        
        <?php if ($message): ?>
            <div class="status success">
                <?php echo $message; ?>
            </div>
        <?php elseif ($error): ?>
            <div class="status error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$message): ?>
            <div class="info">
                <strong>インストール前の確認事項:</strong><br>
                このインストールでは以下の処理を実行します：
                <ul>
                    <li>データベース <code><?php echo htmlspecialchars(DB_NAME); ?></code> の作成</li>
                    <li>必要なテーブルの作成</li>
                    <li>初期データの投入</li>
                    <li>初期ユーザーアカウントの作成</li>
                </ul>
            </div>
            
            <div class="config-info">
                <h3>現在の設定</h3>
                <p><strong>データベースホスト:</strong> <?php echo htmlspecialchars(DB_HOST); ?></p>
                <p><strong>データベース名:</strong> <?php echo htmlspecialchars(DB_NAME); ?></p>
                <p><strong>ユーザー名:</strong> <?php echo htmlspecialchars(DB_USER); ?></p>
                <p><strong>文字セット:</strong> <?php echo htmlspecialchars(DB_CHARSET); ?></p>
            </div>
            
            <form method="post" class="text-center">
                <button type="submit" name="install" class="btn">
                    インストール開始
                </button>
            </form>
            
            <div class="info" style="margin-top: 30px;">
                <strong>注意:</strong><br>
                インストール完了後は、セキュリティのため必ずこの <code>install.php</code> ファイルを削除してください。
            </div>
        <?php endif; ?>
    </div>
</body>
</html>