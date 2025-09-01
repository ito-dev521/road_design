<?php
echo "<h2>APIエンドポイントテスト</h2>";
echo "<p>各APIエンドポイントの動作を確認します</p>";

// テストするエンドポイント
$endpoints = [
    'users' => 'ユーザー一覧',
    'phases' => 'フェーズ一覧',
    'templates' => 'テンプレート一覧'
];

foreach ($endpoints as $endpoint => $description) {
    echo "<h3>{$description} ({$endpoint})</h3>";
    
    // 直接APIを呼び出し
    $url = "api.php?path={$endpoint}";
    echo "<p>URL: <code>{$url}</code></p>";
    
    // cURLでAPIをテスト
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    echo "<p>HTTPステータス: <strong>{$httpCode}</strong></p>";
    echo "<p>レスポンスヘッダー:</p>";
    echo "<pre>" . htmlspecialchars($headers) . "</pre>";
    
    echo "<p>レスポンスボディ:</p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
    
    // JSONとして解析できるかテスト
    if ($body) {
        $jsonData = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✓ JSON解析成功</p>";
            echo "<p>データ構造:</p>";
            echo "<pre>" . print_r($jsonData, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ JSON解析失敗: " . json_last_error_msg() . "</p>";
        }
    }
    
    echo "<hr>";
}
?>
