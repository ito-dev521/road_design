# 道路詳細設計管理システム デプロイメントガイド

## 🚀 システム概要

本システムは、道路設計作業の進捗管理を効率化するWebアプリケーションです。

### 主要機能
- ユーザー認証（3つの権限レベル）
- プロジェクト管理
- 3フェーズ30タスクの進捗管理
- リアルタイム統計表示
- 作業履歴記録

---

## 📋 デプロイメント要件

### サーバー環境
- **ホスティング**: エックスサーバー
- **PHP**: 8.0以上
- **MySQL**: 8.0以上
- **ウェブサーバー**: Apache（mod_rewrite対応）

### データベース設定
```
データベース名: iistylelab_road
ユーザー名: iistylelab_road
パスワード: K6RVCwzMDxtz5dn
文字セット: utf8mb4
```

---

## 🔧 インストール手順

### 1. ファイルアップロード

エックスサーバーのファイルマネージャーまたはFTPクライアントを使用：

```
public_html/
└── road-design/        ← 新規作成
    ├── index.html
    ├── login.html
    ├── config.php
    ├── database.php
    ├── auth.php
    ├── api.php
    ├── install.php    ← セットアップ後削除
    ├── .htaccess
    └── assets/
        ├── css/
        │   └── style.css
        ├── js/
        │   └── main.js
        └── uploads/    ← 権限755設定
```

### 2. データベース設定確認

エックスサーバーのコントロールパネルでMySQL設定を確認：
- データベースが作成済みであることを確認
- 接続情報が `config.php` に正しく設定されていることを確認

### 3. 初期セットアップ実行

ブラウザで以下のURLにアクセス：
```
https://ii-stylelab.com/road-design/install.php
```

**インストール手順：**
1. データベース接続設定の確認
2. 「インストール開始」ボタンをクリック
3. インストール完了後、`install.php` を削除

### 4. 初回ログイン

システムにアクセス：
```
https://ii-stylelab.com/road-design/
```

**初期ユーザーアカウント：**
- **管理者**: admin@ii-stylelab.com / admin123
- **技術者**: tech@ii-stylelab.com / tech123
- **一般スタッフ**: staff@ii-stylelab.com / staff123

---

## 🛡️ セキュリティ設定

### SSL/TLS設定
エックスサーバーの「SSL設定」で独自SSL（無料）を有効化：
```
1. サーバーパネル → SSL設定
2. 独自SSL設定追加 → 確認画面へ進む → 追加する
```

### ファイル権限設定
```bash
# ディレクトリ: 755
# PHPファイル: 644
# アップロードディレクトリ: 755
```

### セキュリティヘッダー
`.htaccess`で以下を設定済み：
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block

---

## 🗄️ データベース構造

### 主要テーブル
- `users` - ユーザー情報
- `projects` - プロジェクト管理
- `tasks` - タスク詳細
- `task_notes` - 作業メモ
- `project_history` - 変更履歴

### 初期データ
- 30個のタスクテンプレート（3フェーズ）
- 3つの初期ユーザーアカウント
- システム設定値

---

## 📊 使用方法

### 1. プロジェクト作成
1. 「新規プロジェクト」ボタンをクリック
2. プロジェクト情報を入力
3. 作成と同時に30個のタスクが自動生成

### 2. タスク管理
- **ステータス**: 未着手 → 進行中 → 完了 → 対象外
- **担当者割り当て**: ドロップダウンから選択
- **予定日設定**: カレンダーから選択
- **作業メモ**: 詳細情報を記録

### 3. 進捗確認
- リアルタイム統計表示
- フェーズ別進捗バー
- 遅延タスクアラート

---

## 🔧 カスタマイズ

### タスクテンプレート追加
`database_schema.sql`の`INSERT INTO task_templates`部分を編集：
```sql
INSERT INTO task_templates (phase_name, task_name, task_order, is_technical_work, has_manual) VALUES
('フェーズ4', '新規タスク名', 1, TRUE, TRUE);
```

### ユーザー権限カスタマイズ
`auth.php`の`hasPermission`メソッドで権限レベル調整

### UIカスタマイズ
- `assets/css/style.css` - スタイル調整
- `assets/js/main.js` - 機能追加

---

## 🚨 トラブルシューティング

### よくある問題と解決方法

#### 1. データベース接続エラー
```
対処法：
1. config.phpの接続情報確認
2. MySQL設定の確認
3. データベース権限の確認
```

#### 2. ファイルアップロードエラー
```
対処法：
1. uploadsフォルダの権限を755に設定
2. php.iniのアップロード設定確認
```

#### 3. セッションタイムアウト
```
対処法：
1. config.phpのSESSION_TIMEOUT調整
2. ブラウザのクッキー設定確認
```

#### 4. 403エラー（アクセス拒否）
```
対処法：
1. .htaccessの設定確認
2. ファイル権限の確認
3. mod_rewriteの有効性確認
```

---

## 🔄 バックアップとメンテナンス

### データベースバックアップ
エックスサーバーのコントロールパネルから定期バックアップ設定：
```
1. MySQL設定 → データベースバックアップ
2. 自動バックアップを有効化
```

### ファイルバックアップ
重要ファイルの定期バックアップ：
- `config.php`
- `database.php`
- `assets/uploads/`（アップロードファイル）

### ログ監視
`error_log`ファイルの定期確認：
```
/home/サーバーID/ii-stylelab.com/public_html/road-design/error_log
```

---

## 📈 パフォーマンス最適化

### 推奨設定
1. **PHP設定最適化**
   ```php
   memory_limit = 256M
   max_execution_time = 60
   upload_max_filesize = 10M
   post_max_size = 12M
   ```

2. **MySQL設定最適化**
   - 適切なインデックス設定済み
   - クエリ最適化実装済み

3. **フロントエンド最適化**
   - CSS/JSファイルの圧縮
   - 画像最適化
   - ブラウザキャッシュ活用

---

## 🆘 サポート情報

### ログファイル場所
- **エラーログ**: `/error_log`
- **アクセスログ**: エックスサーバーのログ機能

### 開発情報
- **フレームワーク**: バニラPHP/JavaScript
- **データベース**: MySQL 8.x
- **認証方式**: セッションベース認証

### 連絡先
システムに関する問い合わせは、ii-stylelab.comの管理者まで。

---

## ✅ デプロイメントチェックリスト

- [ ] ファイル全てアップロード完了
- [ ] データベース接続設定完了
- [ ] install.php実行完了
- [ ] install.php削除完了
- [ ] SSL証明書設定完了
- [ ] 初回ログインテスト完了
- [ ] プロジェクト作成テスト完了
- [ ] タスク管理機能テスト完了
- [ ] 権限別アクセステスト完了
- [ ] バックアップ設定完了
- [ ] パフォーマンステスト完了

**🎉 デプロイメント完了！**

システムは`https://ii-stylelab.com/road-design/`でアクセス可能です。