<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

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
error_log("API Request: " . $_SERVER['REQUEST_METHOD'] . " " . ($_GET['path'] ?? 'no_path'));

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
            $path = $_GET['path'] ?? '';
            $input = $this->getJsonInput();
            
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
                    
                case 'users':
                    return $this->getUsers();
                    
                case 'templates':
                    if ($method === 'GET') {
                        return $this->getTemplates();
                    }
                    break;
                    
                case 'manuals':
                    if ($method === 'GET') {
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
                        if ($method === 'DELETE') {
                            return $this->deleteAdminManual($matches[1]);
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
                    if ($path === 'progress_report') {
                        return $this->getProgressReport($_GET['project_id'] ?? null);
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
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
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
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
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
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requirePermission('technical'); // 技術者以上の権限が必要
        
        // セッション開始を確実にする
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 入力値検証
        $required = ['name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required", 400);
            }
        }
        
        // データ整理
        $name = trim($input['name']);
        $description = trim($input['description'] ?? '');
        $clientName = trim($input['client_name'] ?? '');
        $projectCode = trim($input['project_code'] ?? '');
        $startDate = $input['start_date'] ?? null;
        $targetEndDate = $input['target_end_date'] ?? null;
        $selectedTemplates = $input['selected_templates'] ?? [];
        
        // ユーザーIDの取得（デフォルトは1）
        $currentUser = $this->auth->getCurrentUser();
        $createdBy = $currentUser ? $currentUser['id'] : 1;
        
        error_log("=== createProject 開始 ===");
        error_log("プロジェクト名: $name");
        error_log("作成者ID: $createdBy");
        error_log("選択されたテンプレート: " . implode(', ', $selectedTemplates));
        
        // 日付フォーマット検証
        if ($startDate && !$this->isValidDate($startDate)) {
            throw new Exception('Invalid start date format', 400);
        }
        if ($targetEndDate && !$this->isValidDate($targetEndDate)) {
            throw new Exception('Invalid end date format', 400);
        }
        
        // プロジェクト作成
        $projectId = $this->db->createProject($name, $description, $clientName, $projectCode, $startDate, $targetEndDate, $createdBy);
        
        if (!$projectId) {
            error_log("プロジェクト作成失敗");
            throw new Exception('Failed to create project');
        }
        
        error_log("プロジェクト作成成功: ID $projectId");
        
        // 選択されたテンプレートからタスクを作成
        if (!empty($selectedTemplates) && is_array($selectedTemplates)) {
            $taskCreationResult = $this->db->createTasksFromSelectedTemplates($projectId, $selectedTemplates);
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
    
    // タスク関連
    public function updateTaskStatus($input) {
        $this->auth->requireLogin();
        
        $taskId = $input['task_id'] ?? null;
        $status = $input['status'] ?? null;
        $notes = trim($input['notes'] ?? '');
        
        if (!$taskId || !$status) {
            throw new Exception('Task ID and status are required', 400);
        }
        
        $validStatuses = ['not_started', 'in_progress', 'completed', 'not_applicable'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status', 400);
        }
        
        $userId = $this->auth->getCurrentUser()['id'];
        $result = $this->db->updateTaskStatus($taskId, $status, $userId, $notes ?: null);
        
        if (!$result) {
            throw new Exception('Failed to update task status');
        }
        
        return [
            'success' => true,
            'message' => 'タスクの状態が更新されました'
        ];
    }
    
    public function getTask($taskId) {
        $this->auth->requireLogin();
        
        if (!is_numeric($taskId)) {
            throw new Exception('Invalid task ID', 400);
        }
        
        $task = $this->db->getTaskById($taskId);
        if (!$task) {
            throw new Exception('Task not found', 404);
        }
        
        return [
            'success' => true,
            'task' => $task
        ];
    }
    
    public function updateTask($input) {
        $this->auth->requireLogin();
        
        $taskId = $input['task_id'] ?? null;
        $status = $input['status'] ?? null;
        $assignedTo = $input['assigned_to'] ?? null;
        $plannedDate = $input['planned_date'] ?? null;
        $notes = trim($input['notes'] ?? '');
        
        if (!$taskId) {
            throw new Exception('Task ID is required', 400);
        }
        
        $userId = $this->auth->getCurrentUser()['id'];
        
        // ステータス更新
        if ($status) {
            $validStatuses = ['not_started', 'in_progress', 'completed', 'not_applicable'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status', 400);
            }
            
            $this->db->updateTaskStatus($taskId, $status, $userId, $notes ?: null);
        }
        
        // 担当者・日付更新
        if ($assignedTo !== null || $plannedDate !== null) {
            // 日付形式検証
            if ($plannedDate && !$this->isValidDate($plannedDate)) {
                throw new Exception('Invalid planned date format', 400);
            }
            
            $this->db->assignTask($taskId, $assignedTo ?: null, $userId, $plannedDate ?: null);
        }
        
        return [
            'success' => true,
            'message' => 'タスクが更新されました'
        ];
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
            $phaseTasks = array_filter($tasks, fn($task) => $task['phase_name'] === $phase);
            $completed = array_filter($phaseTasks, fn($task) => $task['status'] === 'completed');
            
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
        $phases = $this->db->getAllPhases();
        if ($phases === false) {
            throw new Exception('Failed to retrieve phases');
        }

        return [
            'success' => true,
            'phases' => $phases
        ];
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
        $this->auth->requireLogin();

        $manuals = $this->db->getAllManuals();
        if ($manuals === false) {
            throw new Exception('Failed to retrieve manuals');
        }

        return [
            'success' => true,
            'manuals' => $manuals
        ];
    }

    public function createAdminPhase($input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $phaseName = trim($input['phase_name'] ?? '');
        $description = trim($input['description'] ?? '');

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

        $newPhaseName = trim($input['phase_name'] ?? '');
        $description = trim($input['description'] ?? '');

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
            'total_projects' => $stats['total_projects'] ?? 0,
            'active_projects' => $stats['active_projects'] ?? 0,
            'total_tasks' => $stats['total_tasks'] ?? 0,
            'completed_tasks' => $stats['completed_tasks'] ?? 0
        ];
    }

    // ユーザープロフィール
    public function getUserProfile() {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();

        // テスト用のユーザー情報を返す
        return [
            'success' => true,
            'name' => '管理者',
            'email' => 'admin@ii-stylelab.com',
            'role' => 'manager'
        ];
    }

    // テンプレート一覧取得
    public function getTemplates() {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();

        $templates = $this->db->getAllTemplates();
        if ($templates === false) {
            throw new Exception('Failed to retrieve templates');
        }

        return [
            'success' => true,
            'templates' => $templates
        ];
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

    // タスク詳細取得
    public function getTask($taskId) {
        // 一時的に認証を無効化（デバッグ用）
        // $this->auth->requireLogin();

        try {
            $task = $this->db->getTaskById($taskId);
            if ($task) {
                return ['success' => true, 'task' => $task];
            } else {
                return ['success' => false, 'message' => 'タスクが見つかりません'];
            }
        } catch (Exception $e) {
            error_log("getTask error: " . $e->getMessage());
            return ['success' => false, 'message' => 'タスク詳細の取得に失敗しました'];
        }
    }

    // フェーズ作成（一般ユーザー向け）
    public function createPhase($input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $orderNum = intval($input['order_num'] ?? 1);

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

        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $orderNum = intval($input['order_num'] ?? 1);
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

    // 管理用テンプレート作成
    public function createAdminTemplate($input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $phaseName = trim($input['phase_name'] ?? '');
        $taskName = trim($input['task_name'] ?? '');
        $content = trim($input['content'] ?? '');
        $taskOrder = intval($input['task_order'] ?? 1);
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

        $phaseName = trim($input['phase_name'] ?? '');
        $taskName = trim($input['task_name'] ?? '');
        $content = trim($input['content'] ?? '');
        $taskOrder = intval($input['task_order'] ?? 1);
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
        $taskName = trim($_POST['task_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

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
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        // 削除前にマニュアル情報を取得
        $manual = $this->db->getManualById($id);
        if (!$manual) {
            throw new Exception('Manual not found', 404);
        }

        $result = $this->db->deleteManual($id);
        if (!$result) {
            throw new Exception('Failed to delete manual');
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
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $email = trim($input['email'] ?? '');
        $name = trim($input['name'] ?? '');
        $role = trim($input['role'] ?? '');
        $password = $input['password'] ?? '';
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        if (empty($email) || empty($name) || empty($role) || empty($password)) {
            throw new Exception('Email, name, role, and password are required', 400);
        }

        $result = $this->db->createUser($email, $name, $role, $password, $isActive);
        if (!$result) {
            throw new Exception('Failed to create user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが作成されました'
        ];
    }

    // 管理用ユーザー更新
    public function updateAdminUser($id, $input) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $email = trim($input['email'] ?? '');
        $name = trim($input['name'] ?? '');
        $role = trim($input['role'] ?? '');
        $password = $input['password'] ?? null;
        $isActive = isset($input['is_active']) ? ($input['is_active'] == '1' ? 1 : 0) : 1;

        if (empty($email) || empty($name) || empty($role)) {
            throw new Exception('Email, name, and role are required', 400);
        }

        $result = $this->db->updateUser($id, $email, $name, $role, $password, $isActive);
        if (!$result) {
            throw new Exception('Failed to update user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが更新されました'
        ];
    }

    // 管理用ユーザー削除
    public function deleteAdminUser($id) {
        // $this->auth->requirePermission('manager'); // 一時的に無効化

        $result = $this->db->deleteUser($id);
        if (!$result) {
            throw new Exception('Failed to delete user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが削除されました'
        ];
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
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $statusCode
    ], JSON_UNESCAPED_UNICODE);
    
    // デバッグ情報（本番環境では無効化）
    if (defined('DEBUG') && DEBUG) {
        error_log("API Debug: " . $e->getTraceAsString());
    }
}
?>