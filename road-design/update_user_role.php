<?php
// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTMLヘッダー
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>ユーザー権限更新</title></head><body>";
echo "<h1>ユーザー権限更新スクリプト</h1>";
echo "<pre>";

echo "=== スクリプト開始 ===\n";

try {
    echo "設定ファイル読み込み中...\n";
    require_once 'config.php';
    echo "データベースクラス読み込み中...\n";
    require_once 'database.php';
    
    echo "データベース接続中...\n";
    $db = new Database();
    $connection = $db->getConnection();
    echo "データベース接続成功\n";
    
    $email = 'ito@ii-stylelab.com';
    $newRole = 'manager';
    
    echo "=== ユーザー権限更新スクリプト ===\n";
    echo "対象メールアドレス: $email\n";
    echo "新しい権限: $newRole\n\n";
    
    // 現在のユーザー情報を確認
    $stmt = $connection->prepare("SELECT id, email, name, role, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ エラー: メールアドレス '$email' のユーザーが見つかりません。\n";
        echo "</pre></body></html>";
        exit(1);
    }
    
    echo "現在のユーザー情報:\n";
    echo "- ID: {$user['id']}\n";
    echo "- メールアドレス: {$user['email']}\n";
    echo "- 名前: {$user['name']}\n";
    echo "- 現在の権限: {$user['role']}\n";
    echo "- アクティブ: " . ($user['is_active'] ? 'はい' : 'いいえ') . "\n\n";
    
    if ($user['role'] === $newRole) {
        echo "ℹ️ 情報: ユーザーの権限は既に '$newRole' に設定されています。\n";
        echo "</pre>";
        echo "<p><a href='settings.html'>設定ページに戻る</a></p>";
        echo "</body></html>";
        exit(0);
    }
    
    // 権限を更新
    $updateStmt = $connection->prepare("UPDATE users SET role = ? WHERE email = ?");
    $result = $updateStmt->execute([$newRole, $email]);
    
    if ($result) {
        echo "✅ 成功: ユーザーの権限を '$newRole' に更新しました。\n";
        
        // 更新後の情報を確認
        $stmt->execute([$email]);
        $updatedUser = $stmt->fetch();
        
        echo "\n更新後のユーザー情報:\n";
        echo "- ID: {$updatedUser['id']}\n";
        echo "- メールアドレス: {$updatedUser['email']}\n";
        echo "- 名前: {$updatedUser['name']}\n";
        echo "- 新しい権限: {$updatedUser['role']}\n";
        echo "- アクティブ: " . ($updatedUser['is_active'] ? 'はい' : 'いいえ') . "\n";
        
    } else {
        echo "❌ エラー: ユーザー権限の更新に失敗しました。\n";
        echo "エラー情報: " . print_r($updateStmt->errorInfo(), true) . "\n";
        echo "</pre></body></html>";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "</pre></body></html>";
    exit(1);
}

echo "</pre>";
echo "<p><a href='settings.html'>設定ページに戻る</a></p>";
echo "</body></html>";
?>
