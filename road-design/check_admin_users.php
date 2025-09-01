<?php
require_once 'config.php';
require_once 'database.php';

echo "<h1>管理画面アクセス診断</h1>";

try {
    // データベース接続
    $db = new Database();
    $pdo = $db->connect();

    if (!$pdo) {
        throw new Exception('データベース接続に失敗しました');
    }

    // ユーザーテーブルが存在するか確認
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $result->rowCount() > 0;

    if (!$tableExists) {
        echo "<h2 style='color: red;'>❌ 問題: usersテーブルが存在しません</h2>";
        echo "<p>データベースのインストールが完了していない可能性があります。</p>";
        echo "<p><a href='install.php'>install.php</a>を実行してください。</p>";
        exit;
    }

    // ユーザーテーブルの構造を確認
    echo "<h2>✅ usersテーブルの構造</h2>";
    $columns = $pdo->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>フィールド</th><th>タイプ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 登録されているユーザーを確認
    echo "<h2>✅ 登録されているユーザー</h2>";
    $users = $pdo->query("SELECT id, email, name, role, is_active, created_at, last_login FROM users ORDER BY id");
    $userCount = $users->rowCount();

    if ($userCount == 0) {
        echo "<h3 style='color: red;'>❌ 問題: ユーザーが登録されていません</h3>";
        echo "<p>テストユーザーを作成する必要があります。</p>";

        // テストユーザーを作成
        echo "<h3>🔧 テストユーザー作成</h3>";
        $testUsers = [
            ['admin@ii-stylelab.com', 'admin123', '管理者', 'manager'],
            ['tech@ii-stylelab.com', 'tech123', '技術者', 'technical'],
            ['staff@ii-stylelab.com', 'staff123', '一般スタッフ', 'general']
        ];

        foreach ($testUsers as $user) {
            $hash = password_hash($user[1], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, role, is_active) VALUES (?, ?, ?, ?, TRUE) ON DUPLICATE KEY UPDATE password_hash = ?, name = ?, role = ?");
            $result = $stmt->execute([$user[0], $hash, $user[2], $user[3], $hash, $user[2], $user[3]]);

            if ($result) {
                echo "✅ {$user[2]}ユーザーを作成しました: {$user[0]}<br>";
            } else {
                echo "❌ {$user[2]}ユーザーの作成に失敗しました<br>";
            }
        }

        // 作成後に再取得
        $users = $pdo->query("SELECT id, email, name, role, is_active, created_at, last_login FROM users ORDER BY id");
        $userCount = $users->rowCount();
    }

    if ($userCount > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>メールアドレス</th><th>名前</th><th>ロール</th><th>アクティブ</th><th>作成日</th><th>最終ログイン</th></tr>";

        while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
            $roleClass = ($user['role'] === 'manager') ? 'style="background-color: #d4edda;"' : '';
            echo "<tr {$roleClass}>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "<td>" . ($user['last_login'] ? $user['last_login'] : '未ログイン') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // 管理者権限を持つユーザーを確認
        $managers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'manager' AND is_active = TRUE");
        $managerCount = $managers->fetch(PDO::FETCH_ASSOC)['count'];

        echo "<h3>👑 管理者権限の確認</h3>";
        if ($managerCount > 0) {
            echo "<p style='color: green;'>✅ {$managerCount}人の管理者ユーザーが存在します。</p>";
            echo "<p><strong>管理者アカウント:</strong> admin@ii-stylelab.com / admin123</p>";
            echo "<p><a href='login.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>ログイン画面へ</a></p>";
        } else {
            echo "<p style='color: red;'>❌ 管理者権限を持つユーザーが存在しません。</p>";
        }
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ エラー発生</h2>";
    echo "<p>{$e->getMessage()}</p>";
}
?>
