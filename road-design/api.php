<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
// API応答がキャッシュされないように明示的に無効化
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// CORS設定（必要に応じて調整）
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
    exit(0);
}

// デバッグ情報出力（一時的に有効化）
error_log("API Request: " . $_SERVER['REQUEST_METHOD'] . " " . (isset($_GET['path']) ? $_GET['path'] : 'no_path'));

class ApiController {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Database();
    }
    
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = isset($_GET['path']) ? $_GET['path'] : '';
            $input = $this->getJsonInput();
            
            error_log("API Request: $method $path");
            error_log("Input: " . json_encode($input));
            
            // データベース接続確認
            $connection = $this->db->getConnection();
            if (!$connection) {
                error_log("Database connection failed");
                return ['success' => false, 'message' => 'データベース接続エラー'];
            }
            
            // ルーティング
            switch ($path) {
                case 'login':
                    return $this->login($input);
                    
                case 'logout':
                    return $this->logout();
                    
                case 'check_auth':
                    return $this->checkAuth();
                    
                case 'projects':
                    if ($method === 'GET') {
                        return $this->getProjects();
                    } elseif ($method === 'POST') {
                        return $this->createProject($input);
                    }
                    break;

                case (preg_match('/^projects\/(\d+)$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^projects\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getProject($matches[1]);
                        } elseif ($method === 'PUT') {
                            return $this->updateProject($matches[1], $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteProject($matches[1]);
                        }
                    }
                    break;

                case (preg_match('/^projects\/(\d+)\/tasks$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^projects\/(\d+)\/tasks$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getProjectTasks($matches[1]);
                        }
                    }
                    break;
                    
                case 'users':
                    return $this->getUsers();
                    
                case 'templates':
                    if ($method === 'GET') {
                        return $this->getTemplates();
                    }
                    break;
                    
                case 'manuals':
                    if ($method === 'GET') {
                        $taskName = isset($_GET['task_name']) ? trim($_GET['task_name']) : '';
                        if ($taskName !== '') {
                            return $this->getManualsByTaskName($taskName);
                        }
                        return $this->getManuals();
                    }
                    break;
                    
                case 'tasks':
                    if ($method === 'GET') {
                        return $this->getTasks();
                    }
                    break;

                case (preg_match('/^tasks\/(\d+)$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^tasks\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getTask($matches[1]);
                        }
                    }
                    break;
                    
                case 'stats/overview':
                    if ($method === 'GET') {
                        return $this->getStatsOverview();
                    }
                    break;
                    
                case 'user/profile':
                    if ($method === 'GET') {
                        return $this->getUserProfile();
                    }
                    break;
                    
                // 管理機能
                case (preg_match('/^admin\/users$/', $path) ? $path : !$path):
                    if ($path === 'admin/users') {
                        if ($method === 'GET') {
                            return $this->getAdminUsers();
                        } elseif ($method === 'POST') {
                            return $this->createAdminUser($input);
                        }
                    }
                    break;

                case 'phases':
                    if ($method === 'GET') {
                        return $this->getPhases();
                    } elseif ($method === 'POST') {
                        return $this->createPhase($input);
                    }
                    break;

                case (preg_match('/^phases\/(\d+)$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^phases\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getPhase($matches[1]);
                        } elseif ($method === 'PUT') {
                            return $this->updatePhase($matches[1], $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deletePhase($matches[1]);
                        }
                    }
                    break;

                case (preg_match('/^admin\/phases$/', $path) ? $path : !$path):
                    if ($path === 'admin/phases') {
                        if ($method === 'GET') {
                            return $this->getAdminPhases();
                        } elseif ($method === 'POST') {
                            return $this->createAdminPhase($input);
                        }
                    }
                    break;

                case (preg_match('/^admin\/templates$/', $path) ? $path : !$path):
                    if ($path === 'admin/templates') {
                        if ($method === 'GET') {
                            return $this->getAdminTemplates();
                        } elseif ($method === 'POST') {
                            return $this->createAdminTemplate($input);
                        }
                    }
                    break;

                case (preg_match('/^admin\/templates\/(\d+)$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^admin\/templates\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getAdminTemplate($matches[1]);
                        } elseif ($method === 'PUT') {
                            return $this->updateAdminTemplate($matches[1], $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteAdminTemplate($matches[1]);
                        }
                    }
                    break;

                case (preg_match('/^admin\/manuals$/', $path) ? $path : !$path):
                    if ($path === 'admin/manuals') {
                        if ($method === 'GET') {
                            return $this->getAdminManuals();
                        } elseif ($method === 'POST') {
                            return $this->createAdminManual();
                        }
                    }
                    break;

                case (preg_match('/^admin\/manuals\/(\d+)$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^admin\/manuals\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->downloadAdminManual($matches[1]);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteAdminManual($matches[1]);
                        }
                    }
                    break;

                case 'clients':
                    if ($method === 'GET') {
                        return $this->getClients();
                    }
                    break;

                case (preg_match('/^admin\/clients$/', $path) ? $path : !$path):
                    if ($path === 'admin/clients') {
                        if ($method === 'GET') {
                            return $this->getAdminClients();
                        } elseif ($method === 'POST') {
                            return $this->createAdminClient($input);
                        }
                    }
                    break;

                case (preg_match('/^admin\/clients\/(\d+)$/', $path, $matches) ? $path : !$path):
                    if (preg_match('/^admin\/clients\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getAdminClient($matches[1]);
                        } elseif ($method === 'PUT') {
                            return $this->updateAdminClient($matches[1], $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteAdminClient($matches[1]);
                        }
                    }
                    break;

                // プロジェクト詳細 (projects/123 形式)
                default:
                    if (preg_match('/^projects\/(\d+)$/', $path, $matches)) {
                        return $this->getProject($matches[1]);
                    }
                    if (preg_match('/^admin\/users\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getAdminUser($matches[1]);
                        } elseif ($method === 'PUT') {
                            return $this->updateAdminUser($matches[1], $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteAdminUser($matches[1]);
                        }
                    }
                    if (preg_match('/^tasks\/(\d+)$/', $path, $matches)) {
                        if ($method === 'GET') {
                            return $this->getTask($matches[1]);
                        }
                    }
                    if (preg_match('/^admin\/phases\/(.+)$/', $path, $matches)) {
                        if ($method === 'PUT') {
                            return $this->updateAdminPhase(urldecode($matches[1]), $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteAdminPhase(urldecode($matches[1]));
                        }
                    }
                    if ($path === 'tasks/status') {
                        return $this->updateTaskStatus($input);
                    }
                    if ($path === 'tasks/update') {
                        return $this->updateTask($input);
                    }
                    if ($path === 'projects/tasks/add') {
                        return $this->addTaskToProject($input);
                    }
                    if ($path === 'projects/tasks/remove') {
                        return $this->removeTaskFromProject($input);
                    }
                    if ($path === 'projects/tasks/update') {
                        return $this->updateProjectTask($input);
                    }
                    if ($path === 'projects/tasks/add-from-templates') {
                        return $this->addTasksFromTemplatesToProject($input);
                    }
                    // タスクメモ関連
                    if (preg_match('/^tasks\/(\d+)\/notes$/', $path, $matches)) {
                        $taskId = $matches[1];
                        if ($method === 'GET') {
                            return $this->getTaskNotes($taskId);
                        } elseif ($method === 'POST') {
                            return $this->addTaskNote($taskId, $input);
                        }
                    }
                    
                    // メモ編集・削除
                    if (preg_match('/^notes\/(\d+)$/', $path, $matches)) {
                        $noteId = $matches[1];
                        if ($method === 'PUT') {
                            return $this->updateTaskNote($noteId, $input);
                        } elseif ($method === 'DELETE') {
                            return $this->deleteTaskNote($noteId);
                        }
                    }
                    if ($path === 'progress_report') {
                        return $this->getProgressReport(isset($_GET['project_id']) ? $_GET['project_id'] : null);
                    }

                    throw new Exception('API endpoint not found', 404);
            }
            
            throw new Exception('Invalid request method', 405);
            
        } catch (Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            http_response_code($statusCode);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $statusCode
            ];
        }
    }
    
    private function getJsonInput() {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?: [];
    }
    
    // 認証関連
    public function login($input) {
        $email = isset($input['email']) ? $input['email'] : '';
        $password = isset($input['password']) ? $input['password'] : '';
        
        $result = $this->auth->login($email, $password);
        
        if ($result['success']) {
            // ログイン成功時の追加情報
            $result['csrf_token'] = $this->auth->generateCSRFToken();
        }
        
        return $result;
    }
    
    public function logout() {
        return $this->auth->logout();
    }
    
    public function checkAuth() {
        if (!$this->auth->isLoggedIn()) {
            throw new Exception('Not authenticated', 401);
        }

        $user = $this->auth->getCurrentUser();

        // デバッグ情報追加
        error_log("Check auth - User: " . ($user ? $user['name'] . " (" . $user['role'] . ")" : "None"));

        return [
            'success' => true,
            'user' => $user,
            'debug_info' => [
                'session_id' => session_id(),
                            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
            'remote_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown'
            ]
        ];
    }
    
    // プロジェクト関連（ゲストアクセス対応）
    public function getProjects() {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        try {
            error_log("=== getProjects 開始 ===");
            $projects = $this->db->getAllProjects();
            error_log("getAllProjects 結果: " . ($projects === false ? 'false' : count($projects) . '件'));
            
            if ($projects === false) {
                error_log("getAllProjects が false を返しました");
                throw new Exception('Failed to retrieve projects');
            }

            error_log("=== getProjects 完了 ===");
            return [
                'success' => true,
                'projects' => $projects
            ];
        } catch (Exception $e) {
            error_log("getProjects エラー: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function createProject($input) {
        try {
            // 一時的に認証を無効化（デバッグ用）
            // $this->auth->requirePermission('technical'); // 技術者以上の権限が必要
            
            error_log("=== API createProject 開始 ===");
            error_log("入力データ: " . json_encode($input));
            
            // セッション開始を確実にする
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // 入力値検証
            $required = ['name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    error_log("createProject error: Required field '$field' is empty");
                    throw new Exception("Field '$field' is required", 400);
                }
            }
        
        // データ整理
        $name = trim($input['name']);
        $description = trim(isset($input['description']) ? $input['description'] : '');
        $clientId = isset($input['client_id']) ? $input['client_id'] : null;
        $projectCode = trim(isset($input['project_code']) ? $input['project_code'] : '');
        $startDate = isset($input['start_date']) ? $input['start_date'] : null;
        $endDate = isset($input['end_date']) ? $input['end_date'] : null;
        $templates = isset($input['templates']) ? $input['templates'] : [];
        
        error_log("=== createProject 入力データ ===");
        error_log("プロジェクト名: $name");
        error_log("発注者ID: $clientId");
        error_log("開始日: $startDate");
        error_log("終了日: $endDate");
        error_log("テンプレート: " . print_r($templates, true));
        
        // ユーザーIDの取得（デフォルトは1）
        $currentUser = $this->auth->getCurrentUser();
        $createdBy = $currentUser ? $currentUser['id'] : 1;
        
        error_log("=== createProject 開始 ===");
        error_log("プロジェクト名: $name");
        error_log("作成者ID: $createdBy");
        error_log("選択されたテンプレート: " . implode(', ', $templates));
        
        // 日付フォーマット検証
        if ($startDate && !$this->isValidDate($startDate)) {
            throw new Exception('Invalid start date format', 400);
        }
        if ($endDate && !$this->isValidDate($endDate)) {
            throw new Exception('Invalid end date format', 400);
        }
        
        // プロジェクト作成
        $projectId = $this->db->createProject($name, $description, $clientId, $projectCode, $startDate, $endDate, $createdBy);
        
        if (!$projectId) {
            error_log("プロジェクト作成失敗");
            throw new Exception('Failed to create project');
        }
        
        error_log("プロジェクト作成成功: ID $projectId");
        
        // 選択されたテンプレートからタスクを作成
        if (!empty($templates) && is_array($templates)) {
            $taskCreationResult = $this->db->createTasksFromSelectedTemplates($projectId, $templates);
            if (!$taskCreationResult) {
                error_log("Warning: Failed to create tasks from selected templates for project ID: $projectId");
            } else {
                error_log("タスク作成成功: プロジェクトID $projectId");
            }
        }
        
            error_log("=== createProject 完了 ===");
            
            return [
                'success' => true,
                'message' => 'プロジェクトが作成されました',
                'project_id' => $projectId
            ];
        } catch (Exception $e) {
            error_log("createProject error: " . $e->getMessage());
            error_log("createProject error trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'プロジェクトの作成に失敗しました',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    public function updateProject($projectId, $input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('technical'); // 技術者以上の権限が必要
        
        error_log("updateProject called with projectId: $projectId, input: " . json_encode($input));
        
        if (!is_numeric($projectId)) {
            error_log("updateProject error: Invalid project ID: $projectId");
            throw new Exception('Invalid project ID', 400);
        }
        
        $name = trim(isset($input['name']) ? $input['name'] : '');
        $clientId = isset($input['client_id']) ? $input['client_id'] : null;
        $startDate = isset($input['start_date']) ? $input['start_date'] : null;
        $endDate = isset($input['end_date']) ? $input['end_date'] : null;
        $status = isset($input['status']) ? $input['status'] : 'planning';
        // フロントの互換（active→in_progress、on_hold→planning/cancelledのいずれか）
        if ($status === 'active') { $status = 'in_progress'; }
        if ($status === 'on_hold') { $status = 'planning'; }
        
        error_log("updateProject parsed values - name: $name, clientId: $clientId, startDate: $startDate, endDate: $endDate, status: $status");
        
        if (empty($name)) {
            error_log("updateProject error: Project name is required");
            throw new Exception('Project name is required', 400);
        }
        
        try {
            // プロジェクトの存在確認
            $existingProject = $this->db->getProjectById($projectId);
            if (!$existingProject) {
                error_log("updateProject error: Project not found with ID: $projectId");
                throw new Exception('Project not found', 404);
            }
            
            error_log("updateProject: Existing project found: " . json_encode($existingProject));
            if (empty($status) && isset($existingProject['status'])) {
                $status = $existingProject['status'];
            }
            
            // プロジェクト更新
            $result = $this->db->updateProject($projectId, $name, $clientId, $startDate, $endDate, $status);
            if (!$result) {
                error_log("updateProject error: Database update failed");
                throw new Exception('Failed to update project', 500);
            }
            
            error_log("updateProject: Project updated successfully");
            
            return [
                'success' => true,
                'message' => 'プロジェクトが更新されました'
            ];
        } catch (Exception $e) {
            error_log("updateProject error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getProject($projectId) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        if (!is_numeric($projectId)) {
            throw new Exception('Invalid project ID', 400);
        }
        
        $project = $this->db->getProjectById($projectId);
        if (!$project) {
            throw new Exception('Project not found', 404);
        }
        
        $tasks = $this->db->getProjectTasks($projectId);
        $statistics = $this->db->getProjectStatistics($projectId);
        
        return [
            'success' => true,
            'project' => $project,
            'tasks' => $tasks ?: [],
            'statistics' => $statistics ?: []
        ];
    }

    // プロジェクトのタスク取得
    public function getProjectTasks($projectId) {
        error_log("getProjectTasks called with project ID: $projectId");
        
        try {
            // プロジェクトの存在確認
            $project = $this->db->getProjectById($projectId);
            if (!$project) {
                error_log("Project not found: $projectId");
                throw new Exception('プロジェクトが見つかりません', 404);
            }

            // プロジェクトのタスクを取得
            $tasks = $this->db->getProjectTasks($projectId);
            if ($tasks === false) {
                error_log("Failed to get tasks for project: $projectId");
                throw new Exception('タスクの取得に失敗しました');
            }

            error_log("Successfully retrieved " . count($tasks) . " tasks for project: $projectId");
            error_log("Tasks data: " . json_encode($tasks, JSON_UNESCAPED_UNICODE));
            
            return [
                'success' => true,
                'tasks' => $tasks ?: []
            ];
        } catch (Exception $e) {
            error_log("Error in getProjectTasks: " . $e->getMessage());
            throw $e;
        }
    }
    
    // タスク関連
    public function updateTaskStatus($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        $taskId = isset($input['task_id']) ? $input['task_id'] : null;
        $status = isset($input['status']) ? $input['status'] : null;
        $notes = trim(isset($input['notes']) ? $input['notes'] : '');
        
        if (!$taskId || !$status) {
            throw new Exception('Task ID and status are required', 400);
        }
        
        $validStatuses = ['not_started', 'in_progress', 'completed', 'not_applicable', 'pending'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status', 400);
        }
        
        // デフォルトユーザーIDを設定（認証無効化時）
        $userId = 1; // デフォルト管理者ユーザー
        $result = $this->db->updateTaskStatus($taskId, $status, $userId, $notes ?: null);
        
        if (!$result) {
            throw new Exception('Failed to update task status');
        }
        
        // 更新されたタスクの詳細を取得
        $updatedTask = $this->db->getTaskById($taskId);
        error_log("updateTaskStatus: Updated task data: " . json_encode($updatedTask));
        
        return [
            'success' => true,
            'message' => 'タスクの状態が更新されました',
            'task' => $updatedTask
        ];
    }
    
    public function getTask($taskId) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("getTask called with taskId: $taskId");
        
        if (!is_numeric($taskId)) {
            error_log("getTask error: Invalid task ID: $taskId");
            throw new Exception('Invalid task ID', 400);
        }
        
        try {
            $task = $this->db->getTaskById($taskId);
            error_log("getTask database result: " . json_encode($task));
            
            if ($task) {
                error_log("getTask planned_date: " . ($task['planned_date'] ?? 'null'));
                return [
                    'success' => true,
                    'task' => $task
                ];
            } else {
                error_log("getTask: Task not found");
                return [
                    'success' => false,
                    'message' => 'タスクが見つかりません'
                ];
            }
        } catch (Exception $e) {
            error_log("getTask error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスク詳細の取得に失敗しました'
            ];
        }
    }
    
    public function updateTask($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("updateTask called with input: " . json_encode($input));
        
        $taskId = isset($input['task_id']) ? $input['task_id'] : null;
        $status = isset($input['status']) ? $input['status'] : null;
        $assignedTo = isset($input['assigned_to']) ? $input['assigned_to'] : null;
        $plannedDate = isset($input['planned_date']) ? $input['planned_date'] : null;
        $notes = trim(isset($input['notes']) ? $input['notes'] : '');
        
        error_log("updateTask parsed values - taskId: $taskId, status: $status, assignedTo: $assignedTo, plannedDate: $plannedDate");
        
        if (!$taskId) {
            error_log("updateTask error: Task ID is required");
            throw new Exception('Task ID is required', 400);
        }
        
        // タスクの存在確認
        $existingTask = $this->db->getTaskById($taskId);
        if (!$existingTask) {
            error_log("updateTask error: Task not found with ID: $taskId");
            throw new Exception('Task not found', 404);
        }
        
        error_log("updateTask: Existing task found: " . json_encode($existingTask));
        
        // デフォルトユーザーIDを設定（認証無効化時）
        $userId = 1; // デフォルト管理者ユーザー
        
        // ステータス更新
        if ($status) {
            error_log("updateTask: Updating status to '$status' for task $taskId");
            $validStatuses = ['not_started', 'in_progress', 'completed', 'needs_confirmation', 'not_applicable', 'pending'];
            if (!in_array($status, $validStatuses)) {
                error_log("updateTask error: Invalid status '$status'. Valid statuses: " . implode(', ', $validStatuses));
                throw new Exception('Invalid status', 400);
            }
            
            $result = $this->db->updateTaskStatus($taskId, $status, $userId, $notes ?: null);
            error_log("updateTask: updateTaskStatus result: " . ($result ? 'true' : 'false'));
        }
        
        // 担当者・日付更新
        if ($assignedTo !== null || $plannedDate !== null) {
            error_log("updateTask: Updating assignee/date - assignedTo: $assignedTo, plannedDate: $plannedDate");
            
            // 日付形式検証
            if ($plannedDate && !$this->isValidDate($plannedDate)) {
                error_log("updateTask error: Invalid planned date format: $plannedDate");
                throw new Exception('Invalid planned date format', 400);
            }
            
            $result = $this->db->assignTask($taskId, $assignedTo ?: null, $userId, $plannedDate ?: null);
            error_log("updateTask: assignTask result: " . ($result ? 'true' : 'false'));
        }
        
        // 更新されたタスクの詳細を取得
        $updatedTask = $this->db->getTaskById($taskId);
        error_log("updateTask: Updated task data: " . json_encode($updatedTask));
        
        return [
            'success' => true,
            'message' => 'タスクが更新されました',
            'task' => $updatedTask
        ];
    }

    // タスクメモ取得
    public function getTaskNotes($taskId) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("getTaskNotes called with taskId: $taskId");
        
        if (!is_numeric($taskId)) {
            error_log("Invalid task ID: $taskId");
            throw new Exception('Invalid task ID', 400);
        }
        
        try {
            $notes = $this->db->getTaskNotes($taskId);
            error_log("getTaskNotes database result: " . print_r($notes, true));
            
            if ($notes !== false) {
                return [
                    'success' => true,
                    'notes' => $notes
                ];
            } else {
                error_log("getTaskNotes returned false");
                throw new Exception('Failed to retrieve task notes');
            }
        } catch (Exception $e) {
            error_log("getTaskNotes error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'タスクメモの取得に失敗しました'
            ];
        }
    }

    // タスクメモ追加
    public function addTaskNote($taskId, $input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("addTaskNote called with taskId: $taskId, input: " . json_encode($input));
        
        if (!is_numeric($taskId)) {
            error_log("Invalid task ID: $taskId");
            throw new Exception('Invalid task ID', 400);
        }
        
        $note = trim(isset($input['note']) ? $input['note'] : '');
        error_log("Note content: '$note'");
        
        if (empty($note)) {
            error_log("Note content is empty");
            throw new Exception('Note content is required', 400);
        }
        
        // デフォルトユーザーIDを設定（認証無効化時）
        $userId = 1; // デフォルト管理者ユーザー
        error_log("Using user ID: $userId");
        
        try {
            $result = $this->db->addTaskNote($taskId, $userId, $note);
            error_log("Database addTaskNote result: " . ($result ? 'true' : 'false'));
            
        if ($result) {
            error_log("Task note added successfully");
            // 追加されたメモのIDを取得
            $noteId = $this->db->getConnection()->lastInsertId();
            error_log("Added note ID: $noteId");
            return [
                'success' => true,
                'message' => 'メモが追加されました',
                'note_id' => $noteId
            ];
        } else {
            error_log("Database addTaskNote returned false");
            throw new Exception('Failed to add task note');
        }
        } catch (Exception $e) {
            error_log("addTaskNote error: " . $e->getMessage());
            error_log("addTaskNote error trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'メモの追加に失敗しました: ' . $e->getMessage()
            ];
        }
    }

    // タスクメモ更新
    public function updateTaskNote($noteId, $input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("updateTaskNote called with noteId: $noteId, input: " . json_encode($input));
        
        if (!is_numeric($noteId)) {
            error_log("Invalid note ID: $noteId");
            throw new Exception('Invalid note ID', 400);
        }
        
        $note = trim(isset($input['note']) ? $input['note'] : '');
        error_log("Note content: '$note'");
        
        if (empty($note)) {
            error_log("Note content is empty");
            throw new Exception('Note content is required', 400);
        }
        
        try {
            $result = $this->db->updateTaskNote($noteId, $note);
            error_log("Database updateTaskNote result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                error_log("Task note updated successfully");
                return [
                    'success' => true,
                    'message' => 'メモを更新しました'
                ];
            } else {
                error_log("Database updateTaskNote returned false");
                throw new Exception('Failed to update task note');
            }
        } catch (Exception $e) {
            error_log("updateTaskNote error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'メモの更新に失敗しました: ' . $e->getMessage()
            ];
        }
    }

    // タスクメモ削除
    public function deleteTaskNote($noteId) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("deleteTaskNote called with noteId: $noteId");
        
        if (!is_numeric($noteId)) {
            error_log("Invalid note ID: $noteId");
            throw new Exception('Invalid note ID', 400);
        }
        
        try {
            $result = $this->db->deleteTaskNote($noteId);
            error_log("Database deleteTaskNote result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                error_log("Task note deleted successfully");
                return [
                    'success' => true,
                    'message' => 'メモを削除しました'
                ];
            } else {
                error_log("Database deleteTaskNote returned false");
                throw new Exception('Failed to delete task note');
            }
        } catch (Exception $e) {
            error_log("deleteTaskNote error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'メモの削除に失敗しました: ' . $e->getMessage()
            ];
        }
    }
    
    // ユーザー関連
    public function getUsers() {
        // 一時的に認証を緩和（デバッグ用）
        // $this->auth->requireLogin();
        
        $users = $this->db->getAllUsers();
        if ($users === false) {
            throw new Exception('Failed to retrieve users');
        }
        
        return [
            'success' => true,
            'users' => $users
        ];
    }
    
    // 進捗レポート
    public function getProgressReport($projectId) {
        $this->auth->requireLogin();
        
        if (!$projectId || !is_numeric($projectId)) {
            throw new Exception('Valid project ID is required', 400);
        }
        
        $project = $this->db->getProjectById($projectId);
        if (!$project) {
            throw new Exception('Project not found', 404);
        }
        
        $tasks = $this->db->getProjectTasks($projectId);
        $statistics = $this->db->getProjectStatistics($projectId);
        $history = $this->db->getProjectHistory($projectId);
        
        // フェーズ別統計
        $phaseStats = [];
        foreach (['フェーズ1', 'フェーズ2', 'フェーズ3'] as $phase) {
            $phaseTasks = array_filter($tasks, function($task) use ($phase) { return $task['phase_name'] === $phase; });
            $completed = array_filter($phaseTasks, function($task) { return $task['status'] === 'completed'; });
            
            $phaseStats[$phase] = [
                'total' => count($phaseTasks),
                'completed' => count($completed),
                'progress_percentage' => count($phaseTasks) > 0 ? round((count($completed) / count($phaseTasks)) * 100) : 0
            ];
        }
        
        // 担当者別統計
        $assigneeStats = [];
        foreach ($tasks as $task) {
            if ($task['assigned_to_name']) {
                $name = $task['assigned_to_name'];
                if (!isset($assigneeStats[$name])) {
                    $assigneeStats[$name] = ['total' => 0, 'completed' => 0];
                }
                $assigneeStats[$name]['total']++;
                if ($task['status'] === 'completed') {
                    $assigneeStats[$name]['completed']++;
                }
            }
        }
        
        return [
            'success' => true,
            'project' => $project,
            'overall_statistics' => $statistics,
            'phase_statistics' => $phaseStats,
            'assignee_statistics' => $assigneeStats,
            'recent_history' => $history,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // ヘルパーメソッド
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    private function validateCSRF($token) {
        if (!$this->auth->verifyCSRFToken($token)) {
            throw new Exception('Invalid CSRF token', 403);
        }
    }

    // ==================== 管理機能 ====================

    // ユーザー管理
    public function getAdminUsers() {
        // $this->auth->requirePermission('manager'); // 管理者権限必須 - 一時的に無効化

        $users = $this->db->getAllUsers();
        if ($users === false) {
            throw new Exception('Failed to retrieve users');
        }

        return [
            'success' => true,
            'users' => $users
        ];
    }



    // 一般ユーザー向けフェーズ取得（ゲストアクセス対応）
    public function getPhases() {
        // ゲストアクセスも許可（認証不要）
        error_log("getPhases called");
        
        try {
            $phases = $this->db->getAllPhases();
            error_log("getAllPhases result: " . print_r($phases, true));
            
            if ($phases === false) {
                error_log("getAllPhases returned false");
                throw new Exception('Failed to retrieve phases');
            }

            error_log("getPhases returning success with " . count($phases) . " phases");
            return [
                'success' => true,
                'phases' => $phases
            ];
        } catch (Exception $e) {
            error_log("getPhases error: " . $e->getMessage());
            throw $e;
        }
    }

    // フェーズ管理
    public function getAdminPhases() {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $phases = $this->db->getAllPhases();
        if ($phases === false) {
            throw new Exception('Failed to retrieve phases');
        }

        return [
            'success' => true,
            'phases' => $phases
        ];
    }

    // テンプレート一覧
    public function getAdminTemplates() {
        // 閲覧のみはログインで許可（要件に応じて権限強化可）
        $this->auth->requireLogin();

        $templates = $this->db->getAllTemplates();
        if ($templates === false) {
            throw new Exception('Failed to retrieve templates');
        }

        return [
            'success' => true,
            'templates' => $templates
        ];
    }

    // マニュアル一覧
    public function getAdminManuals() {
        // 閲覧のみはログインで許可
        // $this->auth->requireLogin(); // 一時的に無効化

        error_log("getAdminManuals called");

        try {
            $manuals = $this->db->getAllManuals();
            error_log("getAllManuals result: " . print_r($manuals, true));
            
            if ($manuals === false) {
                error_log("getAllManuals returned false");
                throw new Exception('Failed to retrieve manuals');
            }

            error_log("getAdminManuals returning success with " . count($manuals) . " manuals");
            return [
                'success' => true,
                'manuals' => $manuals
            ];
        } catch (Exception $e) {
            error_log("getAdminManuals error: " . $e->getMessage());
            throw $e;
        }
    }

    public function createAdminPhase($input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $phaseName = trim(isset($input['phase_name']) ? $input['phase_name'] : '');
        $description = trim(isset($input['description']) ? $input['description'] : '');

        if (empty($phaseName)) {
            throw new Exception('Phase name is required', 400);
        }

        $result = $this->db->createPhase($phaseName, $description);
        if (!$result) {
            throw new Exception('Failed to create phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが作成されました'
        ];
    }

    public function updateAdminPhase($phaseName, $input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $newPhaseName = trim(isset($input['phase_name']) ? $input['phase_name'] : '');
        $description = trim(isset($input['description']) ? $input['description'] : '');

        if (empty($newPhaseName)) {
            throw new Exception('Phase name is required', 400);
        }

        $result = $this->db->updatePhase($phaseName, $newPhaseName, $description);
        if (!$result) {
            throw new Exception('Failed to update phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが更新されました'
        ];
    }

    public function deleteAdminPhase($phaseName) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $result = $this->db->deletePhase($phaseName);
        if (!$result) {
            throw new Exception('Failed to delete phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが削除されました'
        ];
    }







    // 統計概要
    public function getStatsOverview() {
        $this->auth->requireLogin();

        $stats = $this->db->getSystemStatistics();
        if ($stats === false) {
            throw new Exception('Failed to retrieve statistics');
        }

        return [
            'success' => true,
            'total_projects' => isset($stats['total_projects']) ? $stats['total_projects'] : 0,
            'active_projects' => isset($stats['active_projects']) ? $stats['active_projects'] : 0,
            'total_tasks' => isset($stats['total_tasks']) ? $stats['total_tasks'] : 0,
            'completed_tasks' => isset($stats['completed_tasks']) ? $stats['completed_tasks'] : 0
        ];
    }

    // ユーザープロフィール
    public function getUserProfile() {
        $this->auth->requireLogin();
        $user = $this->auth->getCurrentUser();
        return [
            'success' => true,
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
    }

    // テンプレート一覧取得
    public function getTemplates() {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        error_log("=== API getTemplates called ===");

        try {
            error_log("Calling database getAllTemplates...");
            $templates = $this->db->getAllTemplates();
            error_log("Database getAllTemplates returned: " . gettype($templates));
            
            if ($templates === false) {
                error_log("getAllTemplates returned false - database error");
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve templates from database',
                    'templates' => []
                ];
            }

            if (!is_array($templates)) {
                error_log("getAllTemplates returned non-array: " . gettype($templates));
                return [
                    'success' => false,
                    'error' => 'Invalid template data format',
                    'templates' => []
                ];
            }

            error_log("getTemplates returning success with " . count($templates) . " templates");
            return [
                'success' => true,
                'templates' => $templates
            ];
        } catch (Exception $e) {
            error_log("getTemplates error: " . $e->getMessage());
            error_log("getTemplates error trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'templates' => []
            ];
        }
    }

    // マニュアル一覧取得
    public function getManuals() {
        // $this->auth->requireLogin(); // 一時的に無効化

        $manuals = $this->db->getAllManuals();
        if ($manuals === false) {
            throw new Exception('Failed to retrieve manuals');
        }

        return [
            'success' => true,
            'manuals' => $manuals
        ];
    }

    public function getManualsByTaskName($taskName) {
        // $this->auth->requireLogin(); // 一時的に無効化
        $manuals = $this->db->getManualsByTaskName($taskName);
        if ($manuals === false) {
            throw new Exception('Failed to retrieve manuals for task');
        }
        return [
            'success' => true,
            'manuals' => $manuals
        ];
    }

    // タスク一覧取得
    public function getTasks() {
        $this->auth->requireLogin();

        $tasks = $this->db->getAllTasks();
        if ($tasks === false) {
            throw new Exception('Failed to retrieve tasks');
        }

        return [
            'success' => true,
            'tasks' => $tasks
        ];
    }



    // フェーズ作成（一般ユーザー向け）
    public function createPhase($input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $name = trim(isset($input['name']) ? $input['name'] : '');
        $description = trim(isset($input['description']) ? $input['description'] : '');
        $orderNum = intval(isset($input['order_num']) ? $input['order_num'] : 1);

        if (empty($name)) {
            throw new Exception('Phase name is required', 400);
        }

        $result = $this->db->createPhase($name, $description, $orderNum);
        if (!$result) {
            throw new Exception('Failed to create phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが作成されました'
        ];
    }

    // フェーズ詳細取得
    public function getPhase($id) {
        // $this->auth->requireLogin(); // 一時的に無効化

        $phase = $this->db->getPhaseById($id);
        if (!$phase) {
            throw new Exception('Phase not found', 404);
        }

        return [
            'success' => true,
            'phase' => $phase
        ];
    }

    // フェーズ更新
    public function updatePhase($id, $input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $name = trim(isset($input['name']) ? $input['name'] : '');
        $description = trim(isset($input['description']) ? $input['description'] : '');
        $orderNum = intval(isset($input['order_num']) ? $input['order_num'] : 1);
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        if (empty($name)) {
            throw new Exception('Phase name is required', 400);
        }

        $result = $this->db->updatePhase($id, $name, $description, $orderNum, $isActive);
        if (!$result) {
            throw new Exception('Failed to update phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが更新されました'
        ];
    }

    // フェーズ削除
    public function deletePhase($id) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $result = $this->db->deletePhase($id);
        if (!$result) {
            throw new Exception('Failed to delete phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが削除されました'
        ];
    }

    // クライアント一覧取得（一般ユーザー向け）
    public function getClients() {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();

        try {
            error_log("getClients called");
            $clients = $this->db->getAllClients();
            error_log("getClients database result: " . json_encode($clients));
            return ['success' => true, 'clients' => $clients];
        } catch (Exception $e) {
            error_log("getClients error: " . $e->getMessage());
            return ['success' => false, 'message' => 'クライアント一覧の取得に失敗しました'];
        }
    }

    // 管理用クライアント一覧取得
    public function getAdminClients() {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        try {
            $clients = $this->db->getAllClients();
            return ['success' => true, 'clients' => $clients];
        } catch (Exception $e) {
            error_log("getAdminClients error: " . $e->getMessage());
            return ['success' => false, 'message' => 'クライアント一覧の取得に失敗しました'];
        }
    }

    // 管理用クライアント作成
    public function createAdminClient($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        error_log("createAdminClient called with input: " . print_r($input, true));

        $name = trim(isset($input['name']) ? $input['name'] : '');
        $phone = trim(isset($input['phone']) ? $input['phone'] : '');
        $address = trim(isset($input['address']) ? $input['address'] : '');
        $description = trim(isset($input['description']) ? $input['description'] : '');
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        error_log("Parsed values - name: '$name', phone: '$phone', address: '$address', description: '$description', isActive: '$isActive'");

        if (empty($name)) {
            error_log("Client name is empty");
            throw new Exception('クライアント名は必須です', 400);
        }

        try {
            error_log("Calling database createClient with: name='$name', code='', contactPerson='', email='', phone='$phone', address='$address', description='$description', isActive='$isActive'");
            $result = $this->db->createClient($name, '', '', '', $phone, $address, $description, $isActive);
            error_log("Database createClient result: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                error_log("Client created successfully");
                return ['success' => true, 'message' => 'クライアントが作成されました'];
            } else {
                error_log("Database createClient returned false");
                return ['success' => false, 'message' => 'クライアントの作成に失敗しました'];
            }
        } catch (Exception $e) {
            error_log("createAdminClient error: " . $e->getMessage());
            error_log("createAdminClient error trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'クライアントの作成に失敗しました: ' . $e->getMessage()];
        }
    }

    // 管理用クライアント詳細取得
    public function getAdminClient($id) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        try {
            $client = $this->db->getClientById($id);
            if ($client) {
                return ['success' => true, 'client' => $client];
            } else {
                return ['success' => false, 'message' => 'クライアントが見つかりません'];
            }
        } catch (Exception $e) {
            error_log("getAdminClient error: " . $e->getMessage());
            return ['success' => false, 'message' => 'クライアント詳細の取得に失敗しました'];
        }
    }

    // 管理用クライアント更新
    public function updateAdminClient($id, $input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        $name = trim(isset($input['name']) ? $input['name'] : '');
        $phone = trim(isset($input['phone']) ? $input['phone'] : '');
        $address = trim(isset($input['address']) ? $input['address'] : '');
        $description = trim(isset($input['description']) ? $input['description'] : '');
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        if (empty($name)) {
            throw new Exception('クライアント名は必須です', 400);
        }

        try {
            $result = $this->db->updateClient($id, $name, '', '', '', $phone, $address, $description, $isActive);
            if ($result) {
                return ['success' => true, 'message' => 'クライアントが更新されました'];
            } else {
                return ['success' => false, 'message' => 'クライアントの更新に失敗しました'];
            }
        } catch (Exception $e) {
            error_log("updateAdminClient error: " . $e->getMessage());
            return ['success' => false, 'message' => 'クライアントの更新に失敗しました'];
        }
    }

    // 管理用クライアント削除
    public function deleteAdminClient($id) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        try {
            $result = $this->db->deleteClient($id);
            if ($result) {
                return ['success' => true, 'message' => 'クライアントが削除されました'];
            } else {
                return ['success' => false, 'message' => 'クライアントの削除に失敗しました'];
            }
        } catch (Exception $e) {
            error_log("deleteAdminClient error: " . $e->getMessage());
            return ['success' => false, 'message' => 'クライアントの削除に失敗しました'];
        }
    }

    // 管理用テンプレート作成
    public function createAdminTemplate($input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $phaseName = trim(isset($input['phase_name']) ? $input['phase_name'] : '');
        $taskName = trim(isset($input['task_name']) ? $input['task_name'] : '');
        $content = trim(isset($input['content']) ? $input['content'] : '');
        $taskOrder = intval(isset($input['task_order']) ? $input['task_order'] : 1);
        $isTechnicalWork = isset($input['is_technical_work']) ? ($input['is_technical_work'] == '1' ? 1 : 0) : 0;
        // マニュアルありは自動設定のため、常に0で作成
        $hasManual = 0;

        if (empty($phaseName) || empty($taskName)) {
            throw new Exception('Phase name and task name are required', 400);
        }

        $result = $this->db->createTaskTemplate($phaseName, $taskName, $content, $taskOrder, $isTechnicalWork, $hasManual);
        if (!$result) {
            throw new Exception('Failed to create template');
        }

        return [
            'success' => true,
            'message' => 'テンプレートが作成されました'
        ];
    }

    // 管理用テンプレート詳細取得
    public function getAdminTemplate($id) {
        // $this->auth->requireLogin(); // 一時的に無効化

        $template = $this->db->getTaskTemplateById($id);
        if (!$template) {
            throw new Exception('Template not found', 404);
        }

        return [
            'success' => true,
            'template' => $template
        ];
    }

    // 管理用テンプレート更新
    public function updateAdminTemplate($id, $input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $phaseName = trim(isset($input['phase_name']) ? $input['phase_name'] : '');
        $taskName = trim(isset($input['task_name']) ? $input['task_name'] : '');
        $content = trim(isset($input['content']) ? $input['content'] : '');
        $taskOrder = intval(isset($input['task_order']) ? $input['task_order'] : 1);
        $isTechnicalWork = isset($input['is_technical_work']) ? ($input['is_technical_work'] == '1' ? 1 : 0) : 0;
        // マニュアルありは自動設定のため、更新しない

        if (empty($phaseName) || empty($taskName)) {
            throw new Exception('Phase name and task name are required', 400);
        }

        $result = $this->db->updateTaskTemplateWithoutManual($id, $phaseName, $taskName, $content, $taskOrder, $isTechnicalWork);
        if (!$result) {
            throw new Error('Failed to update template');
        }

        return [
            'success' => true,
            'message' => 'テンプレートが更新されました'
        ];
    }

    // 管理用テンプレート削除
    public function deleteAdminTemplate($id) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $result = $this->db->deleteTaskTemplate($id);
        if (!$result) {
            throw new Exception('Failed to delete template');
        }

        return [
            'success' => true,
            'message' => 'テンプレートが削除されました'
        ];
    }

    // 管理用マニュアル作成
    public function createAdminManual() {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        error_log("=== createAdminManual 開始 ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        // ファイルアップロードチェック
        if (!isset($_FILES['file'])) {
            error_log("ファイルがアップロードされていません");
            throw new Exception('ファイルがアップロードされていません', 400);
        }

        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            error_log("ファイルアップロードエラー: " . $_FILES['file']['error']);
            throw new Exception('ファイルのアップロードに失敗しました (エラーコード: ' . $_FILES['file']['error'] . ')', 400);
        }

        $file = $_FILES['file'];
        $taskName = trim(isset($_POST['task_name']) ? $_POST['task_name'] : '');
        $description = trim(isset($_POST['description']) ? $_POST['description'] : '');

        error_log("タスク名: " . $taskName);
        error_log("説明: " . $description);

        if (empty($taskName)) {
            error_log("タスク名が空です");
            throw new Exception('タスク名は必須です', 400);
        }

        // ファイル形式チェック
        $allowedExtensions = ['pdf', 'xlsx', 'xls', 'docx', 'doc'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        error_log("ファイル拡張子: " . $fileExtension);

        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("許可されていないファイル形式: " . $fileExtension);
            throw new Exception('対応していないファイル形式です。PDF、Excel、Wordファイルのみアップロード可能です。', 400);
        }

        // ファイルサイズチェック（10MB制限）
        $maxSize = 10 * 1024 * 1024;
        error_log("ファイルサイズ: " . $file['size'] . " bytes (制限: " . $maxSize . " bytes)");
        if ($file['size'] > $maxSize) {
            error_log("ファイルサイズが制限を超えています");
            throw new Exception('ファイルサイズは10MB以下にしてください', 400);
        }

        // アップロードディレクトリ作成
        $uploadDir = 'uploads/manuals/';
        error_log("アップロードディレクトリ: " . $uploadDir);
        
        if (!is_dir($uploadDir)) {
            error_log("ディレクトリが存在しないため作成します");
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("ディレクトリ作成に失敗しました");
                throw new Exception('アップロードディレクトリの作成に失敗しました', 500);
            }
        }

        if (!is_writable($uploadDir)) {
            error_log("ディレクトリに書き込み権限がありません");
            throw new Exception('アップロードディレクトリに書き込み権限がありません', 500);
        }

        // ファイル名の重複チェックとユニーク名生成
        $originalName = $file['name'];
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $filePath = $uploadDir . $fileName;
        
        error_log("元のファイル名: " . $originalName);
        error_log("保存ファイル名: " . $fileName);
        error_log("保存パス: " . $filePath);

        // ファイルアップロード
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            error_log("ファイルの移動に失敗しました");
            error_log("一時ファイル: " . $file['tmp_name']);
            error_log("移動先: " . $filePath);
            throw new Exception('ファイルの保存に失敗しました', 500);
        }

        error_log("ファイルアップロード成功");

        // データベースに保存
        $result = $this->db->createManual($taskName, $fileName, $originalName, $description, $file['size']);
        if (!$result) {
            error_log("データベース保存に失敗しました");
            // データベース保存に失敗した場合、ファイルを削除
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            throw new Exception('マニュアルの保存に失敗しました', 500);
        }

        // 対応するタスクテンプレートの「マニュアルあり」を更新
        $templateUpdateResult = $this->db->updateTaskTemplateHasManual($taskName);
        if ($templateUpdateResult) {
            error_log("タスクテンプレートのマニュアルありを更新しました: " . $taskName);
        } else {
            error_log("タスクテンプレートの更新に失敗しました: " . $taskName);
        }

        error_log("=== createAdminManual 成功 ===");
        return [
            'success' => true,
            'message' => 'マニュアルが正常にアップロードされました'
        ];
    }

    // 管理用マニュアル削除
    public function deleteAdminManual($id) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        try {
            // 削除前にマニュアル情報を取得
            $manual = $this->db->getManualById($id);
            if (!$manual) {
                return ['success' => false, 'message' => 'マニュアルが見つかりません'];
            }

            $result = $this->db->deleteManual($id);
            if (!$result) {
                return ['success' => false, 'message' => 'マニュアルの削除に失敗しました'];
            }

            // 対応するタスクテンプレートの「マニュアルあり」をチェック解除
            if ($manual['task_name']) {
                // 同じタスク名の他のマニュアルが存在するかチェック
                $otherManuals = $this->db->getManualsByTaskName($manual['task_name']);
                $hasOtherManuals = count($otherManuals) > 0;
                
                if (!$hasOtherManuals) {
                    // 他のマニュアルが存在しない場合のみチェック解除
                    $templateUpdateResult = $this->db->updateTaskTemplateHasManual($manual['task_name'], false);
                    if ($templateUpdateResult) {
                        error_log("タスクテンプレートのマニュアルありをチェック解除しました: " . $manual['task_name']);
                    } else {
                        error_log("タスクテンプレートの更新に失敗しました: " . $manual['task_name']);
                    }
                } else {
                    error_log("同じタスク名の他のマニュアルが存在するため、チェック解除しません: " . $manual['task_name']);
                }
            }

            return [
                'success' => true,
                'message' => 'マニュアルが削除されました'
            ];
        } catch (Exception $e) {
            error_log("deleteAdminManual error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'マニュアルの削除に失敗しました: ' . $e->getMessage()
            ];
        }
    }

    // 管理用マニュアルダウンロード
    public function downloadAdminManual($id) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('manager');

        try {
            error_log("downloadAdminManual called with id: $id");
            
            // 旧実装ではディレクトリ未存在時に即エラーにしていたが、
            // DBの file_path が絶対/相対で保存されているケースがあるため、
            // ディレクトリ存在は致命ではない。ログのみに留める。
            $uploadDir = __DIR__ . '/uploads/manuals/';
            if (!is_dir($uploadDir)) {
                error_log("[downloadAdminManual] Upload directory not found (will continue): $uploadDir");
            }
            
            // マニュアル情報を取得
            $manual = $this->db->getManualById($id);
            if (!$manual) {
                error_log("Manual not found with id: $id");
                throw new Exception('マニュアルが見つかりません', 404);
            }

            error_log("Found manual: " . print_r($manual, true));

            $filePath = isset($manual['file_path']) ? $manual['file_path'] : '';
            $originalName = isset($manual['original_name']) && $manual['original_name'] ? $manual['original_name'] : (isset($manual['file_name']) ? $manual['file_name'] : 'manual');

            // URLが保存されている場合はリダイレクト（外部ストレージ対応）
            if (preg_match('/^https?:\/\//i', $filePath)) {
                header('Location: ' . $filePath);
                exit;
            }

            // パス正規化（Windowsのバックスラッシュをスラッシュへ）
            $filePath = str_replace('\\\\', '/', $filePath);
            $filePath = str_replace('\\', '/', $filePath);

            error_log("DB file_path(norm): $filePath, original_name: $originalName");

            // 候補パスを順に検証
            $candidates = [];
            if ($filePath) { $candidates[] = $filePath; }
            if ($filePath && strpos($filePath, __DIR__) !== 0) { $candidates[] = __DIR__ . '/' . ltrim($filePath, '/\\'); }
            if (!empty($manual['file_name'])) { $candidates[] = rtrim($uploadDir, '/\\') . '/' . $manual['file_name']; }

            $resolved = '';
            foreach ($candidates as $p) {
                if (is_string($p) && $p !== '' && file_exists($p)) { $resolved = $p; break; }
            }

            if ($resolved === '') {
                error_log('[downloadAdminManual] None of candidate paths exist: ' . implode(' | ', $candidates));
                throw new Exception('ファイルが見つかりません', 404);
            }

            $filePath = $resolved;

            // ファイルサイズ確認
            $fileSize = filesize($filePath);
            error_log("File size: $fileSize bytes");

            // ファイルタイプの判定
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $contentType = 'application/octet-stream'; // デフォルト

            switch ($fileExtension) {
                case 'pdf':
                    $contentType = 'application/pdf';
                    break;
                case 'xlsx':
                case 'xls':
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case 'docx':
                case 'doc':
                    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
            }

            error_log("Content type: $contentType");

            // ヘッダー設定（inline 表示を許可。?download=1 で強制ダウンロード）
            $disposition = (isset($_GET['download']) && $_GET['download'] == '1') ? 'attachment' : 'inline';
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: ' . $disposition . '; filename="' . $originalName . '"');
            header('Content-Length: ' . $fileSize);
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            // ファイル出力
            readfile($filePath);
            error_log("File download completed successfully");
            exit;

        } catch (Exception $e) {
            error_log("downloadAdminManual error: " . $e->getMessage());
            error_log("downloadAdminManual error code: " . $e->getCode());
            error_log("downloadAdminManual error file: " . $e->getFile());
            error_log("downloadAdminManual error line: " . $e->getLine());
            
            http_response_code($e->getCode() ?: 500);
            
            // ダウンロードエラーの場合はHTMLレスポンスを返す
            if (headers_sent()) {
                echo '<html><body><h1>ダウンロードエラー</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
            } else {
                header('Content-Type: text/html; charset=utf-8');
                echo '<html><body><h1>ダウンロードエラー</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
            }
            exit;
        }
    }

    // 管理用ユーザー詳細取得
    public function getAdminUser($id) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $user = $this->db->getUserById($id);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }

    // 管理用ユーザー作成
    public function createAdminUser($input) {
        $this->auth->requirePermission('manager');

        $email = trim(isset($input['email']) ? $input['email'] : '');
        $name = trim(isset($input['name']) ? $input['name'] : '');
        $role = trim(isset($input['role']) ? $input['role'] : '');
        $password = isset($input['password']) ? $input['password'] : '';
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        if (empty($email) || empty($name) || empty($role) || empty($password)) {
            throw new Exception('Email, name, role, and password are required', 400);
        }

        // パスワードをハッシュ化
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $result = $this->db->createUser($email, $hashedPassword, $name, $role, $isActive);
        if (!$result) {
            error_log("User creation failed in database layer");
            throw new Exception('ユーザーの作成に失敗しました。データベースエラーが発生した可能性があります。');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが作成されました'
        ];
    }

    // 管理用ユーザー更新
    public function updateAdminUser($id, $input) {
        $this->auth->requirePermission('manager');
        
        error_log("updateAdminUser called with id: $id, input: " . json_encode($input));

        $email = trim(isset($input['email']) ? $input['email'] : '');
        $name = trim(isset($input['name']) ? $input['name'] : '');
        $role = trim(isset($input['role']) ? $input['role'] : '');
        $password = isset($input['password']) ? $input['password'] : null;
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        // パスワードの検証
        if ($password !== null && trim($password) === '') {
            $password = null; // 空文字列はnullに変換
        }

        error_log("Parsed values: email='$email', name='$name', role='$role', password=" . ($password ? 'set' : 'null') . ", isActive=$isActive");
        if ($password) {
            error_log("Password length: " . strlen($password));
        }

        if (empty($email) || empty($name) || empty($role)) {
            throw new Exception('Email, name, and role are required', 400);
        }

        $result = $this->db->updateUser($id, $email, $name, $role, $password, $isActive);
        if (!$result) {
            error_log("Database updateUser failed for user ID: $id");
            throw new Exception('Failed to update user');
        }

        error_log("User updated successfully: ID=$id, name=$name, email=$email");

        return [
            'success' => true,
            'message' => 'ユーザーが更新されました'
        ];
    }

    // 管理用ユーザー削除
    public function deleteAdminUser($id) {
        $this->auth->requirePermission('manager');

        $result = $this->db->deleteUser($id);
        if (!$result) {
            throw new Exception('Failed to delete user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが削除されました'
        ];
    }



    // プロジェクト削除
    public function deleteProject($projectId) {
        error_log("deleteProject called with ID: $projectId");
        
        try {
            // プロジェクトの存在確認
            $project = $this->db->getProjectById($projectId);
            if (!$project) {
                error_log("Project not found: $projectId");
                throw new Exception('プロジェクトが見つかりません', 404);
            }

            error_log("Project found, proceeding with deletion: " . json_encode($project));
            
            // プロジェクト削除（関連するタスクも自動削除される）
            $result = $this->db->deleteProject($projectId);
            if (!$result) {
                error_log("Failed to delete project: $projectId");
                throw new Exception('プロジェクトの削除に失敗しました');
            }

            error_log("Project deleted successfully: $projectId");
            
            return [
                'success' => true,
                'message' => 'プロジェクトが正常に削除されました'
            ];
        } catch (Exception $e) {
            error_log("Error in deleteProject: " . $e->getMessage());
            throw $e;
        }
    }

    // プロジェクトにタスクを追加
    public function addTaskToProject($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("addTaskToProject called with input: " . json_encode($input));
        
        $projectId = isset($input['project_id']) ? $input['project_id'] : null;
        $taskName = trim(isset($input['task_name']) ? $input['task_name'] : '');
        $phaseName = trim(isset($input['phase_name']) ? $input['phase_name'] : '');
        $taskOrder = intval(isset($input['task_order']) ? $input['task_order'] : 0);
        $isTechnicalWork = isset($input['is_technical_work']) ? ($input['is_technical_work'] == '1' || $input['is_technical_work'] === true) : false;
        $hasManual = isset($input['has_manual']) ? ($input['has_manual'] == '1' || $input['has_manual'] === true) : false;
        $estimatedHours = isset($input['estimated_hours']) ? floatval($input['estimated_hours']) : null;
        
        if (!$projectId || empty($taskName) || empty($phaseName)) {
            throw new Exception('Project ID, task name, and phase name are required', 400);
        }
        
        try {
            $taskId = $this->db->addTaskToProject($projectId, $taskName, $phaseName, $taskOrder, $isTechnicalWork, $hasManual, $estimatedHours);
            
            if ($taskId) {
                return [
                    'success' => true,
                    'message' => 'タスクが追加されました',
                    'task_id' => $taskId
                ];
            } else {
                throw new Exception('Failed to add task to project');
            }
        } catch (Exception $e) {
            error_log("addTaskToProject error: " . $e->getMessage());
            throw $e;
        }
    }

    // プロジェクトからタスクを削除
    public function removeTaskFromProject($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("removeTaskFromProject called with input: " . json_encode($input));
        
        $taskId = isset($input['task_id']) ? $input['task_id'] : null;
        
        if (!$taskId) {
            throw new Exception('Task ID is required', 400);
        }
        
        try {
            $result = $this->db->removeTaskFromProject($taskId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'タスクが削除されました'
                ];
            } else {
                throw new Exception('Failed to remove task from project');
            }
        } catch (Exception $e) {
            error_log("removeTaskFromProject error: " . $e->getMessage());
            throw $e;
        }
    }

    // プロジェクトのタスクを更新
    public function updateProjectTask($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("updateProjectTask called with input: " . json_encode($input));
        
        $taskId = isset($input['task_id']) ? $input['task_id'] : null;
        $taskName = trim(isset($input['task_name']) ? $input['task_name'] : '');
        $phaseName = trim(isset($input['phase_name']) ? $input['phase_name'] : '');
        $taskOrder = intval(isset($input['task_order']) ? $input['task_order'] : 0);
        $isTechnicalWork = isset($input['is_technical_work']) ? ($input['is_technical_work'] == '1' || $input['is_technical_work'] === true) : false;
        $hasManual = isset($input['has_manual']) ? ($input['has_manual'] == '1' || $input['has_manual'] === true) : false;
        $estimatedHours = isset($input['estimated_hours']) ? floatval($input['estimated_hours']) : null;
        
        if (!$taskId || empty($taskName) || empty($phaseName)) {
            throw new Exception('Task ID, task name, and phase name are required', 400);
        }
        
        try {
            $result = $this->db->updateProjectTask($taskId, $taskName, $phaseName, $taskOrder, $isTechnicalWork, $hasManual, $estimatedHours);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'タスクが更新されました'
                ];
            } else {
                throw new Exception('Failed to update project task');
            }
        } catch (Exception $e) {
            error_log("updateProjectTask error: " . $e->getMessage());
            throw $e;
        }
    }

    // テンプレートからプロジェクトにタスクを追加
    public function addTasksFromTemplatesToProject($input) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();
        
        error_log("addTasksFromTemplatesToProject called with input: " . json_encode($input));
        
        $projectId = isset($input['project_id']) ? $input['project_id'] : null;
        $templateIds = isset($input['template_ids']) ? $input['template_ids'] : [];
        
        if (!$projectId || empty($templateIds) || !is_array($templateIds)) {
            throw new Exception('Project ID and template IDs are required', 400);
        }
        
        try {
            $result = $this->db->createTasksFromSelectedTemplates($projectId, $templateIds);
            
            if ($result && is_array($result) && $result['success']) {
                $message = '';
                if ($result['created_count'] > 0) {
                    $message .= $result['created_count'] . '個のタスクが追加されました';
                }
                if ($result['skipped_count'] > 0) {
                    if ($message) $message .= '。';
                    $message .= $result['skipped_count'] . '個のタスクは既に存在するためスキップされました';
                }
                if ($result['created_count'] == 0 && $result['skipped_count'] == 0) {
                    $message = '追加可能なタスクがありませんでした';
                }
                
                return [
                    'success' => true,
                    'message' => $message,
                    'created_count' => $result['created_count'],
                    'skipped_count' => $result['skipped_count']
                ];
            } else {
                throw new Exception('Failed to add tasks from templates');
            }
        } catch (Exception $e) {
            error_log("addTasksFromTemplatesToProject error: " . $e->getMessage());
            throw $e;
        }
    }
}

// エラーハンドリング
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error_code' => 500
    ]);
    
    // ログ出力
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
});

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// メインの処理実行
try {
    $api = new ApiController();
    $result = $api->handleRequest();
    
    if ($result === null) {
        // ルートが見つからない場合
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'APIエンドポイントが見つかりません',
            'error_code' => 404
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $statusCode
    ], JSON_UNESCAPED_UNICODE);
}
?>