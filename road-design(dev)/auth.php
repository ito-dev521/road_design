<?php
require_once 'config.php';
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($email, $password) {
        // 入力値検証
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'メールアドレスとパスワードを入力してください。'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '有効なメールアドレスを入力してください。'];
        }
        
        // ユーザー取得
        $user = $this->db->getUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'メールアドレスまたはパスワードが正しくありません。'];
        }
        
        // パスワード確認
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'メールアドレスまたはパスワードが正しくありません。'];
        }
        
        // アカウント有効性確認
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'このアカウントは無効化されています。管理者にお問い合わせください。'];
        }
        
        // セッション開始
        $this->startSession();
        
        // セッションデータ設定
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = $this->generateCSRFToken();
        
        // 最終ログイン時刻更新
        $this->db->updateUserLastLogin($user['id']);
        
        return [
            'success' => true, 
            'message' => 'ログインしました。', 
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
    
    public function logout() {
        $this->startSession();
        
        // セッションデータ削除
        $_SESSION = array();
        
        // セッションクッキー削除
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // セッション破棄
        session_destroy();
        
        return ['success' => true, 'message' => 'ログアウトしました。'];
    }
    
    public function isLoggedIn() {
        $this->startSession();
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // セッションタイムアウト確認
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        // セッション更新
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
    
    public function hasPermission($requiredRole) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $roleHierarchy = [
            'general' => 1,
            'technical' => 2,
            'manager' => 3
        ];
        
        $userLevel = isset($roleHierarchy[$user['role']]) ? $roleHierarchy[$user['role']] : 0;
        $requiredLevel = isset($roleHierarchy[$requiredRole]) ? $roleHierarchy[$requiredRole] : 999;
        
        return $userLevel >= $requiredLevel;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ログインが必要です。']);
                exit;
            } else {
                header('Location: login.html');
                exit;
            }
        }
    }
    
    public function requirePermission($requiredRole) {
        $this->requireLogin();
        
        if (!$this->hasPermission($requiredRole)) {
            if ($this->isAjaxRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '権限が不足しています。']);
                exit;
            } else {
                http_response_code(403);
                echo '<!DOCTYPE html><html><head><title>アクセス拒否</title></head><body><h1>アクセス拒否</h1><p>この操作を実行する権限がありません。</p><a href="index.html">戻る</a></body></html>';
                exit;
            }
        }
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // セキュアなセッション設定
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
            
            // セッションハイジャック対策
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    // パスワード変更（将来の機能拡張用）
    public function changePassword($userId, $currentPassword, $newPassword) {
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'パスワードは' . PASSWORD_MIN_LENGTH . '文字以上である必要があります。'];
        }
        
        // 現在のパスワード確認処理（実装省略）
        // 新しいパスワードのハッシュ化と更新処理（実装省略）
        
        return ['success' => true, 'message' => 'パスワードが変更されました。'];
    }
}

// セッションチェック関数（他のファイルから簡単に使用するため）
function requireLogin() {
    $auth = new Auth();
    $auth->requireLogin();
}

function requirePermission($role) {
    $auth = new Auth();
    $auth->requirePermission($role);
}

function getCurrentUser() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}
?>