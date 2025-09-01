<?php
echo "<h2>index.html用APIエンドポイントテスト</h2>";

// エラー表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>1. 基本情報</h3>";
echo "<p>PHP バージョン: " . phpversion() . "</p>";
echo "<p>現在のディレクトリ: " . getcwd() . "</p>";

echo "<h3>2. ファイル存在確認</h3>";
$files = ['config.php', 'auth.php', 'database.php', 'api.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ {$file} 存在</p>";
    } else {
        echo "<p style='color: red;'>✗ {$file} 存在しない</p>";
    }
}

echo "<h3>3. データベース接続テスト</h3>";
try {
    require_once 'config.php';
    require_once 'database.php';

    $db = new Database();
    $connection = $db->getConnection();

    if ($connection) {
        echo "<p style='color: green;'>✓ データベース接続成功</p>";

        // テーブル存在確認
        $tables = ['users', 'phases', 'task_templates', 'manuals', 'projects', 'tasks'];
        foreach ($tables as $table) {
            $stmt = $connection->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->fetch()) {
                echo "<p style='color: green;'>✓ {$table} テーブル存在</p>";
            } else {
                echo "<p style='color: red;'>✗ {$table} テーブル存在しない</p>";
            }
        }

        // データ件数確認
        foreach ($tables as $table) {
            try {
                $stmt = $connection->query("SELECT COUNT(*) as count FROM {$table}");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p>{$table}: {$count}件</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ {$table} カウント失敗: " . $e->getMessage() . "</p>";
            }
        }

    } else {
        echo "<p style='color: red;'>✗ データベース接続失敗</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
}

echo "<h3>4. API直接テスト</h3>";
echo "<p>index.htmlで使用する各エンドポイントをテストします</p>";

// 個別エンドポイントテスト
$endpoints = [
    'projects' => 'プロジェクト一覧',
    'user/profile' => 'ユーザープロフィール'
];

foreach ($endpoints as $endpoint => $description) {
    echo "<h4>{$description} ({$endpoint})</h4>";

    try {
        // セッション開始
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // テスト用セッション設定
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'manager';

        // APIコントローラーを直接テスト
        require_once 'api.php';
        $api = new ApiController();

        // パスを設定
        $_GET['path'] = $endpoint;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        echo "<p>APIコントローラー呼び出し中...</p>";

        // 出力バッファリング開始
        ob_start();
        $result = $api->handleRequest();
        $output = ob_get_clean();

        if ($output) {
            echo "<p style='color: orange;'>⚠ 出力バッファに内容があります:</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }

        if ($result) {
            echo "<p style='color: green;'>✓ API呼び出し成功</p>";
            echo "<p>結果:</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ API呼び出し失敗</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ エラー: " . $e->getMessage() . "</p>";
        echo "<p>ファイル: " . $e->getFile() . "</p>";
        echo "<p>行: " . $e->getLine() . "</p>";
    }

    echo "<hr>";
}

echo "<h3>5. データベース詳細テスト</h3>";

try {
    // プロジェクトデータの詳細確認
    echo "<h4>プロジェクトデータ詳細</h4>";
    $stmt = $connection->query("SELECT * FROM projects LIMIT 3");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($projects) {
        echo "<p>プロジェクト数: " . count($projects) . "</p>";
        foreach ($projects as $i => $project) {
            echo "<h5>プロジェクト " . ($i + 1) . "</h5>";
            echo "<ul>";
            foreach ($project as $key => $value) {
                echo "<li><strong>{$key}:</strong> " . htmlspecialchars($value) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>プロジェクトデータがありません</p>";
    }

    // タスクデータの詳細確認
    echo "<h4>タスクデータ詳細</h4>";
    $stmt = $connection->query("SELECT * FROM tasks LIMIT 3");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($tasks) {
        echo "<p>タスク数: " . count($tasks) . "</p>";
        foreach ($tasks as $i => $task) {
            echo "<h5>タスク " . ($i + 1) . "</h5>";
            echo "<ul>";
            foreach ($task as $key => $value) {
                echo "<li><strong>{$key}:</strong> " . htmlspecialchars($value) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>タスクデータがありません</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ データベース詳細テストエラー: " . $e->getMessage() . "</p>";
}
?>
