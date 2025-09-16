<?php
/**
 * 強制ログアウトスクリプト
 * セッションを完全にクリアし、ログインページにリダイレクト
 */

// セッションを開始
session_start();

// セッションを完全に破棄
session_unset();
session_destroy();

// セッションクッキーを削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// キャッシュを無効化
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ログインページにリダイレクト
header('Location: login.html?message=' . urlencode('セッションをクリアしました。再度ログインしてください。') . '&t=' . time());
exit;
?>
