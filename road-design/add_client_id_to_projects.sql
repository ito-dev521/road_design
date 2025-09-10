USE iistylelab_road;

-- ステップ3: projectsテーブルにclient_idカラムを追加
ALTER TABLE projects ADD COLUMN IF NOT EXISTS client_id INT;

-- ステップ4: 外部キー制約を追加
ALTER TABLE projects ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;
