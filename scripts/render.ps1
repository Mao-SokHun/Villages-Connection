# Village Connect — Render CLI helpers (Windows)
# Requires: render login (see README)

param(
    [Parameter(Position = 0)]
    [ValidateSet("login", "whoami", "services", "deploy", "logs", "status", "restart")]
    [string]$Action = "status"
)

$RenderBin = Join-Path $env:USERPROFILE ".local\bin\render.exe"
if (-not (Test-Path $RenderBin)) {
    Write-Error "Render CLI not found at $RenderBin. See README.md → Render CLI."
    exit 1
}

function Invoke-Render {
    param([string[]]$Args)
    & $RenderBin @Args
}

function Get-VillagesService {
    $services = Invoke-Render @("services", "list", "-o", "json") | ConvertFrom-Json
    foreach ($entry in $services) {
        if ($entry.PSObject.Properties.Name -contains "service") {
            $svc = $entry.service
            if ($svc.name -match "villages-connection") { return $svc }
        }
    }
    Write-Error "Service villages-connection not found. Run: render services list"
    exit 1
}

if (-not (Invoke-Render @("workspace", "current", "-o", "text") 2>$null)) {
    Invoke-Render @("workspace", "set", "tea-d7usjp0sfn5c73bgsdjg", "-o", "text") | Out-Null
}

switch ($Action) {
    "login" { Invoke-Render @("login") }
    "whoami" { Invoke-Render @("whoami") }
    "services" { Invoke-Render @("services", "list", "-o", "text") }
    "deploy" {
        $svc = Get-VillagesService
        Write-Host "Deploying $($svc.name) ($($svc.id))..."
        Invoke-Render @("deploys", "create", $svc.id, "--confirm", "-o", "text")
    }
    "logs" {
        $svc = Get-VillagesService
        Invoke-Render @("logs", "--resources", $svc.id, "--tail", "-o", "text")
    }
    "status" {
        Invoke-Render @("whoami")
        Write-Host ""
        Invoke-Render @("services", "list", "-o", "text")
        Write-Host ""
        $svc = Get-VillagesService
        Write-Host "Latest deploys for $($svc.name):"
        Invoke-Render @("deploys", "list", $svc.id, "-o", "text")
    }
    "restart" {
        $svc = Get-VillagesService
        Invoke-Render @("restart", $svc.id, "--confirm", "-o", "text")
    }
}
