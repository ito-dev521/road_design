<?php
require_once 'config.php';

class Database {
    private $connection;
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->port = defined('DB_PORT') ? DB_PORT : '3306';
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
    public function createProject($name, $description, $clientId, $projectCode, $startDate, $EndDate, $createdBy) {
        try {
            error_log("Database createProject called with: name=$name, clientId=$clientId, startDate=$startDate, endDate=$EndDate");
            
            $connection = $this->getConnection();
            if (!$connection) {
                error_log("Database createProject error: No database connection");
                return false;
            }
            
            $connection->beginTransaction();
            
            // 互換性対応: 環境ごとの列名差異に対処（client_id/client_name、end_date/target_end_date）
            $hasClientId = $connection->query("SHOW COLUMNS FROM projects LIKE 'client_id'")->rowCount() > 0;
            $hasClientName = $connection->query("SHOW COLUMNS FROM projects LIKE 'client_name'")->rowCount() > 0;
            $hasEndDate = $connection->query("SHOW COLUMNS FROM projects LIKE 'end_date'")->rowCount() > 0;
            $hasTargetEndDate = $connection->query("SHOW COLUMNS FROM projects LIKE 'target_end_date'")->rowCount() > 0;

            $columns = ['name', 'description', 'project_code', 'start_date', 'created_by'];
            $params = [$name, $description, $projectCode, $startDate, $createdBy];

            if ($hasClientId) {
                array_splice($columns, 2, 0, 'client_id');
                array_splice($params, 2, 0, $clientId);
            } elseif ($hasClientName) {
                $clientName = null;
                if ($clientId) {
                    $stmtClient = $connection->prepare("SELECT name FROM clients WHERE id = ?");
                    $stmtClient->execute([$clientId]);
                    $row = $stmtClient->fetch();
                    $clientName = $row ? $row['name'] : null;
                }
                array_splice($columns, 2, 0, 'client_name');
                array_splice($params, 2, 0, $clientName);
            }

            if ($hasEndDate) {
                array_splice($columns, 4, 0, 'end_date');
                array_splice($params, 4, 0, $EndDate);
            } elseif ($hasTargetEndDate) {
                array_splice($columns, 4, 0, 'target_end_date');
                array_splice($params, 4, 0, $EndDate);
            }

            $columnsSql = implode(', ', $columns);
            $placeholders = rtrim(str_repeat('?, ', count($columns)), ', ');
            $sql = "INSERT INTO projects ($columnsSql) VALUES ($placeholders)";

            error_log("Executing project creation SQL: $sql");
            error_log("SQL params: " . json_encode($params));
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("Database createProject error: Failed to execute dynamic project creation SQL");
                $connection->rollBack();
                return false;
            }
            
            $projectId = $connection->lastInsertId();
            error_log("Project created successfully with ID: $projectId");
            
            // 履歴記録
            $this->addProjectHistory($projectId, null, $createdBy, 'created', null, $name, 'プロジェクト作成');
            
            $connection->commit();
            error_log("Database createProject completed successfully");
            return $projectId;
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("createProject PDO error: " . $e->getMessage());
            error_log("createProject PDO error code: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("createProject general error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllProjects() {
        try {
            error_log("=== getAllProjects 開始 ===");
            
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
            
            error_log("SQL実行開始");
            $stmt->execute();
            $result = $stmt->fetchAll();
            error_log("SQL実行完了: " . count($result) . "件取得");
            
            error_log("=== getAllProjects 完了 ===");
            return $result;
        } catch (PDOException $e) {
            error_log("getAllProjects error: " . $e->getMessage());
            error_log("getAllProjects error trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function getTaskById($taskId) {
        try {
            error_log("Database getTaskById called with taskId: $taskId");
            
            $stmt = $this->getConnection()->prepare("
                SELECT t.*, t.status, u.name as assigned_to_name,
                       tt.content as template_content,
                       (SELECT GROUP_CONCAT(note SEPARATOR '|') FROM task_notes WHERE task_id = t.id ORDER BY created_at DESC) as notes
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN task_templates tt ON t.template_id = tt.id
                WHERE t.id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            
            error_log("Database getTaskById result: " . json_encode($task));
            if ($task) {
                error_log("Database getTaskById planned_date: " . ($task['planned_date'] ?? 'null'));
                error_log("Database getTaskById status: " . ($task['status'] ?? 'null'));
            }
            
            if ($task && $task['notes']) {
                $task['notes'] = explode('|', $task['notes'])[0]; // 最新のノートのみ取得
            }
            
            return $task;
        } catch (PDOException $e) {
            error_log("getTaskById error: " . $e->getMessage());
            return false;
        }
    }
    
    public function addTaskNote($taskId, $userId, $note) {
        try {
            error_log("Database addTaskNote called with taskId: $taskId, userId: $userId, note: '$note'");
            
            $stmt = $this->getConnection()->prepare("INSERT INTO task_notes (task_id, user_id, note, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$taskId, $userId, $note]);
            
            error_log("Database addTaskNote execute result: " . ($result ? 'true' : 'false'));
            if (!$result) {
                error_log("Database addTaskNote error info: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("addTaskNote PDO error: " . $e->getMessage());
            error_log("addTaskNote PDO error code: " . $e->getCode());
            return false;
        }
    }

    // タスクメモ更新
    public function updateTaskNote($noteId, $note) {
        try {
            error_log("Database updateTaskNote called with noteId: $noteId, note: '$note'");
            
            $stmt = $this->getConnection()->prepare("UPDATE task_notes SET note = ? WHERE id = ?");
            $result = $stmt->execute([$note, $noteId]);
            
            error_log("Database updateTaskNote execute result: " . ($result ? 'true' : 'false'));
            if (!$result) {
                error_log("Database updateTaskNote error info: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("updateTaskNote PDO error: " . $e->getMessage());
            error_log("updateTaskNote PDO error code: " . $e->getCode());
            return false;
        }
    }

    // タスクメモ削除
    public function deleteTaskNote($noteId) {
        try {
            error_log("Database deleteTaskNote called with noteId: $noteId");
            
            $stmt = $this->getConnection()->prepare("DELETE FROM task_notes WHERE id = ?");
            $result = $stmt->execute([$noteId]);
            
            error_log("Database deleteTaskNote execute result: " . ($result ? 'true' : 'false'));
            if (!$result) {
                error_log("Database deleteTaskNote error info: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("deleteTaskNote PDO error: " . $e->getMessage());
            error_log("deleteTaskNote PDO error code: " . $e->getCode());
            return false;
        }
    }

    // タスクメモ取得（ユーザー名付き）
    public function getTaskNotes($taskId) {
        try {
            error_log("Database getTaskNotes called with taskId: $taskId");
            
            $stmt = $this->getConnection()->prepare("
                SELECT tn.*, u.name as user_name
                FROM task_notes tn
                LEFT JOIN users u ON tn.user_id = u.id
                WHERE tn.task_id = ?
                ORDER BY tn.created_at DESC
            ");
            $stmt->execute([$taskId]);
            $result = $stmt->fetchAll();
            
            error_log("Database getTaskNotes result: " . print_r($result, true));
            return $result;
        } catch (PDOException $e) {
            error_log("getTaskNotes error: " . $e->getMessage());
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
            $project = $stmt->fetch();

            if (!$project) {
                return false;
            }

            // 列・テーブル存在を確認
            $connection = $this->getConnection();
            $hasClientId = false; $hasClientName = false; $hasEndDate = false; $hasTargetEndDate = false; $hasClientsTable = false;
            try { $hasClientId = $connection->query("SHOW COLUMNS FROM projects LIKE 'client_id'")->rowCount() > 0; } catch (Exception $e) {}
            try { $hasClientName = $connection->query("SHOW COLUMNS FROM projects LIKE 'client_name'")->rowCount() > 0; } catch (Exception $e) {}
            try { $hasEndDate = $connection->query("SHOW COLUMNS FROM projects LIKE 'end_date'")->rowCount() > 0; } catch (Exception $e) {}
            try { $hasTargetEndDate = $connection->query("SHOW COLUMNS FROM projects LIKE 'target_end_date'")->rowCount() > 0; } catch (Exception $e) {}
            try { $hasClientsTable = $connection->query("SHOW TABLES LIKE 'clients'")->rowCount() > 0; } catch (Exception $e) {}

            // 発注者名の補完（client_id優先 → 既存client_name）
            if ($hasClientId && isset($project['client_id']) && $project['client_id'] && $hasClientsTable) {
                try {
                    $cstmt = $connection->prepare("SELECT name FROM clients WHERE id = ?");
                    $cstmt->execute([$project['client_id']]);
                    $client = $cstmt->fetch();
                    if ($client && isset($client['name'])) {
                        $project['client_name'] = $client['name'];
                    }
                } catch (Exception $e) {
                    // noop
                }
            } elseif ($hasClientName && isset($project['client_name'])) {
                // そのまま
            } else {
                // どちらもなければ '不明'
                $project['client_name'] = '不明';
            }

            // 終了日の補完（end_dateが無ければtarget_end_dateを採用）
            if ($hasTargetEndDate && (!isset($project['end_date']) || !$project['end_date'])) {
                if (isset($project['target_end_date']) && $project['target_end_date']) {
                    $project['end_date'] = $project['target_end_date'];
                }
            }

            return $project;
        } catch (PDOException $e) {
            error_log("getProjectById error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateProject($projectId, $name, $clientId, $startDate, $endDate, $status) {
        try {
            error_log("Database updateProject called with projectId: $projectId, name: $name, clientId: $clientId");
            
            $connection = $this->getConnection();
            if (!$connection) {
                error_log("Database updateProject error: No database connection");
                return false;
            }
            
            $connection->beginTransaction();
            
            // 既存取得
            $stmt = $connection->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $existingProject = $stmt->fetch();
            if (!$existingProject) {
                $connection->rollBack();
                return false;
            }
            
            // 列存在チェック
            $hasClientId = $connection->query("SHOW COLUMNS FROM projects LIKE 'client_id'")->rowCount() > 0;
            $hasClientName = $connection->query("SHOW COLUMNS FROM projects LIKE 'client_name'")->rowCount() > 0;
            $hasEndDate = $connection->query("SHOW COLUMNS FROM projects LIKE 'end_date'")->rowCount() > 0;
            $hasTargetEndDate = $connection->query("SHOW COLUMNS FROM projects LIKE 'target_end_date'")->rowCount() > 0;
            $hasStatus = $connection->query("SHOW COLUMNS FROM projects LIKE 'status'")->rowCount() > 0;
            
            $columns = ["name = ?"]; $params = [$name];
            if ($hasClientId) { $columns[] = "client_id = ?"; $params[] = $clientId; }
            elseif ($hasClientName) { $columns[] = "client_name = ?"; $params[] = $clientId; }
            
            if ($hasEndDate) { $columns[] = "end_date = ?"; $params[] = $endDate; }
            elseif ($hasTargetEndDate) { $columns[] = "target_end_date = ?"; $params[] = $endDate; }
            
            if ($hasStatus && $status) { $columns[] = "status = ?"; $params[] = $status; }
            
            $sql = "UPDATE projects SET " . implode(', ', $columns) . ", updated_at = NOW() WHERE id = ?";
            $params[] = $projectId;
            
            error_log("updateProject dynamic SQL: $sql");
            error_log("updateProject params: " . json_encode($params));
            
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute($params);
            
            $connection->commit();
            return $result ? true : false;
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("updateProject error: " . $e->getMessage());
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
    
    public function createTasksFromSelectedTemplates($projectId, $selectedTemplateIds) {
        try {
            error_log("=== createTasksFromSelectedTemplates 開始 ===");
            error_log("プロジェクトID: $projectId");
            error_log("選択されたテンプレートID: " . implode(', ', $selectedTemplateIds));
            
            if (empty($selectedTemplateIds)) {
                error_log("選択されたテンプレートがありません");
                return true;
            }
            
            // 選択されたテンプレートを取得
            $placeholders = str_repeat('?,', count($selectedTemplateIds) - 1) . '?';
            $stmt = $this->getConnection()->prepare("
                SELECT * FROM task_templates 
                WHERE id IN ($placeholders) 
                ORDER BY phase_name, task_order
            ");
            $stmt->execute($selectedTemplateIds);
            $templates = $stmt->fetchAll();
            
            error_log("取得されたテンプレート数: " . count($templates));
            
            if (empty($templates)) {
                error_log("テンプレートが見つかりませんでした");
                return false;
            }
            
            // 既存のタスクをチェック（重複防止）
            $existingTasksStmt = $this->getConnection()->prepare("
                SELECT template_id, task_name FROM tasks 
                WHERE project_id = ? AND template_id IS NOT NULL
            ");
            $existingTasksStmt->execute([$projectId]);
            $existingTasks = $existingTasksStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 既存タスクのテンプレートIDとタスク名を配列に変換
            $existingTemplateIds = array_column($existingTasks, 'template_id');
            $existingTaskNames = array_column($existingTasks, 'task_name');
            
            error_log("既存のタスク数: " . count($existingTasks));
            error_log("既存のテンプレートID: " . implode(', ', $existingTemplateIds));
            
            // タスク作成
            $insertStmt = $this->getConnection()->prepare("
                INSERT INTO tasks (project_id, template_id, phase_name, task_name, task_order, is_technical_work, has_manual, estimated_hours, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'not_started', NOW(), NOW())
            ");
            
            $createdCount = 0;
            $skippedCount = 0;
            foreach ($templates as $template) {
                // 重複チェック：同じテンプレートIDまたは同じタスク名が既に存在する場合はスキップ
                if (in_array($template['id'], $existingTemplateIds) || in_array($template['task_name'], $existingTaskNames)) {
                    error_log("重複のためスキップ: テンプレートID {$template['id']}, タスク名: {$template['task_name']}");
                    $skippedCount++;
                    continue;
                }
                try {
                    $insertStmt->execute([
                        $projectId,
                        $template['id'],
                        $template['phase_name'],
                        $template['task_name'],
                        $template['task_order'],
                        $template['is_technical_work'],
                        $template['has_manual'],
                        isset($template['estimated_hours']) ? $template['estimated_hours'] : 0
                    ]);
                    $createdCount++;
                    error_log("タスク作成成功: {$template['task_name']}");
                } catch (PDOException $e) {
                    error_log("タスク作成失敗: {$template['task_name']} - " . $e->getMessage());
                }
            }
            
            error_log("作成されたタスク数: $createdCount");
            error_log("スキップされたタスク数: $skippedCount");
            error_log("=== createTasksFromSelectedTemplates 完了 ===");
            
            // 結果を配列で返す（作成数とスキップ数を含む）
            return [
                'success' => true,
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'total_selected' => count($templates)
            ];
        } catch (PDOException $e) {
            error_log("createTasksFromSelectedTemplates error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getProjectTasks($projectId) {
        try {
            error_log("getProjectTasks called with project ID: $projectId");
            
            $stmt = $this->getConnection()->prepare("
                SELECT t.*, 
                       t.task_name as name, 
                       t.status,
                       u.name as assigned_to_name, 
                       tt.task_name as template_name, 
                       tt.content as template_content, 
                       tt.is_technical_work, 
                       tt.has_manual, 
                       tt.task_order,
                       tt.phase_name,
                       (SELECT GROUP_CONCAT(note SEPARATOR '|') FROM task_notes WHERE task_id = t.id ORDER BY created_at DESC) as notes
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN task_templates tt ON t.template_id = tt.id
                WHERE t.project_id = ?
                ORDER BY tt.phase_name, tt.task_order
            ");
            $stmt->execute([$projectId]);
            $result = $stmt->fetchAll();
            
            error_log("getProjectTasks result: " . count($result) . " tasks found");
            error_log("getProjectTasks sample data: " . json_encode($result[0] ?? null));
            
            // 各タスクの期限データとステータスを確認
            foreach ($result as $index => $task) {
                error_log("タスク{$index} (ID: {$task['id']}) planned_date: " . ($task['planned_date'] ?? 'null') . ", status: " . ($task['status'] ?? 'null'));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("getProjectTasks error: " . $e->getMessage());
            error_log("getProjectTasks error trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function updateTaskStatus($taskId, $status, $userId, $notes = null) {
        try {
            error_log("Database updateTaskStatus called with taskId: $taskId, status: $status, userId: $userId");
            $this->getConnection()->beginTransaction();
            
            // 現在の状態を取得
            $stmt = $this->getConnection()->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch();
            error_log("Current task data: " . json_encode($currentTask));
            
            // タスク更新
            $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            if ($status === 'in_progress' && $currentTask['actual_start_date'] === null) {
                $updateData['actual_start_date'] = date('Y-m-d');
            }
            if ($status === 'completed' && $currentTask['actual_end_date'] === null) {
                $updateData['actual_end_date'] = date('Y-m-d');
            }
            
            $setClause = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($updateData)));
            $sql = "UPDATE tasks SET $setClause WHERE id = ?";
            $params = array_merge(array_values($updateData), [$taskId]);
            
            error_log("Executing SQL: $sql");
            error_log("SQL parameters: " . json_encode($params));
            
            $stmt = $this->getConnection()->prepare($sql);
            $result = $stmt->execute($params);
            
            error_log("SQL execution result: " . ($result ? 'true' : 'false'));
            error_log("Affected rows: " . $stmt->rowCount());
            
            // ノート追加
            if ($notes) {
                $stmt = $this->getConnection()->prepare("INSERT INTO task_notes (task_id, user_id, note) VALUES (?, ?, ?)");
                $stmt->execute([$taskId, $userId, $notes]);
            }
            
            // 履歴記録
            $this->addProjectHistory($currentTask['project_id'], $taskId, $userId, 'status_changed', $currentTask['status'], $status, "タスク状態変更: {$currentTask['task_name']}");
            
            $this->getConnection()->commit();
            error_log("Database updateTaskStatus: Transaction committed successfully");
            return true;
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("updateTaskStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    public function assignTask($taskId, $assignedTo, $userId, $plannedDate = null) {
        try {
            error_log("Database assignTask called with taskId: $taskId, assignedTo: $assignedTo, plannedDate: $plannedDate");
            
            // 接続の確認
            $connection = $this->getConnection();
            if (!$connection) {
                error_log("Database assignTask error: No database connection");
                return false;
            }
            
            $connection->beginTransaction();
            
            // タスクの存在確認
            $stmt = $connection->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch();
            
            if (!$currentTask) {
                error_log("Database assignTask error: Task not found with ID: $taskId");
                $connection->rollBack();
                return false;
            }
            
            error_log("Current task data: " . json_encode($currentTask));
            
            // データの変更があるかチェック
            $hasChanges = false;
            if ($currentTask['assigned_to'] != $assignedTo) {
                error_log("Assigned to changed: {$currentTask['assigned_to']} -> $assignedTo");
                $hasChanges = true;
            }
            if ($currentTask['planned_date'] != $plannedDate) {
                error_log("Planned date changed: {$currentTask['planned_date']} -> $plannedDate");
                $hasChanges = true;
            }
            
            if (!$hasChanges) {
                error_log("No changes detected, skipping update");
                $connection->rollBack();
                return true;
            }
            
            $sql = "UPDATE tasks SET assigned_to = ?, planned_date = ?, updated_at = NOW() WHERE id = ?";
            $params = [$assignedTo, $plannedDate, $taskId];
            
            error_log("Executing SQL: $sql");
            error_log("SQL parameters: " . json_encode($params));
            
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute($params);
            
            error_log("SQL execution result: " . ($result ? 'true' : 'false'));
            error_log("Affected rows: " . $stmt->rowCount());
            
            if ($stmt->rowCount() === 0) {
                error_log("Database assignTask warning: No rows were updated");
            }
            
            // 履歴記録
            $oldAssignee = $currentTask['assigned_to'] ? "ID: {$currentTask['assigned_to']}" : "unassigned";
            $newAssignee = $assignedTo ? "ID: $assignedTo" : "unassigned";
            $this->addProjectHistory($currentTask['project_id'], $taskId, $userId, 'assigned', $oldAssignee, $newAssignee, "タスク担当者変更: {$currentTask['task_name']}");
            
            $this->getConnection()->commit();
            error_log("Database assignTask: Transaction committed successfully");
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

    public function createUser($email, $passwordHash, $name, $role, $isActive = 1) {
        try {
            error_log("Database createUser called with: email=$email, name=$name, role=$role, isActive=$isActive");
            
            $stmt = $this->getConnection()->prepare("
                INSERT INTO users (email, password_hash, name, role, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$email, $passwordHash, $name, $role, $isActive]);
            
            if ($result) {
                $userId = $this->getConnection()->lastInsertId();
                error_log("User created successfully with ID: $userId");
                return $userId;
            } else {
                error_log("User creation failed - execute returned false");
                error_log('createUser execute error info: ' . print_r($stmt->errorInfo(), true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("createUser PDO error: " . $e->getMessage());
            error_log("createUser PDO error code: " . $e->getCode());
            return false;
        }
    }

    public function updateUser($userId, $email, $name, $role, $password = null, $isActive = 1) {
        try {
            error_log("Database updateUser called with: userId=$userId, email=$email, name=$name, role=$role, password=" . ($password ? 'set' : 'null') . ", isActive=$isActive");
            
            if ($password && trim($password) !== '') {
                // パスワードも更新する場合
                error_log("Password update requested for user ID: $userId");
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $connection = $this->getConnection();
                $hasPasswordHash = false;
                try {
                    $hasPasswordHash = $connection->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->rowCount() > 0;
                } catch (Exception $e) {
                    error_log("SHOW COLUMNS failed: " . $e->getMessage());
                }
                $passwordColumn = $hasPasswordHash ? 'password_hash' : 'password';
                $stmt = $connection->prepare("
                    UPDATE users
                    SET email = ?, name = ?, role = ?, {$passwordColumn} = ?, is_active = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$email, $name, $role, $hashedPassword, $isActive, $userId]);
                if (!$result) {
                    error_log('updateUser execute error info (with password): ' . print_r($stmt->errorInfo(), true));
                }
            } else {
                // パスワードは更新しない場合
                error_log("Password update skipped for user ID: $userId (password is empty or null)");
                $stmt = $this->getConnection()->prepare("
                    UPDATE users
                    SET email = ?, name = ?, role = ?, is_active = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$email, $name, $role, $isActive, $userId]);
                if (!$result) {
                    error_log('updateUser execute error info (without password): ' . print_r($stmt->errorInfo(), true));
                }
            }
            
            error_log("updateUser result: " . ($result ? 'success' : 'failed'));
            return $result;
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
            error_log("Database getAllPhases called");
            
            $stmt = $this->getConnection()->prepare("
                SELECT id, name, description, order_num, is_active
                FROM phases
                WHERE is_active = 1
                ORDER BY order_num
            ");
            
            error_log("SQL prepared, executing...");
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            error_log("getAllPhases result: " . print_r($result, true));
            return $result;
            
        } catch (PDOException $e) {
            error_log("getAllPhases PDO error: " . $e->getMessage());
            error_log("getAllPhases PDO error code: " . $e->getCode());
            return false;
        }
    }

    public function createPhase($name, $description, $orderNum = 1) {
        try {
            $stmt = $this->getConnection()->prepare("
                INSERT INTO phases (name, description, order_num, is_active)
                VALUES (?, ?, ?, 1)
            ");
            return $stmt->execute([$name, $description, $orderNum]);
        } catch (PDOException $e) {
            error_log("createPhase error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePhase($id, $name, $description, $orderNum) {
        try {
            $stmt = $this->getConnection()->prepare("
                UPDATE phases
                SET name = ?, description = ?, order_num = ?
                WHERE id = ?
            ");
            return $stmt->execute([$name, $description, $orderNum, $id]);
        } catch (PDOException $e) {
            error_log("updatePhase error: " . $e->getMessage());
            return false;
        }
    }

    public function deletePhase($id) {
        try {
            $stmt = $this->getConnection()->prepare("DELETE FROM phases WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("deletePhase error: " . $e->getMessage());
            return false;
        }
    }

    // フェーズ詳細取得
    public function getPhaseById($id) {
        try {
            error_log("Database getPhaseById called with id: $id");
            
            $stmt = $this->getConnection()->prepare("
                SELECT id, name, description, order_num, is_active
                FROM phases
                WHERE id = ?
            ");
            
            error_log("SQL prepared, executing with id: $id");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            error_log("getPhaseById result: " . print_r($result, true));
            return $result;
            
        } catch (PDOException $e) {
            error_log("getPhaseById PDO error: " . $e->getMessage());
            error_log("getPhaseById PDO error code: " . $e->getCode());
            return false;
        }
    }

    // テンプレート管理
    public function getAllTemplates() {
        try {
            error_log("=== Database getAllTemplates called ===");
            
            $connection = $this->getConnection();
            if (!$connection) {
                error_log("getAllTemplates error: No database connection");
                return false;
            }
            
            error_log("Database connection established successfully");
            
            $sql = "
                SELECT id, phase_name, task_name, task_order, is_technical_work, has_manual, description as content
                FROM task_templates
                ORDER BY phase_name, task_order
            ";
            
            error_log("Preparing SQL: " . $sql);
            $stmt = $connection->prepare($sql);
            
            if (!$stmt) {
                error_log("getAllTemplates error: Failed to prepare statement");
                return false;
            }
            
            error_log("SQL prepared successfully, executing...");
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("getAllTemplates error: Failed to execute statement");
                return false;
            }
            
            $templates = $stmt->fetchAll();
            error_log("getAllTemplates executed successfully, found " . count($templates) . " templates");
            
            if (count($templates) > 0) {
                error_log("First template structure: " . print_r($templates[0], true));
                error_log("First template keys: " . implode(', ', array_keys($templates[0])));
            }
            
            error_log("getAllTemplates result: " . print_r($templates, true));
            
            return $templates;
            
        } catch (PDOException $e) {
            error_log("getAllTemplates PDO error: " . $e->getMessage());
            error_log("getAllTemplates PDO error code: " . $e->getCode());
            error_log("getAllTemplates PDO error trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("getAllTemplates general error: " . $e->getMessage());
            error_log("getAllTemplates general error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // タスクテンプレート詳細取得
    public function getTaskTemplateById($id) {
        try {
            error_log("Database getTaskTemplateById called with id: $id");
            
            $stmt = $this->getConnection()->prepare("
                SELECT id, phase_name, task_name, task_order, is_technical_work, has_manual, content
                FROM task_templates
                WHERE id = ?
            ");
            
            error_log("SQL prepared, executing with id: $id");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            error_log("getTaskTemplateById result: " . print_r($result, true));
            return $result;
            
        } catch (PDOException $e) {
            error_log("getTaskTemplateById PDO error: " . $e->getMessage());
            error_log("getTaskTemplateById PDO error code: " . $e->getCode());
            return false;
        }
    }

    // テンプレート作成
    public function createTaskTemplate($phaseName, $taskName, $content, $taskOrder, $isTechnicalWork, $hasManual) {
        try {
            error_log("Database createTaskTemplate called with: phase=$phaseName, task=$taskName, content=$content, order=$taskOrder, tech=$isTechnicalWork, manual=$hasManual");
            
            $stmt = $this->getConnection()->prepare("
                INSERT INTO task_templates (phase_name, task_name, content, task_order, is_technical_work, has_manual)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$phaseName, $taskName, $content, $taskOrder, $isTechnicalWork, $hasManual]);
            
            if ($result) {
                error_log("Task template created successfully with ID: " . $this->getConnection()->lastInsertId());
                return true;
            } else {
                error_log("Failed to create task template");
                return false;
            }
        } catch (PDOException $e) {
            error_log("createTaskTemplate PDO error: " . $e->getMessage());
            return false;
        }
    }

    // テンプレート更新（マニュアル情報除く）
    public function updateTaskTemplateWithoutManual($id, $phaseName, $taskName, $content, $taskOrder, $isTechnicalWork) {
        try {
            error_log("Database updateTaskTemplateWithoutManual called with: id=$id, phase=$phaseName, task=$taskName, content=$content, order=$taskOrder, tech=$isTechnicalWork");
            
            $stmt = $this->getConnection()->prepare("
                UPDATE task_templates 
                SET phase_name = ?, task_name = ?, content = ?, task_order = ?, is_technical_work = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$phaseName, $taskName, $content, $taskOrder, $isTechnicalWork, $id]);
            
            if ($result) {
                error_log("Task template updated successfully");
                return true;
            } else {
                error_log("Failed to update task template");
                return false;
            }
        } catch (PDOException $e) {
            error_log("updateTaskTemplateWithoutManual PDO error: " . $e->getMessage());
            return false;
        }
    }

    // テンプレート削除
    public function deleteTaskTemplate($id) {
        try {
            error_log("Database deleteTaskTemplate called with id: $id");
            
            $stmt = $this->getConnection()->prepare("DELETE FROM task_templates WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                error_log("Task template deleted successfully");
                return true;
            } else {
                error_log("Failed to delete task template");
                return false;
            }
        } catch (PDOException $e) {
            error_log("deleteTaskTemplate PDO error: " . $e->getMessage());
            return false;
        }
    }

    // マニュアル管理
    public function getAllManuals() {
        try {
            error_log("Database getAllManuals called");
            
            $stmt = $this->getConnection()->prepare("
                SELECT id, task_name, file_name, original_name, description, file_size, file_path, created_at
                FROM manuals
                ORDER BY created_at DESC
            ");
            
            error_log("SQL prepared, executing...");
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            error_log("getAllManuals result: " . print_r($result, true));
            return $result;
            
        } catch (PDOException $e) {
            error_log("getAllManuals PDO error: " . $e->getMessage());
            error_log("getAllManuals PDO error code: " . $e->getCode());
            return false;
        }
    }

    // マニュアル削除
    public function deleteManual($id) {
        try {
            error_log("Database deleteManual called with id: $id");
            
            // まずファイルパスを取得
            $stmt = $this->getConnection()->prepare("
                SELECT file_path FROM manuals WHERE id = ?
            ");
            
            error_log("SQL prepared for file path, executing with id: $id");
            $stmt->execute([$id]);
            $manual = $stmt->fetch();
            
            if (!$manual) {
                error_log("Manual not found with id: $id");
                return false;
            }
            
            error_log("Found manual with file_path: " . $manual['file_path']);
            
            // ファイルを削除
            if (!empty($manual['file_path']) && file_exists($manual['file_path'])) {
                if (unlink($manual['file_path'])) {
                    error_log("File deleted successfully: " . $manual['file_path']);
                } else {
                    error_log("Failed to delete file: " . $manual['file_path']);
                }
            }
            
            // データベースからレコードを削除
            $stmt = $this->getConnection()->prepare("DELETE FROM manuals WHERE id = ?");
            error_log("SQL prepared for deletion, executing with id: $id");
            $result = $stmt->execute([$id]);
            
            error_log("deleteManual result: " . ($result ? 'true' : 'false'));
            return $result;
            
        } catch (PDOException $e) {
            error_log("deleteManual PDO error: " . $e->getMessage());
            error_log("deleteManual PDO error code: " . $e->getCode());
            return false;
        }
    }

    // マニュアル詳細取得
    public function getManualById($id) {
        try {
            error_log("Database getManualById called with id: $id");
            
            $stmt = $this->getConnection()->prepare("
                SELECT * FROM manuals WHERE id = ?
            ");
            
            error_log("SQL prepared, executing with id: $id");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            error_log("getManualById result: " . print_r($result, true));
            return $result;
            
        } catch (PDOException $e) {
            error_log("getManualById PDO error: " . $e->getMessage());
            error_log("getManualById PDO error code: " . $e->getCode());
            return false;
        }
    }

    // タスク名によるマニュアル取得
    public function getManualsByTaskName($taskName) {
        try {
            error_log("Database getManualsByTaskName called with taskName: $taskName");
            
            $stmt = $this->getConnection()->prepare("
                SELECT * FROM manuals WHERE task_name = ?
            ");
            
            error_log("SQL prepared, executing with taskName: $taskName");
            $stmt->execute([$taskName]);
            $result = $stmt->fetchAll();
            
            error_log("getManualsByTaskName result: " . print_r($result, true));
            return $result;
            
        } catch (PDOException $e) {
            error_log("getManualsByTaskName PDO error: " . $e->getMessage());
            error_log("getManualsByTaskName PDO error code: " . $e->getCode());
            return false;
        }
    }

    // マニュアル作成
    public function createManual($taskName, $fileName, $originalName, $description, $fileSize) {
        try {
            error_log("Database createManual called with taskName: $taskName, fileName: $fileName");
            
            $stmt = $this->getConnection()->prepare("
                INSERT INTO manuals (task_name, file_name, original_name, file_path, description, file_size, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            // 絶対パスでファイルパスを設定
            $filePath = __DIR__ . '/uploads/manuals/' . $fileName;
            error_log("SQL prepared, executing with filePath: $filePath");
            $result = $stmt->execute([$taskName, $fileName, $originalName, $filePath, $description, $fileSize]);
            
            error_log("createManual result: " . ($result ? 'true' : 'false'));
            if ($result) {
                error_log("Manual created successfully, last insert ID: " . $this->getConnection()->lastInsertId());
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("createManual PDO error: " . $e->getMessage());
            error_log("createManual PDO error code: " . $e->getCode());
            return false;
        }
    }

    // タスクテンプレートのマニュアルありフラグ更新
    public function updateTaskTemplateHasManual($taskName, $hasManual = true) {
        try {
            error_log("Database updateTaskTemplateHasManual called with taskName: $taskName, hasManual: " . ($hasManual ? 'true' : 'false'));
            
            $stmt = $this->getConnection()->prepare("
                UPDATE task_templates 
                SET has_manual = ?, updated_at = NOW()
                WHERE task_name = ?
            ");
            
            $value = $hasManual ? 1 : 0;
            error_log("SQL prepared, executing with hasManual: $value, taskName: $taskName");
            $result = $stmt->execute([$value, $taskName]);
            
            error_log("updateTaskTemplateHasManual result: " . ($result ? 'true' : 'false'));
            return $result;
            
        } catch (PDOException $e) {
            error_log("updateTaskTemplateHasManual PDO error: " . $e->getMessage());
            error_log("updateTaskTemplateHasManual PDO error code: " . $e->getCode());
            return false;
        }
    }

    // タスク管理
    public function getAllTasks() {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT t.id, t.task_name, t.status, t.planned_date, t.completed_date,
                       p.name as project_name, tt.phase_name,
                       u.name as assigned_user_name
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN task_templates tt ON t.template_id = tt.id
                LEFT JOIN users u ON t.assigned_to = u.id
                ORDER BY p.name, tt.phase_name, t.planned_date
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getAllTasks error: " . $e->getMessage());
            return false;
        }
    }

    // プロジェクトにタスクを追加
    public function addTaskToProject($projectId, $taskName, $phaseName, $taskOrder = 0, $isTechnicalWork = false, $hasManual = false, $estimatedHours = null) {
        try {
            error_log("Database addTaskToProject called with projectId: $projectId, taskName: $taskName, phaseName: $phaseName");
            
            $this->getConnection()->beginTransaction();
            
            $stmt = $this->getConnection()->prepare("
                INSERT INTO tasks (project_id, template_id, phase_name, task_name, task_order, is_technical_work, has_manual, estimated_hours, status, created_at, updated_at) 
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 'not_started', NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $projectId,
                $phaseName,
                $taskName,
                $taskOrder,
                $isTechnicalWork ? 1 : 0,
                $hasManual ? 1 : 0,
                $estimatedHours
            ]);
            
            if ($result) {
                $taskId = $this->getConnection()->lastInsertId();
                error_log("Task added successfully with ID: $taskId");
                
                // 履歴記録
                $this->addProjectHistory($projectId, $taskId, 1, 'created', null, $taskName, "タスク追加: $taskName");
                
                $this->getConnection()->commit();
                return $taskId;
            } else {
                $this->getConnection()->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("addTaskToProject error: " . $e->getMessage());
            return false;
        }
    }

    // プロジェクトからタスクを削除
    public function removeTaskFromProject($taskId) {
        try {
            error_log("Database removeTaskFromProject called with taskId: $taskId");
            
            // タスク情報を取得（履歴記録用）
            $stmt = $this->getConnection()->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                error_log("Task not found with ID: $taskId");
                return false;
            }
            
            $this->getConnection()->beginTransaction();
            
            // タスクを削除
            $stmt = $this->getConnection()->prepare("DELETE FROM tasks WHERE id = ?");
            $result = $stmt->execute([$taskId]);
            
            if ($result) {
                error_log("Task removed successfully: $taskId");
                
                // 履歴記録
                $this->addProjectHistory($task['project_id'], $taskId, 1, 'deleted', $task['task_name'], null, "タスク削除: {$task['task_name']}");
                
                $this->getConnection()->commit();
                return true;
            } else {
                $this->getConnection()->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("removeTaskFromProject error: " . $e->getMessage());
            return false;
        }
    }

    // プロジェクトのタスクを更新
    public function updateProjectTask($taskId, $taskName, $phaseName, $taskOrder, $isTechnicalWork, $hasManual, $estimatedHours) {
        try {
            error_log("Database updateProjectTask called with taskId: $taskId");
            
            // 現在のタスク情報を取得
            $stmt = $this->getConnection()->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $currentTask = $stmt->fetch();
            
            if (!$currentTask) {
                error_log("Task not found with ID: $taskId");
                return false;
            }
            
            $this->getConnection()->beginTransaction();
            
            $stmt = $this->getConnection()->prepare("
                UPDATE tasks 
                SET task_name = ?, phase_name = ?, task_order = ?, is_technical_work = ?, has_manual = ?, estimated_hours = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $taskName,
                $phaseName,
                $taskOrder,
                $isTechnicalWork ? 1 : 0,
                $hasManual ? 1 : 0,
                $estimatedHours,
                $taskId
            ]);
            
            if ($result) {
                error_log("Task updated successfully: $taskId");
                
                // 履歴記録
                $this->addProjectHistory($currentTask['project_id'], $taskId, 1, 'updated', $currentTask['task_name'], $taskName, "タスク更新: $taskName");
                
                $this->getConnection()->commit();
                return true;
            } else {
                $this->getConnection()->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("updateProjectTask error: " . $e->getMessage());
            return false;
        }
    }

    // システム統計
    public function getSystemStatistics() {
        try {
            // プロジェクト統計
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_projects
                FROM projects
            ");
            $stmt->execute();
            $projectStats = $stmt->fetch();

            // タスク統計
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                FROM tasks
            ");
            $stmt->execute();
            $taskStats = $stmt->fetch();

            return [
                            'total_projects' => isset($projectStats['total_projects']) ? $projectStats['total_projects'] : 0,
            'active_projects' => isset($projectStats['active_projects']) ? $projectStats['active_projects'] : 0,
            'total_tasks' => isset($taskStats['total_tasks']) ? $taskStats['total_tasks'] : 0,
            'completed_tasks' => isset($taskStats['completed_tasks']) ? $taskStats['completed_tasks'] : 0
            ];
        } catch (PDOException $e) {
            error_log("getSystemStatistics error: " . $e->getMessage());
            return false;
        }
    }

    // クライアント関連メソッド
    public function getAllClients() {
        try {
            error_log("Database getAllClients called");
            $stmt = $this->getConnection()->prepare("
                SELECT * FROM clients 
                ORDER BY name
            ");
            $stmt->execute();
            $result = $stmt->fetchAll();
            error_log("Database getAllClients result: " . json_encode($result));
            return $result;
        } catch (PDOException $e) {
            error_log("getAllClients error: " . $e->getMessage());
            return false;
        }
    }

    public function getClientById($id) {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT * FROM clients 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getClientById error: " . $e->getMessage());
            return false;
        }
    }

    public function createClient($name, $code, $contactPerson, $email, $phone, $address, $description, $isActive) {
        try {
            error_log("Database createClient called with parameters: name='$name', code='$code', contactPerson='$contactPerson', email='$email', phone='$phone', address='$address', description='$description', isActive='$isActive'");
            
            $stmt = $this->getConnection()->prepare("
                INSERT INTO clients (name, code, contact_person, email, phone, address, description, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            error_log("SQL prepared, executing with parameters...");
            $result = $stmt->execute([$name, $code, $contactPerson, $email, $phone, $address, $description, $isActive]);
            error_log("SQL execute result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                error_log("Client inserted successfully, last insert ID: " . $this->getConnection()->lastInsertId());
            } else {
                error_log("SQL execute failed, error info: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("createClient PDO error: " . $e->getMessage());
            error_log("createClient PDO error code: " . $e->getCode());
            error_log("createClient PDO error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function updateClient($id, $name, $code, $contactPerson, $email, $phone, $address, $description, $isActive) {
        try {
            $stmt = $this->getConnection()->prepare("
                UPDATE clients 
                SET name = ?, code = ?, contact_person = ?, email = ?, phone = ?, address = ?, description = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$name, $code, $contactPerson, $email, $phone, $address, $description, $isActive, $id]);
        } catch (PDOException $e) {
            error_log("updateClient error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteClient($id) {
        try {
            $stmt = $this->getConnection()->prepare("DELETE FROM clients WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("deleteClient error: " . $e->getMessage());
            return false;
        }
    }

    // プロジェクト削除
    public function deleteProject($projectId) {
        try {
            error_log("Database deleteProject called with ID: $projectId");
            
            $this->getConnection()->beginTransaction();
            
            // 関連するタスクを削除
            $stmt = $this->getConnection()->prepare("DELETE FROM tasks WHERE project_id = ?");
            $taskResult = $stmt->execute([$projectId]);
            error_log("Tasks deletion result: " . ($taskResult ? 'success' : 'failed'));
            
            // プロジェクト履歴を削除
            $stmt = $this->getConnection()->prepare("DELETE FROM project_history WHERE project_id = ?");
            $historyResult = $stmt->execute([$projectId]);
            error_log("Project history deletion result: " . ($historyResult ? 'success' : 'failed'));
            
            // プロジェクトを削除
            $stmt = $this->getConnection()->prepare("DELETE FROM projects WHERE id = ?");
            $projectResult = $stmt->execute([$projectId]);
            error_log("Project deletion result: " . ($projectResult ? 'success' : 'failed'));
            
            if ($projectResult) {
                $this->getConnection()->commit();
                error_log("Project deleted successfully from database: $projectId");
                return true;
            } else {
                $this->getConnection()->rollBack();
                error_log("Project deletion failed, rolling back transaction");
                return false;
            }
        } catch (PDOException $e) {
            $this->getConnection()->rollBack();
            error_log("deleteProject PDO error: " . $e->getMessage());
            error_log("deleteProject PDO error code: " . $e->getCode());
            error_log("deleteProject PDO error trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
?>