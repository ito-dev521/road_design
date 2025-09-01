<?php
// test_manual_upload.php
require_once 'config.php';
require_once 'database.php';

echo "<h2>マニュアルアップロードテスト</h2>";

// アップロードディレクトリの確認
$uploadDir = 'uploads/manuals/';
echo "<h3>1. アップロードディレクトリの確認</h3>";
if (is_dir($uploadDir)) {
    echo "✓ ディレクトリ '{$uploadDir}' は存在します<br>";
    echo "権限: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "<br>";
    echo "書き込み可能: " . (is_writable($uploadDir) ? 'はい' : 'いいえ') . "<br>";
} else {
    echo "✗ ディレクトリ '{$uploadDir}' が存在しません<br>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✓ ディレクトリを作成しました<br>";
    } else {
        echo "✗ ディレクトリの作成に失敗しました<br>";
    }
}

// データベース接続テスト
echo "<h3>2. データベース接続テスト</h3>";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    echo "✓ データベース接続成功<br>";
    
    // manualsテーブルの構造確認
    $stmt = $pdo->query("DESCRIBE manuals");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ manualsテーブルの構造:<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "✗ データベース接続エラー: " . $e->getMessage() . "<br>";
}

// ファイルアップロード処理のテスト
echo "<h3>3. ファイルアップロード処理テスト</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    echo "アップロードされたファイル情報:<br>";
    echo "- ファイル名: " . htmlspecialchars($file['name']) . "<br>";
    echo "- 一時ファイル: " . htmlspecialchars($file['tmp_name']) . "<br>";
    echo "- ファイルサイズ: " . $file['size'] . " bytes<br>";
    echo "- エラーコード: " . $file['error'] . "<br>";
    echo "- MIMEタイプ: " . htmlspecialchars($file['type']) . "<br>";
    
    // ファイル形式チェック
    $allowedExtensions = ['pdf', 'xlsx', 'xls', 'docx', 'doc'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    echo "- ファイル拡張子: " . $fileExtension . "<br>";
    echo "- 許可された拡張子か: " . (in_array($fileExtension, $allowedExtensions) ? 'はい' : 'いいえ') . "<br>";
    
    // ファイルサイズチェック
    $maxSize = 10 * 1024 * 1024; // 10MB
    echo "- ファイルサイズ制限: " . $maxSize . " bytes<br>";
    echo "- サイズ制限内か: " . ($file['size'] <= $maxSize ? 'はい' : 'いいえ') . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK && in_array($fileExtension, $allowedExtensions) && $file['size'] <= $maxSize) {
        // ファイルアップロードテスト
        $originalName = $file['name'];
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            echo "✓ ファイルアップロード成功: {$filePath}<br>";
            
            // データベース保存テスト
            try {
                $taskName = $_POST['task_name'] ?? 'テストタスク';
                $description = $_POST['description'] ?? 'テスト用マニュアル';
                $uploadedBy = 1; // テスト用
                
                $stmt = $pdo->prepare("
                    INSERT INTO manuals (task_name, file_name, original_name, description, file_size, file_path, uploaded_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                if ($stmt->execute([$taskName, $fileName, $originalName, $description, $file['size'], $filePath, $uploadedBy])) {
                    echo "✓ データベース保存成功<br>";
                    echo "挿入されたID: " . $pdo->lastInsertId() . "<br>";
                } else {
                    echo "✗ データベース保存失敗<br>";
                }
            } catch (Exception $e) {
                echo "✗ データベース保存エラー: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✗ ファイルアップロード失敗<br>";
        }
    } else {
        echo "✗ ファイル検証失敗<br>";
    }
} else {
    echo "ファイルアップロードテスト用フォーム:<br>";
    echo "<form method='POST' enctype='multipart/form-data'>";
    echo "<p>タスク名: <input type='text' name='task_name' value='テストタスク' required></p>";
    echo "<p>ファイル: <input type='file' name='test_file' accept='.pdf,.xlsx,.xls,.docx,.doc' required></p>";
    echo "<p>説明: <textarea name='description'>テスト用マニュアル</textarea></p>";
    echo "<p><input type='submit' value='テストアップロード'></p>";
    echo "</form>";
}

echo "<h3>4. 現在のマニュアル一覧</h3>";
try {
    $stmt = $pdo->query("SELECT id, task_name, file_name, file_size, created_at FROM manuals ORDER BY created_at DESC");
    $manuals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($manuals) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>タスク名</th><th>ファイル名</th><th>サイズ</th><th>作成日</th></tr>";
        foreach ($manuals as $manual) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($manual['id']) . "</td>";
            echo "<td>" . htmlspecialchars($manual['task_name']) . "</td>";
            echo "<td>" . htmlspecialchars($manual['file_name']) . "</td>";
            echo "<td>" . number_format($manual['file_size']) . " bytes</td>";
            echo "<td>" . htmlspecialchars($manual['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "マニュアルが登録されていません。<br>";
    }
} catch (Exception $e) {
    echo "✗ マニュアル一覧取得エラー: " . $e->getMessage() . "<br>";
}

echo "<h3>完了</h3>";
echo "<p><a href='settings.html'>設定画面に戻る</a></p>";
?>
