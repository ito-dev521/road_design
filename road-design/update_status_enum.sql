-- タスクステータスに「要確認」を追加するSQLスクリプト
-- 実行前に必ずバックアップを取ってください

-- データベースを選択
USE iistylelab_road;

-- tasksテーブルのstatusカラムのENUMを更新
ALTER TABLE tasks 
MODIFY COLUMN status ENUM('not_started', 'in_progress', 'completed', 'not_applicable', 'needs_confirmation', 'pending') 
DEFAULT 'not_started';

-- 更新完了の確認
SELECT DISTINCT status FROM tasks;
