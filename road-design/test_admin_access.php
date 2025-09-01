<?php
// 管理画面アクセステストファイル
session_start();

echo "<h1>管理画面アクセステスト</h1>";

echo "<h2>現在のセッション状態</h2>";
echo "<ul>";
echo "<li>セッションID: " . session_id() . "</li>";
echo "<li>ユーザーID: " . ($_SESSION['user_id'] ?? '未設定') . "</li>";
echo "<li>ユーザー名: " . ($_SESSION['user_name'] ?? '未設定') . "</li>";
echo "<li>ユーザー権限: " . ($_SESSION['user_role'] ?? '未設定') . "</li>";
echo "</ul>";

echo "<h2>管理画面ファイル存在確認</h2>";
$adminFiles = [
    'admin.html',
    'assets/js/admin.js',
    'assets/css/admin.css'
];

echo "<ul>";
foreach ($adminFiles as $file) {
    if (file_exists($file)) {
        echo "<li style='color: green;'>✓ $file (存在します)</li>";
    } else {
        echo "<li style='color: red;'>✗ $file (存在しません)</li>";
    }
}
echo "</ul>";

echo "<h2>権限チェック</h2>";
$userRole = $_SESSION['user_role'] ?? null;
if ($userRole === 'manager') {
    echo "<p style='color: green;'>✓ 管理者権限があります。管理画面にアクセスできます。</p>";
    echo "<p><a href='admin.html' class='btn'>管理画面にアクセス</a></p>";
} elseif ($userRole) {
    echo "<p style='color: orange;'>⚠ 現在の権限: $userRole</p>";
    echo "<p>管理者権限が必要です。管理者アカウントでログインしてください。</p>";
} else {
    echo "<p style='color: red;'>✗ ログインしていません。</p>";
    echo "<p><a href='login.html'>ログイン</a></p>";
}

echo "<h2>JavaScriptテスト関数</h2>";
echo "<p>ブラウザのコンソールで以下の関数を実行してください：</p>";
echo "<pre>
// 管理画面ボタンのテスト
testAdminButton()

// 直接管理画面に遷移
goToAdmin()

// デバッグ情報表示
debugRoadDesign()
</pre>";

echo "<h2>手動テスト</h2>";
echo "<ul>";
echo "<li><a href='admin.html'>直接管理画面アクセス</a></li>";
echo "<li><a href='index.html'>メインページに戻る</a></li>";
echo "<li><a href='debug.php'>デバッグページ</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>テスト手順:</strong></p>";
echo "<ol>";
echo "<li>管理者権限でログインしていることを確認</li>";
echo "<li>ブラウザのコンソールを開く（F12）</li>";
echo "<li>testAdminButton() を実行してボタンの状態を確認</li>";
echo "<li>goToAdmin() を実行して直接遷移テスト</li>";
echo "<li>必要に応じて debugRoadDesign() で詳細確認</li>";
echo "</ol>";
?>
