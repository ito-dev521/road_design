# Claude Code 環境設定スクリプト
# 新しいPowerShellウィンドウで実行してください

Write-Host "Claude Code環境を設定中..." -ForegroundColor Green

# Node.jsのPATHを設定
$env:PATH += ";C:\Program Files\nodejs"
Write-Host "✓ Node.js PATH設定完了" -ForegroundColor Green

# Claude Code OAuthトークンを設定
$env:CLAUDE_CODE_OAUTH_TOKEN = "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"
Write-Host "✓ OAuthトークン設定完了" -ForegroundColor Green

# Claude Codeエイリアスを設定
function claude { 
    & "C:\Users\IIS-RSV01\AppData\Roaming\npm\claude.cmd" @args 
}
Write-Host "✓ claudeコマンド設定完了" -ForegroundColor Green

# 動作確認
Write-Host "`n動作確認中..." -ForegroundColor Yellow
try {
    $version = claude --version
    Write-Host "✓ Claude Code動作確認完了: $version" -ForegroundColor Green
    Write-Host "`n🎉 設定完了！ 'claude'コマンドが使用可能になりました。" -ForegroundColor Cyan
    Write-Host "使用方法: claude" -ForegroundColor White
} catch {
    Write-Host "✗ エラーが発生しました: $_" -ForegroundColor Red
}


