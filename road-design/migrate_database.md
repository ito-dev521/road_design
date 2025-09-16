# データベース移行ガイド

## 概要
道路詳細設計管理システムのデータベースを他のサーバーに移動する手順です。

## 現在のデータベース情報
- **ホスト**: localhost
- **データベース名**: iistylelab_road
- **ユーザー**: iistylelab_road
- **ポート**: 3306

## 移行手順

### ステップ1: バックアップの作成

#### 方法A: 自動スクリプトを使用（推奨）
```bash
php backup_database.php
```

#### 方法B: 手動でmysqldumpを使用
```bash
mysqldump -h localhost -u iistylelab_road -p iistylelab_road > road_design_backup.sql
```

#### 方法C: phpMyAdminを使用
1. phpMyAdminにログイン
2. `iistylelab_road`データベースを選択
3. 「エクスポート」→「カスタム」→「SQL」形式でダウンロード

### ステップ2: 新しいサーバーの準備

#### 2.1 データベースの作成
```sql
CREATE DATABASE new_road_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2.2 ユーザーの作成と権限設定
```sql
CREATE USER 'new_user'@'localhost' IDENTIFIED BY 'new_password';
GRANT ALL PRIVILEGES ON new_road_db.* TO 'new_user'@'localhost';
FLUSH PRIVILEGES;
```

### ステップ3: データの復元

#### 方法A: 自動スクリプトを使用
1. `restore_database.php`の設定を新しいサーバー情報に更新
2. バックアップファイルを`backups/`フォルダに配置
3. スクリプトを実行:
```bash
php restore_database.php
```

#### 方法B: 手動でmysqlを使用
```bash
mysql -h [新しいホスト] -u [新しいユーザー] -p [新しいデータベース名] < road_design_backup.sql
```

#### 方法C: phpMyAdminを使用
1. 新しいサーバーのphpMyAdminにログイン
2. 新しいデータベースを選択
3. 「インポート」→バックアップファイルを選択→実行

### ステップ4: 設定ファイルの更新

`config.php`を新しいサーバー情報に更新:

```php
define('DB_HOST', '新しいホスト');
define('DB_PORT', '新しいポート');
define('DB_NAME', '新しいデータベース名');
define('DB_USER', '新しいユーザー名');
define('DB_PASS', '新しいパスワード');
```

### ステップ5: 動作確認

1. **データベース接続テスト**
   - ログイン機能の確認
   - プロジェクト一覧の表示確認

2. **主要機能のテスト**
   - プロジェクト作成・編集
   - タスク管理
   - ファイルアップロード
   - ユーザー管理

3. **データ整合性の確認**
   - レコード数の確認
   - ファイルパスの確認
   - 権限設定の確認

## トラブルシューティング

### よくある問題と解決方法

#### 1. 文字化けが発生する
```sql
-- データベースの文字セットを確認
SHOW VARIABLES LIKE 'character_set%';

-- 必要に応じて文字セットを変更
ALTER DATABASE new_road_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 2. ファイルパスが正しくない
- アップロードファイルのパスを確認
- `uploads/`フォルダの権限設定を確認

#### 3. セッションが正しく動作しない
- セッションフォルダの権限を確認
- `config.php`のセッション設定を確認

#### 4. 権限エラーが発生する
```sql
-- ユーザー権限を再設定
GRANT ALL PRIVILEGES ON new_road_db.* TO 'new_user'@'localhost';
FLUSH PRIVILEGES;
```

## バックアップファイルの管理

### バックアップファイルの保存場所
- `backups/`フォルダに自動保存
- ファイル名: `road_design_backup_YYYY-MM-DD_HH-MM-SS.sql`

### バックアップの定期実行
```bash
# cronで定期実行する場合
0 2 * * * cd /path/to/road-design && php backup_database.php
```

## セキュリティ考慮事項

1. **バックアップファイルの保護**
   - 適切な権限設定（600または644）
   - 定期的な削除（古いバックアップ）

2. **移行時のセキュリティ**
   - 一時的なパスワードの使用
   - 移行完了後のパスワード変更

3. **本番環境での注意**
   - メンテナンス時間の設定
   - ダウンタイムの最小化

## 完了チェックリスト

- [ ] バックアップファイルの作成
- [ ] 新しいサーバーでのデータベース作成
- [ ] データの復元
- [ ] 設定ファイルの更新
- [ ] 動作確認
- [ ] 古いサーバーからのデータ削除（移行完了後）
- [ ] DNS設定の更新（必要に応じて）
