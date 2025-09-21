<?php
require_once 'auth.php';

$auth = new Auth();
$result = $auth->logout();

// Ajaxリクエストの場合はJSONを返す
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// 通常のリクエストの場合はログイン画面にリダイレクト（キャッシュ無効化）
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: login.html?message=' . urlencode('ログアウトしました') . '&t=' . time());
exit;
?>