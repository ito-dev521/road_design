<?php
/**
 * 強制ログアウトAPI
 * セッションを完全にクリア
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
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

    echo json_encode([
        'success' => true,
        'message' => 'セッションをクリアしました'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'セッションクリア中にエラーが発生しました: ' . $e->getMessage()
    ]);
}
?>
