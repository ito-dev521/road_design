<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

session_start();

echo "<h1>管理画面アクセスフロー検証</h1>";

try {
    // テストユーザーの作成と確認
    $db = new Database();
    $pdo = $db->connect();

    echo "<h2>✅ 1. テストユーザー状態確認</h2>";
    $users = $pdo->query("SELECT id, email, name, role, is_active, last_login, password_hash FROM users WHERE role = 'manager' OR email IN ('admin@ii-stylelab.com', 'tech@ii-stylelab.com', 'staff@ii-stylelab.com') ORDER BY role DESC, email");
    $userCount = $users->rowCount();

    if ($userCount > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr><th>ID</th><th>メールアドレス</th><th>名前</th><th>ロール</th><th>アクティブ</th><th>最終ログイン</th><th>パスワード確認</th></tr>";

        while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
            $roleClass = ($user['role'] === 'manager') ? 'style="background-color: #d4edda;"' : '';
            $testPassword = '';

            // パスワード確認
            if ($user['email'] === 'admin@ii-stylelab.com') {
                $testPassword = 'admin123';
            } elseif ($user['email'] === 'tech@ii-stylelab.com') {
                $testPassword = 'tech123';
            } elseif ($user['email'] === 'staff@ii-stylelab.com') {
                $testPassword = 'staff123';
            }

            $passwordCheck = '';
            if ($testPassword) {
                $passwordCheck = password_verify($testPassword, $user['password_hash']) ? '✅' : '❌';
            }

            echo "<tr {$roleClass}>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($user['last_login'] ? $user['last_login'] : '未ログイン') . "</td>";
            echo "<td>{$passwordCheck}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ テストユーザーが見つかりません</p>";
    }

    // セッション情報
    echo "<h2>📋 2. 現在のセッション情報</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ ログイン中: ユーザーID = {$_SESSION['user_id']}</p>";
        echo "<p>ユーザー名: " . ($_SESSION['user_name'] ?? '不明') . "</p>";
        echo "<p>ロール: " . ($_SESSION['user_role'] ?? '不明') . "</p>";
        echo "<p>ログイン時刻: " . ($_SESSION['login_time'] ? date('Y-m-d H:i:s', $_SESSION['login_time']) : '不明') . "</p>";

        // 管理者権限確認
        if (($_SESSION['user_role'] ?? '') === 'manager') {
            echo "<p style='color: green; font-weight: bold;'>✅ 管理者権限が確認されました！</p>";
            echo "<p><a href='admin.html' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>管理画面へ</a></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ 管理者権限がありません</p>";
            echo "<p>現在のロール: " . ($_SESSION['user_role'] ?? '不明') . "</p>";
        }
    } else {
        echo "<p>⚠️ 未ログイン状態</p>";
        echo "<p><a href='login.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>ログイン画面へ</a></p>";
    }

    // ログインAPIテスト
    echo "<h2>🔍 3. ログインAPIテスト</h2>";
    $auth = new Auth();

    $testAccounts = [
        ['admin@ii-stylelab.com', 'admin123', '管理者'],
        ['tech@ii-stylelab.com', 'tech123', '技術者'],
        ['staff@ii-stylelab.com', 'staff123', 'スタッフ']
    ];

    foreach ($testAccounts as $account) {
        echo "<h3>{$account[2]}アカウントテスト</h3>";

        // 新しいセッションでテスト
        session_write_close();
        session_start();

        $result = $auth->login($account[0], $account[1]);

        if ($result['success']) {
            echo "<p style='color: green;'>✅ ログイン成功: {$account[0]}</p>";
            echo "<p>ユーザー情報: " . json_encode($result['user'], JSON_UNESCAPED_UNICODE) . "</p>";

            if (isset($result['user']['role']) && $result['user']['role'] === 'manager') {
                echo "<p style='color: green; font-weight: bold;'>🎯 管理画面アクセス可能！</p>";
            } else {
                echo "<p style='color: blue;'>📊 メインページアクセス</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ ログイン失敗: {$account[0]}</p>";
            echo "<p>エラー: {$result['message']}</p>";
        }

        // セッションクリーンアップ
        $auth->logout();
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ エラー発生</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
