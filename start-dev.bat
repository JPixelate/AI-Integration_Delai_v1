@echo off
echo Starting Browser Sync for AI Agent Integration...
echo.
echo This will:
echo - Proxy your localhost/ai-agent-integration/public
echo - Watch for changes in PHP, CSS, JS, and HTML files
echo - Auto-reload browser when files change
echo - Open browser at http://localhost:3000
echo.
echo Press Ctrl+C to stop Browser Sync
echo.
npm run dev
pause
