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
                        }
                    }
                    break;

                case (preg_match('/^admin\/manuals$/', $path) ? $path : !$path):
                    if ($path === 'admin/manuals') {
                        if ($method === 'GET') {
                            return $this->getAdminManuals();
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
        // ゲストアクセスも許可（認証不要）
        $projects = $this->db->getAllProjects();
        if ($projects === false) {
            throw new Exception('Failed to retrieve projects');
        }

        return [
            'success' => true,
            'projects' => $projects
        ];
    }
    
    public function createProject($input) {
        $this->auth->requirePermission('technical'); // 技術者以上の権限が必要
        
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
        $createdBy = $this->auth->getCurrentUser()['id'];
        
        // 日付フォーマット検証
        if ($startDate && !$this->isValidDate($startDate)) {
            throw new Exception('Invalid start date format', 400);
        }
        if ($targetEndDate && !$this->isValidDate($targetEndDate)) {
            throw new Exception('Invalid end date format', 400);
        }
        
        $projectId = $this->db->createProject($name, $description, $clientName, $projectCode, $startDate, $targetEndDate, $createdBy);
        
        if (!$projectId) {
            throw new Exception('Failed to create project');
        }
        
        return [
            'success' => true,
            'message' => 'プロジェクトが作成されました',
            'project_id' => $projectId
        ];
    }
    
    public function getProject($projectId) {
        $this->auth->requireLogin();
        
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
        $this->auth->requireLogin();
        
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
        $this->auth->requirePermission('manager'); // 管理者権限必須

        $users = $this->db->getAllUsers();
        if ($users === false) {
            throw new Exception('Failed to retrieve users');
        }

        return [
            'success' => true,
            'users' => $users
        ];
    }

    public function getAdminUser($userId) {
        $this->auth->requirePermission('manager');

        if (!is_numeric($userId)) {
            throw new Exception('Invalid user ID', 400);
        }

        $user = $this->db->getUserById($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }

    public function createAdminUser($input) {
        $this->auth->requirePermission('manager');

        // 入力値検証
        $required = ['email', 'name', 'role', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required", 400);
            }
        }

        $email = trim($input['email']);
        $name = trim($input['name']);
        $role = $input['role'];
        $password = $input['password'];

        // メールアドレス形式チェック
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format', 400);
        }

        // 権限チェック
        $validRoles = ['manager', 'technical', 'general'];
        if (!in_array($role, $validRoles)) {
            throw new Exception('Invalid role', 400);
        }

        // パスワード長チェック
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters', 400);
        }

        // パスワードハッシュ化
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->db->createUser($email, $passwordHash, $name, $role);
        if (!$result) {
            throw new Exception('Failed to create user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが作成されました',
            'user_id' => $result
        ];
    }

    public function updateAdminUser($userId, $input) {
        $this->auth->requirePermission('manager');

        if (!is_numeric($userId)) {
            throw new Exception('Invalid user ID', 400);
        }

        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? '';
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;

        // 権限チェック
        $validRoles = ['manager', 'technical', 'general'];
        if ($role && !in_array($role, $validRoles)) {
            throw new Exception('Invalid role', 400);
        }

        $result = $this->db->updateUser($userId, $name, $role, $isActive);
        if (!$result) {
            throw new Exception('Failed to update user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが更新されました'
        ];
    }

    public function deleteAdminUser($userId) {
        $this->auth->requirePermission('manager');

        if (!is_numeric($userId)) {
            throw new Exception('Invalid user ID', 400);
        }

        // 自分自身を削除できないようにする
        $currentUser = $this->auth->getCurrentUser();
        if ($currentUser['id'] == $userId) {
            throw new Exception('Cannot delete your own account', 400);
        }

        $result = $this->db->deleteUser($userId);
        if (!$result) {
            throw new Exception('Failed to delete user');
        }

        return [
            'success' => true,
            'message' => 'ユーザーが削除されました'
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
        $this->auth->requirePermission('manager');

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
        $this->auth->requirePermission('manager');

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
        $this->auth->requirePermission('manager');

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
        $this->auth->requirePermission('manager');

        $result = $this->db->deletePhase($phaseName);
        if (!$result) {
            throw new Exception('Failed to delete phase');
        }

        return [
            'success' => true,
            'message' => 'フェーズが削除されました'
        ];
    }

    // テンプレート一覧（一般ユーザー向け）
    public function getTemplates() {
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

    // マニュアル一覧（一般ユーザー向け）
    public function getManuals() {
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

    // タスク一覧
    public function getTasks() {
        $this->auth->requireLogin();

        $projectId = $_GET['project_id'] ?? null;
        
        if ($projectId) {
            $tasks = $this->db->getProjectTasks($projectId);
        } else {
            $tasks = $this->db->getAllTasks();
        }

        if ($tasks === false) {
            throw new Exception('Failed to retrieve tasks');
        }

        return [
            'success' => true,
            'tasks' => $tasks
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
        $this->auth->requireLogin();

        $user = $this->auth->getCurrentUser();
        if (!$user) {
            throw new Exception('User not found');
        }

        return [
            'success' => true,
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
    }

    // フェーズ作成（一般ユーザー向け）
    public function createPhase($input) {
        $this->auth->requirePermission('manager');

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