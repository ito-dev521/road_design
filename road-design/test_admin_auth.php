<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

session_start();

echo "<h1>管理者認証テスト</h1>";

try {
    // セッション情報の確認
    echo "<h2>📋 セッション情報</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ セッション有効: ユーザーID = {$_SESSION['user_id']}</p>";

        // データベースからユーザー情報を取得
        $db = new Database();
        $pdo = $db->connect();

        $stmt = $pdo->prepare("SELECT id, email, name, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<h2>👤 ログイン中のユーザー情報</h2>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>項目</th><th>値</th></tr>";
            echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
            echo "<tr><td>メールアドレス</td><td>{$user['email']}</td></tr>";
            echo "<tr><td>名前</td><td>{$user['name']}</td></tr>";
            echo "<tr><td>ロール</td><td>{$user['role']}</td></tr>";
            echo "<tr><td>アクティブ</td><td>" . ($user['is_active'] ? '✅' : '❌') . "</td></tr>";
            echo "</table>";

            // 管理者権限チェック
            if ($user['role'] === 'manager' && $user['is_active']) {
                echo "<h2 style='color: green;'>✅ 管理者権限が確認されました！</h2>";
                echo "<p>管理画面にアクセスできます。</p>";
                echo "<p><a href='admin.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>管理画面へ</a></p>";
            } else {
                echo "<h2 style='color: red;'>❌ 管理者権限がありません</h2>";
                echo "<p>現在のロール: {$user['role']}</p>";
                if (!$user['is_active']) {
                    echo "<p style='color: red;'>⚠️ アカウントが無効化されています</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ セッションのユーザー情報が見つかりません</p>";
            session_destroy();
        }
    } else {
        echo "<p style='color: orange;'>⚠️ セッションがありません（ログインが必要です）</p>";
        echo "<p><a href='login.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>ログイン画面へ</a></p>";
    }

    // API check_authをシミュレーション
    echo "<h2>🔍 API check_auth テスト</h2>";
    if (isset($_SESSION['user_id'])) {
        $auth = new Auth();
        $result = $auth->checkAuth();

        if ($result['success']) {
            echo "<p style='color: green;'>✅ API認証成功</p>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ API認証失敗</p>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
    } else {
        echo "<p>セッションがないためAPIテストをスキップ</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ エラー発生</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
