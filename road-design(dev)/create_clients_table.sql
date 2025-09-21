USE iistylelab_road;

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO clients (name, contact_person, email, phone, address) VALUES
('株式会社サンプル建設', '田中太郎', 'tanaka@sample-const.co.jp', '03-1234-5678', '東京都渋谷区1-2-3'),
('ABC道路工事株式会社', '佐藤花子', 'sato@abc-road.co.jp', '06-9876-5432', '大阪府大阪市中央区4-5-6'),
('XYZ土木株式会社', '山田次郎', 'yamada@xyz-civil.co.jp', '052-1111-2222', '愛知県名古屋市中区7-8-9');

ALTER TABLE projects ADD COLUMN IF NOT EXISTS client_id INT;
ALTER TABLE projects ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;
