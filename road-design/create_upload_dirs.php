<?php
// create_upload_dirs.php
echo "<h2>アップロードディレクトリの作成</h2>";

$directories = [
    'uploads',
    'uploads/manuals'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✓ ディレクトリ '{$dir}' を作成しました<br>";
        } else {
            echo "✗ ディレクトリ '{$dir}' の作成に失敗しました<br>";
        }
    } else {
        echo "✓ ディレクトリ '{$dir}' は既に存在します<br>";
    }
}

echo "<h3>完了</h3>";
echo "<p>アップロードディレクトリの作成が完了しました。</p>";
echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
?>
