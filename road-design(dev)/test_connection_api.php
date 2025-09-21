<?php
/**
 * データベース接続テスト用API
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'config.php';
    require_once 'database.php';
    
    $action = $_GET['action'] ?? 'connection';
    $result = ['success' => false, 'message' => '', 'data' => null];
    
    $db = new Database();
    $connection = $db->connect();
    
    if (!$connection) {
        throw new Exception('データベース接続に失敗しました');
    }
    
    switch ($action) {
        case 'connection':
            // 基本的な接続テスト
            $stmt = $connection->query("SELECT VERSION() as version, DATABASE() as current_db, NOW() as current_time");
            $dbInfo = $stmt->fetch();
            
            $result = [
                'success' => true,
                'message' => 'データベース接続成功',
                'data' => [
                    'version' => $dbInfo['version'],
                    'current_database' => $dbInfo['current_db'],
                    'current_time' => $dbInfo['current_time'],
                    'charset' => 'utf8mb4'
                ]
            ];
            break;
            
        case 'tables':
            // テーブル一覧の取得
            $stmt = $connection->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $tableInfo = [];
            foreach ($tables as $table) {
                $stmt = $connection->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch()['count'];
                $tableInfo[] = [
                    'name' => $table,
                    'record_count' => $count
                ];
            }
            
            $result = [
                'success' => true,
                'message' => 'テーブル一覧取得成功',
                'data' => [
                    'tables' => $tableInfo,
                    'total_tables' => count($tables)
                ]
            ];
            break;
            
        case 'users':
            // ユーザーデータの確認
            $stmt = $connection->query("SELECT id, email, name, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10");
            $users = $stmt->fetchAll();
            
            $result = [
                'success' => true,
                'message' => 'ユーザーデータ取得成功',
                'data' => [
                    'users' => $users,
                    'total_users' => count($users)
                ]
            ];
            break;
            
        default:
            throw new Exception('無効なアクションです');
    }
    
} catch (Exception $e) {
    $result = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
    
    // エラーログに記録
    error_log("Database test error: " . $e->getMessage());
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
