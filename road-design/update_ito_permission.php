<?php
/**
 * ito@ii-stylelab.comの権限を技術者に変更するスクリプト
 */

header('Content-Type: text/html; charset=utf-8');

try {
    require_once 'config.php';
    require_once 'database.php';
    
    echo "<h1>ユーザー権限更新</h1>";
    
    $db = new Database();
    $connection = $db->connect();
    
    if (!$connection) {
        throw new Exception('データベース接続に失敗しました');
    }
    
    $email = 'ito@ii-stylelab.com';
    $newRole = 'manager';
    
    echo "<h2>更新対象ユーザー</h2>";
    echo "<p><strong>メールアドレス:</strong> {$email}</p>";
    echo "<p><strong>新しい権限:</strong> 管理者 (manager)</p>";
    
    // 現在のユーザー情報を確認
    echo "<h3>更新前の情報</h3>";
    $stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("ユーザーが見つかりません: {$email}");
    }
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>項目</th><th>値</th></tr>";
    echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
    echo "<tr><td>名前</td><td>{$user['name']}</td></tr>";
    echo "<tr><td>メール</td><td>{$user['email']}</td></tr>";
    echo "<tr><td>現在の権限</td><td>{$user['role']}</td></tr>";
    echo "<tr><td>ステータス</td><td>" . ($user['is_active'] ? '有効' : '無効') . "</td></tr>";
    echo "<tr><td>作成日</td><td>{$user['created_at']}</td></tr>";
    echo "</table>";
    
    // 権限を更新
    echo "<h3>権限更新実行</h3>";
    $stmt = $connection->prepare("UPDATE users SET role = ? WHERE email = ?");
    
    if ($stmt->execute([$newRole, $email])) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb;'>";
        echo "<h4>✅ 権限更新成功</h4>";
        echo "<p>ユーザー <strong>{$email}</strong> の権限を <strong>管理者 (manager)</strong> に更新しました。</p>";
        echo "</div>";
        
        // 更新後の情報を確認
        echo "<h3>更新後の情報</h3>";
        $stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $updatedUser = $stmt->fetch();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>項目</th><th>値</th></tr>";
        echo "<tr><td>ID</td><td>{$updatedUser['id']}</td></tr>";
        echo "<tr><td>名前</td><td>{$updatedUser['name']}</td></tr>";
        echo "<tr><td>メール</td><td>{$updatedUser['email']}</td></tr>";
        echo "<tr><td>新しい権限</td><td style='background: #d4edda;'><strong>{$updatedUser['role']}</strong></td></tr>";
        echo "<tr><td>ステータス</td><td>" . ($updatedUser['is_active'] ? '有効' : '無効') . "</td></tr>";
        echo "<tr><td>更新日時</td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
        echo "</table>";
        
        // セッションクリアの案内
        echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #ffeaa7;'>";
        echo "<h4>⚠️ 重要な注意</h4>";
        echo "<p>権限を変更しました。変更を反映するには以下の手順を実行してください：</p>";
        echo "<ol>";
        echo "<li>現在のセッションをログアウト</li>";
        echo "<li>再度ログイン</li>";
        echo "<li>右上の表示を確認</li>";
        echo "</ol>";
        echo "<p><a href='logout.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>ログアウト</a></p>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb;'>";
        echo "<h4>❌ 権限更新失敗</h4>";
        echo "<p>データベースの更新に失敗しました。</p>";
        echo "<p>エラー情報: " . print_r($stmt->errorInfo(), true) . "</p>";
        echo "</div>";
    }
    
    // 全ユーザーの現在の権限一覧
    echo "<h3>全ユーザーの現在の権限</h3>";
    $stmt = $connection->query("SELECT id, name, email, role, is_active FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th>ID</th><th>名前</th><th>メール</th><th>権限</th><th>ステータス</th>";
    echo "</tr>";
    
    foreach ($allUsers as $user) {
        $status = $user['is_active'] ? '有効' : '無効';
        $roleText = [
            'manager' => '管理者',
            'technical' => '技術者',
            'general' => '一般スタッフ'
        ][$user['role']] ?? $user['role'];
        
        $rowStyle = $user['email'] === $email ? "style='background: #d4edda;'" : "";
        
        echo "<tr {$rowStyle}>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><strong>{$roleText}</strong></td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<h4>❌ エラー</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
