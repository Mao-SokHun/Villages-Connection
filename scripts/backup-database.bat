@powershell -ExecutionPolicy Bypass -File "%~dp0backup-database.ps1" %*
exit /b %ERRORLEVEL%
