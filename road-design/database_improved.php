<?php
require_once 'config.php';

class Database {
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $options;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        
        // 接続オプションを設定（タイムアウト対策）
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_PERSISTENT => false // 永続接続を無効
        ];
    }
    
    public function connect() {
        try {
            $this->connection = null;
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=" . $this->charset;
            $this->connection = new PDO($dsn, $this->username, $this->password, $this->options);
            
            // 接続テスト
            $this->connection->query("SELECT 1");
            
            return $this->connection;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            
            // より詳細なエラー情報
            $errorInfo = [
                'error' => $e->getMessage(),
                'host' => $this->host,
                'dbname' => $this->dbname,
                'user' => $this->username
            ];
            error_log("Connection details: " . json_encode($errorInfo));
            
            return false;
        }
    }
    
    public function getConnection() {
        if (!$this->connection) {
            $this->connect();
        }
        
        // 接続が生きているかチェック
        try {
            if ($this->connection) {
                $this->connection->query("SELECT 1");
            }
        } catch (PDOException $e) {
            // 接続が切れている場合は再接続
            error_log("Connection lost, reconnecting: " . $e->getMessage());
            $this->connect();
        }
        
        return $this->connection;
    }
    
    // 安全なクエリ実行
    public function executeQuery($query, $params = []) {
        try {
            $conn = $this->getConnection();
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute($params);
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution error: " . $e->getMessage() . " Query: " . $query);
            throw $e;
        }
    }
    
    // ユーザー関連メソッド
    public function getUserByEmail($email) {
        try {
            $stmt = $this->executeQuery("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getUserByEmail error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateUserLastLogin($userId) {
        try {
            $stmt = $this->executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("updateUserLastLogin error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers() {
        try {
            $stmt = $this->executeQuery("SELECT id, email, name, role, is_active, created_at, last_login FROM users ORDER BY name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllUsers error: " . $e->getMessage());
            return false;
        }
    }
    
    // プロジェクト関連メソッド（簡略版）
    public function createProject($name, $description, $clientName, $projectCode, $startDate, $targetEndDate, $createdBy) {
        try {
            $conn = $this->getConnection();
            if (!$conn) return false;
            
            $conn->beginTransaction();
            
            // プロジェクト作成
            $stmt = $this->executeQuery("
                INSERT INTO projects (name, description, client_name, project_code, start_date, target_end_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$name, $description, $clientName, $projectCode, $startDate, $targetEndDate, $createdBy]);
            
            $projectId = $conn->lastInsertId();
            
            // テンプレートからタスクを生成
            $this->createTasksFromTemplates($projectId);
            
            // 履歴記録
            $this->addProjectHistory($projectId, null, $createdBy, 'created', null, $name, 'プロジェクト作成');
            
            $conn->commit();
            return $projectId;
        } catch (Exception $e) {
            if ($this->connection) {
                $this->connection->rollBack();
            }
            error_log("createProject error: " . $e->getMessage());
            return false;
        }
    }
    
    // 他の必要なメソッドも同様に実装...
    // （簡潔にするため一部省略）
    
    public function getAllProjects() {
        try {
            $stmt = $this->executeQuery("
                SELECT p.*, u.name as created_by_name,
                       COUNT(t.id) as total_tasks,
                       SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                       SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks
                FROM projects p 
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN tasks t ON p.id = t.project_id
                GROUP BY p.id
                ORDER BY p.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllProjects error: " . $e->getMessage());
            return false;
        }
    }
    
    private function createTasksFromTemplates($projectId) {
        // 実装は元のdatabase.phpと同じ
        return true; // 簡略化
    }
    
    private function addProjectHistory($projectId, $taskId, $userId, $actionType, $oldValue, $newValue, $description) {
        // 実装は元のdatabase.phpと同じ
        return true; // 簡略化
    }
}
?>