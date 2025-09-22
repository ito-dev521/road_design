<?php
// 未分類（テンプレート未紐付け、またはフェーズ未設定）のタスクを削除する管理用スクリプト
// 実行例:
//   GET/POST ?dry_run=1                -> 削除対象の件数と一覧のみを返す
//   GET/POST ?project_id=123           -> 指定プロジェクトの未分類のみ対象
//   GET/POST ?confirm=1                -> 実削除を許可（CSRF と管理者権限必須）

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

// 表示形式の決定（?ui=1 ならHTML、既定はJSON）
$wantsHtml = isset($_GET['ui']) && $_GET['ui'] === '1';
if ($wantsHtml) {
    header('Content-Type: text/html; charset=utf-8');
} else {
    header('Content-Type: application/json; charset=utf-8');
}

// セッション・権限確認（管理者のみ）
$auth = new Auth();
$auth->requirePermission('manager');
$currentUser = $auth->getCurrentUser();

// 入力値
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$params = $method === 'POST' ? $_POST : $_GET;

$projectId = isset($params['project_id']) && $params['project_id'] !== '' ? (int)$params['project_id'] : null;
$isDryRun  = isset($params['dry_run']) && (string)$params['dry_run'] === '1';
$doConfirm = isset($params['confirm']) && (string)$params['confirm'] === '1';

// CSRF チェック（実削除時のみ）
if ($doConfirm) {
    $token = $params['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($token)) {
        http_response_code(400);
        if ($wantsHtml) {
            echo '<!DOCTYPE html><meta charset="utf-8"><p style="color:red">CSRFトークンが無効です。</p>';
        } else {
            echo json_encode(['success' => false, 'message' => 'CSRFトークンが無効です。']);
        }
        exit;
    }
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'データベース接続に失敗しました。']);
        exit;
    }

    // 削除対象条件: テンプレート未紐付け、またはフェーズ未設定（NULL/空）
    $where = "(template_id IS NULL) OR (phase_name IS NULL OR TRIM(phase_name) = '')";
    $params = [];
    if ($projectId !== null) {
        $where = "project_id = ? AND (" . $where . ")";
        $params[] = $projectId;
    }

    // 対象一覧を取得
    $sqlSelect = "SELECT id, project_id, task_name, phase_name, template_id, status FROM tasks WHERE $where ORDER BY project_id, id";
    $stmt = $pdo->prepare($sqlSelect);
    $stmt->execute($params);
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($isDryRun || !$doConfirm) {
        if ($wantsHtml) {
            $csrf = $auth->generateCSRFToken();
            echo '<!DOCTYPE html><html lang="ja"><meta charset="utf-8"><title>未分類タスク削除</title>';
            echo '<style>body{font-family:-apple-system,Segoe UI,Meiryo,system-ui;line-height:1.6;padding:24px;}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #ddd;padding:6px 8px}th{background:#f5f5f7;text-align:left}code{background:#f2f4f8;padding:2px 4px;border-radius:4px}</style>';
            echo '<h2>未分類タスク削除</h2>';
            echo '<p>対象条件: <code>template_id IS NULL</code> または <code>phase_name IS NULL/空</code>' . ($projectId ? '（project_id=' . htmlspecialchars((string)$projectId, ENT_QUOTES, 'UTF-8') . '）' : '') . '</p>';
            echo '<p>該当件数: <strong>' . count($targets) . '</strong></p>';
            if (count($targets) > 0) {
                echo '<table><tr><th>ID</th><th>Project</th><th>Task</th><th>Phase</th><th>Template</th><th>Status</th></tr>';
                foreach ($targets as $t) {
                    echo '<tr><td>' . htmlspecialchars((string)$t['id'], ENT_QUOTES, 'UTF-8') . '</td>';
                    echo '<td>' . htmlspecialchars((string)$t['project_id'], ENT_QUOTES, 'UTF-8') . '</td>';
                    echo '<td>' . htmlspecialchars((string)$t['task_name'], ENT_QUOTES, 'UTF-8') . '</td>';
                    echo '<td>' . htmlspecialchars((string)($t['phase_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                    echo '<td>' . (isset($t['template_id']) && $t['template_id'] !== null ? (int)$t['template_id'] : '<em>null</em>') . '</td>';
                    echo '<td>' . htmlspecialchars((string)$t['status'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
                }
                echo '</table>';
            }
            echo '<form method="post" style="margin-top:16px">';
            if ($projectId !== null) {
                echo '<input type="hidden" name="project_id" value="' . (int)$projectId . '">';
            }
            echo '<input type="hidden" name="confirm" value="1">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">';
            echo '<button type="submit" style="background:#d32f2f;color:#fff;border:none;padding:10px 16px;border-radius:6px;cursor:pointer">未分類タスクを削除する</button>';
            echo '</form>';
            echo '<p style="margin-top:10px;color:#555">プレビューのみの場合は <code>?dry_run=1</code> を付けてアクセスしてください。</p>';
            echo '</html>';
        } else {
            echo json_encode([
                'success' => true,
                'mode' => $isDryRun ? 'dry_run' : 'preview',
                'count' => count($targets),
                'items' => $targets,
                'message' => '削除対象の確認結果です。confirm=1 を指定し、CSRFトークンを付与すると削除を実行します。'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // 実削除
    if (count($targets) === 0) {
        echo json_encode(['success' => true, 'deleted' => 0, 'message' => '削除対象はありません。']);
        exit;
    }

    $pdo->beginTransaction();
    // 先に子テーブル task_notes を削除（外部キーでON DELETE CASCADEの場合は不要だが保守的に）
    $ids = array_column($targets, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // task_notes
    try {
        $stmtNotes = $pdo->prepare("DELETE FROM task_notes WHERE task_id IN ($placeholders)");
        $stmtNotes->execute($ids);
    } catch (Exception $e) {
        // 失敗しても続行（CASCADE想定）
    }

    // tasks 本体
    $stmtDel = $pdo->prepare("DELETE FROM tasks WHERE id IN ($placeholders)");
    $stmtDel->execute($ids);
    $deleted = $stmtDel->rowCount();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'message' => '未分類タスクを削除しました。',
        'project_id' => $projectId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '処理中にエラーが発生しました。', 'error' => $e->getMessage()]);
}
?>


