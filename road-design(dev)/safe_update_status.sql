-- 安全なタスクステータス更新スクリプト
-- 実行前に必ずバックアップを取ってください

-- データベースを選択
USE iistylelab_road;

-- 現在のテーブル構造を確認
DESCRIBE tasks;

-- 現在のstatusカラムの値を確認
SELECT DISTINCT status, COUNT(*) as count FROM tasks GROUP BY status;

-- テーブルが存在するか確認
SELECT COUNT(*) as table_exists FROM information_schema.tables 
WHERE table_schema = 'iistylelab_road' AND table_name = 'tasks';

-- 安全にENUMを更新（既存のデータを保持）
ALTER TABLE tasks 
MODIFY COLUMN status ENUM('not_started', 'in_progress', 'completed', 'not_applicable', 'needs_confirmation', 'pending') 
DEFAULT 'not_started' 
COMMENT 'タスクステータス: not_started=未着手, in_progress=進行中, completed=完了, not_applicable=対象外, needs_confirmation=要確認, pending=保留中';

-- 更新後の確認
SELECT DISTINCT status, COUNT(*) as count FROM tasks GROUP BY status;

-- テーブル構造の最終確認
DESCRIBE tasks;
