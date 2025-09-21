-- 道路詳細設計管理システム データベーススキーマ
-- 文字セット: utf8mb4

-- ユーザーテーブル
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('manager', 'technical', 'general') DEFAULT 'general',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- フェーズテーブル
CREATE TABLE phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    order_num INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order (order_num),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- プロジェクトテーブル
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    client_name VARCHAR(255),
    project_code VARCHAR(50),
    start_date DATE,
    target_end_date DATE,
    actual_end_date DATE,
    status ENUM('planning', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, target_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- タスクテンプレートテーブル（事前定義されたタスク）
CREATE TABLE task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phase_name VARCHAR(100) NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_order INT DEFAULT 0,
    is_technical_work BOOLEAN DEFAULT FALSE,
    has_manual BOOLEAN DEFAULT FALSE,
    estimated_hours DECIMAL(4,1) DEFAULT NULL,
    description TEXT,
    INDEX idx_phase (phase_name),
    INDEX idx_order (task_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- プロジェクトタスクテーブル
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    template_id INT,
    phase_name VARCHAR(100) NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_order INT DEFAULT 0,
    status ENUM('not_started', 'in_progress', 'completed', 'not_applicable', 'needs_confirmation', 'pending') DEFAULT 'not_started',
    assigned_to INT NULL,
    planned_date DATE NULL,
    actual_start_date DATE NULL,
    actual_end_date DATE NULL,
    is_technical_work BOOLEAN DEFAULT FALSE,
    has_manual BOOLEAN DEFAULT FALSE,
    estimated_hours DECIMAL(4,1) DEFAULT NULL,
    actual_hours DECIMAL(4,1) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES task_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_phase_order (phase_name, task_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- タスクメモテーブル
CREATE TABLE task_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task (task_id),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- プロジェクト履歴テーブル
CREATE TABLE project_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    task_id INT NULL,
    user_id INT NOT NULL,
    action_type ENUM('created', 'updated', 'status_changed', 'assigned', 'note_added') NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- システム設定テーブル
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- マニュアルテーブル
CREATE TABLE manuals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    description TEXT,
    file_size INT NULL,
    uploaded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初期ユーザーデータ
INSERT INTO users (email, password_hash, name, role) VALUES
('admin@ii-stylelab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理者', 'manager'),
('tech@ii-stylelab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '技術者', 'technical'),
('staff@ii-stylelab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '一般スタッフ', 'general');

-- フェーズデータ
INSERT INTO phases (name, description, order_num) VALUES
('フェーズ1', '基本設計・調査', 1),
('フェーズ2', '詳細設計', 2),
('フェーズ3', '施工・監理', 3);

-- タスクテンプレートデータ
INSERT INTO task_templates (phase_name, task_name, task_order, is_technical_work, has_manual) VALUES
-- フェーズ1: データ入力・整理段階
('フェーズ1', '現地踏査（写真撮影）', 1, FALSE, TRUE),
('フェーズ1', '既存図面・データの収集', 2, FALSE, TRUE),
('フェーズ1', '測量データ整理', 3, TRUE, TRUE),
('フェーズ1', '地質調査データ整理', 4, TRUE, TRUE),
('フェーズ1', '交通量調査データ整理', 5, FALSE, TRUE),
('フェーズ1', '用地境界データ整理', 6, TRUE, TRUE),
('フェーズ1', '埋設物調査データ整理', 7, TRUE, TRUE),
('フェーズ1', '法規制・基準の確認', 8, TRUE, TRUE),
('フェーズ1', '関連機関協議記録整理', 9, FALSE, TRUE),
('フェーズ1', '過去の類似事例調査', 10, FALSE, FALSE),
('フェーズ1', 'データベース入力・整理', 11, FALSE, TRUE),
('フェーズ1', '基礎データチェック', 12, TRUE, TRUE),

-- フェーズ2: 設計条件の整理
('フェーズ2', '道路構造基準の選定', 1, TRUE, TRUE),
('フェーズ2', '設計速度・線形基準の決定', 2, TRUE, TRUE),
('フェーズ2', '交差点・取付道路計画', 3, TRUE, TRUE),
('フェーズ2', '排水計画・流域検討', 4, TRUE, TRUE),
('フェーズ2', '舗装構成の検討', 5, TRUE, TRUE),
('フェーズ2', '安全施設・付帯設備計画', 6, TRUE, TRUE),

-- フェーズ3: 平面設計
('フェーズ3', '路線計画・線形検討', 1, TRUE, TRUE),
('フェーズ3', 'IP座標・線形要素計算', 2, TRUE, TRUE),
('フェーズ3', '平面線形図作成', 3, TRUE, TRUE),
('フェーズ3', '幅杭計算・設置', 4, TRUE, TRUE),
('フェーズ3', '用地境界との調整', 5, TRUE, TRUE),
('フェーズ3', '交差点詳細設計', 6, TRUE, TRUE),
('フェーズ3', '側道・取付道路設計', 7, TRUE, TRUE),
('フェーズ3', '排水施設配置計画', 8, TRUE, TRUE),
('フェーズ3', '安全施設配置計画', 9, TRUE, TRUE),
('フェーズ3', '平面図データ整理', 10, FALSE, TRUE),
('フェーズ3', '設計図面作成・チェック', 11, TRUE, TRUE),
('フェーズ3', '成果品取りまとめ', 12, FALSE, TRUE);

-- システム設定の初期値
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('default_work_hours', '8', 'デフォルト作業時間（時間/日）'),
('notification_enabled', '1', '通知機能の有効/無効'),
('backup_retention_days', '30', 'バックアップ保持日数'),
('session_timeout_minutes', '60', 'セッションタイムアウト（分）');