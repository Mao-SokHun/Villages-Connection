#Requires -Version 5.1
$ErrorActionPreference = "Stop"
$taskName = "VillageConnect-DatabaseBackup"
$scriptPath = Join-Path $PSScriptRoot "backup-database.ps1"
$trigger = New-ScheduledTaskTrigger -Daily -At "02:00"
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File `"$scriptPath`""
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description "Daily Village Connect PostgreSQL backup" -Force
Write-Host "Scheduled task registered: $taskName (daily at 02:00)"
