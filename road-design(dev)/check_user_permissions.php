<?php
/**
 * ユーザー権限確認・更新スクリプト
 */

header('Content-Type: text/html; charset=utf-8');

try {
    require_once 'config.php';
    require_once 'database.php';
    
    echo "<h1>ユーザー権限確認・更新</h1>";
    
    $db = new Database();
    $connection = $db->connect();
    
    if (!$connection) {
        throw new Exception('データベース接続に失敗しました');
    }
    
    echo "<h2>現在のユーザー一覧</h2>";
    
    // 全ユーザーの情報を取得
    $stmt = $connection->query("SELECT id, email, name, role, is_active, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>名前</th><th>メール</th><th>役割</th><th>ステータス</th><th>作成日</th><th>操作</th></tr>";
    
    foreach ($users as $user) {
        $status = $user['is_active'] ? '有効' : '無効';
        $roleText = [
            'manager' => '管理者',
            'technical' => '技術者', 
            'general' => '一般スタッフ'
        ][$user['role']] ?? $user['role'];
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$roleText}</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "<td>";
        echo "<a href='?action=update_role&id={$user['id']}&role=manager'>管理者</a> | ";
        echo "<a href='?action=update_role&id={$user['id']}&role=technical'>技術者</a> | ";
        echo "<a href='?action=update_role&id={$user['id']}&role=general'>一般</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 権限更新処理
    if (isset($_GET['action']) && $_GET['action'] === 'update_role' && isset($_GET['id']) && isset($_GET['role'])) {
        $userId = (int)$_GET['id'];
        $newRole = $_GET['role'];
        
        if (in_array($newRole, ['manager', 'technical', 'general'])) {
            $stmt = $connection->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$newRole, $userId])) {
                echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "✅ ユーザーID {$userId} の権限を「{$newRole}」に更新しました。";
                echo "</div>";
                
                // ページをリロードして変更を反映
                echo "<script>setTimeout(() => location.reload(), 2000);</script>";
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "❌ 権限の更新に失敗しました。";
                echo "</div>";
            }
        }
    }
    
    // 特定ユーザーの詳細確認
    echo "<h2>特定ユーザーの確認</h2>";
    echo "<form method='GET'>";
    echo "<input type='hidden' name='action' value='check_user'>";
    echo "<input type='email' name='email' placeholder='メールアドレスを入力' value='ito@ii-stylelab.com'>";
    echo "<button type='submit'>確認</button>";
    echo "</form>";
    
    if (isset($_GET['action']) && $_GET['action'] === 'check_user' && isset($_GET['email'])) {
        $email = $_GET['email'];
        $stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<h3>ユーザー詳細: {$user['email']}</h3>";
            echo "<pre>" . print_r($user, true) . "</pre>";
            
            // セッション情報も確認
            echo "<h3>セッション情報</h3>";
            session_start();
            echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        } else {
            echo "<p>ユーザーが見つかりません: {$email}</p>";
        }
    }
    
    // セッションクリア機能
    echo "<h2>セッション管理</h2>";
    echo "<a href='?action=clear_session' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>セッションをクリア</a>";
    
    if (isset($_GET['action']) && $_GET['action'] === 'clear_session') {
        session_start();
        session_destroy();
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ セッションをクリアしました。再度ログインしてください。";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ エラー: " . $e->getMessage();
    echo "</div>";
}
?>
