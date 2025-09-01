<?php
require_once 'config.php';

class Database {
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->port = DB_PORT ?? '3306';
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    public function connect() {
        try {
            $this->connection = null;
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbname . ";charset=" . $this->charset;
            error_log("Database DSN: " . $dsn);
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            error_log("Database connection successful");
            return $this->connection;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            error_log("Database host: " . $this->host);
            error_log("Database name: " . $this->dbname);
            return false;
        }
    }
    
    public function getConnection() {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection;
    }
    
    // ユーザー関連メソッド
    public function getUserByEmail($email) {
        try {
            $stmt = $this->getConnection()->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getUserByEmail error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateUserLastLogin($userId) {
        try {
            $stmt = $this->getConnection()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("updateUserLastLogin error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers() {
        try {
            $stmt = $this->getConnection()->prepare("SELECT id, email, name, role, is_active, created_at, last_login FROM users ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllUsers error: " . $e->getMessage());
            return false;
        }
    }
    
    // プロジェクト関連メソッド
    public function createProject($name, $description, $clientName, $projectCode, $startDate, $targetEndDate, $createdBy) {
        try {
            $this->getConnection()->beginTransaction();
            
            // プロジェクト作成
            $stmt = $this->getConnection()->prepare("
                INSERT INTO projects (name, description, client_name, project_code, start_date, target_end_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $clientName, $projectCode, $startDate, $targetEndDate, $createdBy]);
            $projectId = $this->getConnection()->lastInsertId();
            
            // テンプレートからタスクを生成
            $this->createTasksFromTemplates($projectId);
            
            // 履歴記録
            $this->addProjectHistory($projectId, null, $createdBy, 'created', null, $name, 'プロジェクト作成');
            
            $this->getConnection()->commit();
            return $projectId;
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("createProject error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllProjects() {
        try {
            $stmt = $this->getConnection()->prepare("
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
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllProjects error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getProjectById($id) {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT p.*, u.name as created_by_name
                FROM projects p 
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getProjectById error: " . $e->getMessage());
            return false;
        }
    }
    
    // タスク関連メソッド
    private function createTasksFromTemplates($projectId) {
        try {
            $stmt = $this->getConnection()->prepare("SELECT * FROM task_templates ORDER BY phase_name, task_order");
            $stmt->execute();
            $templates = $stmt->fetchAll();
            
            $insertStmt = $this->getConnection()->prepare("
                INSERT INTO tasks (project_id, template_id, phase_name, task_name, task_order, is_technical_work, has_manual, estimated_hours) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($templates as $template) {
                $insertStmt->execute([
                    $projectId,
                    $template['id'],
                    $template['phase_name'],
                    $template['task_name'],
                    $template['task_order'],
                    $template['is_technical_work'],
                    $template['has_manual'],
                    $template['estimated_hours']
                ]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("createTasksFromTemplates error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getProjectTasks($projectId) {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT t.*, u.name as assigned_to_name
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                ORDER BY t.phase_name, t.task_order
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getProjectTasks error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTaskStatus($taskId, $status, $userId, $notes = null) {
        try {
            $this->getConnection()->beginTransaction();
            
            // 現在の状態を取得
            $stmt = $this->getConnection()->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch();
            
            // タスク更新
            $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            if ($status === 'in_progress' && $currentTask['actual_start_date'] === null) {
                $updateData['actual_start_date'] = date('Y-m-d');
            }
            if ($status === 'completed' && $currentTask['actual_end_date'] === null) {
                $updateData['actual_end_date'] = date('Y-m-d');
            }
            
            $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($updateData)));
            $stmt = $this->getConnection()->prepare("UPDATE tasks SET $setClause WHERE id = ?");
            $stmt->execute([...array_values($updateData), $taskId]);
            
            // ノート追加
            if ($notes) {
                $stmt = $this->getConnection()->prepare("INSERT INTO task_notes (task_id, user_id, note) VALUES (?, ?, ?)");
                $stmt->execute([$taskId, $userId, $notes]);
            }
            
            // 履歴記録
            $this->addProjectHistory($currentTask['project_id'], $taskId, $userId, 'status_changed', $currentTask['status'], $status, "タスク状態変更: {$currentTask['task_name']}");
            
            $this->getConnection()->commit();
            return true;
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("updateTaskStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    public function assignTask($taskId, $assignedTo, $userId, $plannedDate = null) {
        try {
            $this->getConnection()->beginTransaction();
            
            $stmt = $this->getConnection()->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch();
            
            $stmt = $this->getConnection()->prepare("UPDATE tasks SET assigned_to = ?, planned_date = ? WHERE id = ?");
            $stmt->execute([$assignedTo, $plannedDate, $taskId]);
            
            // 履歴記録
            $oldAssignee = $currentTask['assigned_to'] ? "ID: {$currentTask['assigned_to']}" : '未割当';
            $newAssignee = $assignedTo ? "ID: $assignedTo" : '未割当';
            $this->addProjectHistory($currentTask['project_id'], $taskId, $userId, 'assigned', $oldAssignee, $newAssignee, "タスク担当者変更: {$currentTask['task_name']}");
            
            $this->getConnection()->commit();
            return true;
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("assignTask error: " . $e->getMessage());
            return false;
        }
    }
    
    // 履歴管理
    private function addProjectHistory($projectId, $taskId, $userId, $actionType, $oldValue, $newValue, $description) {
        try {
            $stmt = $this->getConnection()->prepare("
                INSERT INTO project_history (project_id, task_id, user_id, action_type, old_value, new_value, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$projectId, $taskId, $userId, $actionType, $oldValue, $newValue, $description]);
        } catch (PDOException $e) {
            error_log("addProjectHistory error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getProjectHistory($projectId, $limit = 50) {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT ph.*, u.name as user_name, t.task_name
                FROM project_history ph
                LEFT JOIN users u ON ph.user_id = u.id
                LEFT JOIN tasks t ON ph.task_id = t.id
                WHERE ph.project_id = ?
                ORDER BY ph.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$projectId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getProjectHistory error: " . $e->getMessage());
            return false;
        }
    }
    
    // プロジェクト統計
    public function getProjectStatistics($projectId) {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) as not_started,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'not_applicable' THEN 1 ELSE 0 END) as not_applicable,
                    SUM(CASE WHEN planned_date < CURDATE() AND status NOT IN ('completed', 'not_applicable') THEN 1 ELSE 0 END) as overdue
                FROM tasks
                WHERE project_id = ?
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getProjectStatistics error: " . $e->getMessage());
            return false;
        }
    }

    // ==================== 管理機能 ====================

    // ユーザー管理
    public function getUserById($userId) {
        try {
            $stmt = $this->getConnection()->prepare("SELECT id, email, name, role, is_active, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getUserById error: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($email, $passwordHash, $name, $role) {
        try {
            $stmt = $this->getConnection()->prepare("
                INSERT INTO users (email, password_hash, name, role, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$email, $passwordHash, $name, $role]);
            return $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            error_log("createUser error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($userId, $name, $role, $isActive) {
        try {
            $stmt = $this->getConnection()->prepare("
                UPDATE users
                SET name = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            return $stmt->execute([$name, $role, $isActive, $userId]);
        } catch (PDOException $e) {
            error_log("updateUser error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            $stmt = $this->getConnection()->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("deleteUser error: " . $e->getMessage());
            return false;
        }
    }

    // フェーズ管理
    public function getAllPhases() {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT phase_name, description
                FROM phases
                ORDER BY phase_name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllPhases error: " . $e->getMessage());
            return false;
        }
    }

    public function createPhase($phaseName, $description) {
        try {
            $stmt = $this->getConnection()->prepare("
                INSERT INTO phases (phase_name, description)
                VALUES (?, ?)
            ");
            return $stmt->execute([$phaseName, $description]);
        } catch (PDOException $e) {
            error_log("createPhase error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePhase($oldPhaseName, $newPhaseName, $description) {
        try {
            $stmt = $this->getConnection()->prepare("
                UPDATE phases
                SET phase_name = ?, description = ?
                WHERE phase_name = ?
            ");
            return $stmt->execute([$newPhaseName, $description, $oldPhaseName]);
        } catch (PDOException $e) {
            error_log("updatePhase error: " . $e->getMessage());
            return false;
        }
    }

    public function deletePhase($phaseName) {
        try {
            $stmt = $this->getConnection()->prepare("DELETE FROM phases WHERE phase_name = ?");
            return $stmt->execute([$phaseName]);
        } catch (PDOException $e) {
            error_log("deletePhase error: " . $e->getMessage());
            return false;
        }
    }
}
?>