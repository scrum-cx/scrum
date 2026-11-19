# Prepares a fresh clone of Scrum on Windows + XAMPP.
# Run from the repo root in PowerShell, after setup-machine.ps1, with XAMPP's MySQL running:
#   powershell -ExecutionPolicy Bypass -File scripts\setup-project.ps1
# After pulling changes that alter existing tables, rebuild the database (deletes all data):
#   powershell -ExecutionPolicy Bypass -File scripts\setup-project.ps1 -Fresh
param([switch]$Fresh)

# 'Continue', not 'Stop': Composer and npm print progress to stderr, which Windows
# PowerShell would otherwise treat as a fatal error. Run() checks real exit codes.
$ErrorActionPreference = 'Continue'
Set-Location (Join-Path $PSScriptRoot '..')

$DbName = 'scrum'
$Mysql  = 'C:\xampp\mysql\bin\mysql.exe'

function Step($msg) { Write-Host "==> $msg" -ForegroundColor Cyan }
function Run($cmd, $argList) {
    & $cmd @argList
    if ($LASTEXITCODE -ne 0) { throw "'$cmd $argList' failed with exit code $LASTEXITCODE" }
}

Step 'Checking tools'
foreach ($t in 'php', 'composer', 'node', 'npm', 'git') {
    if (-not (Get-Command $t -ErrorAction SilentlyContinue)) { throw "Missing: $t (run scripts\setup-machine.ps1)" }
}
if (-not (Test-Path $Mysql)) { throw "XAMPP MySQL not found at $Mysql" }

Step "Creating database '$DbName' if missing (XAMPP MySQL must be running)"
Run $Mysql @('-h127.0.0.1', '-uroot', '-e', "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")

Step 'Installing PHP packages'
Run 'composer' @('install', '--no-interaction')

Step 'Installing JS packages'
Run 'npm' @('ci')

if (-not (Test-Path '.env')) {
    Step 'Creating .env from .env.example'
    Copy-Item '.env.example' '.env'
    Run 'php' @('artisan', 'key:generate')
}

if ($Fresh) {
    Step 'Rebuilding the database from scratch (all data is deleted) and seeding'
    Run 'php' @('artisan', 'migrate:fresh', '--seed', '--force')
} else {
    Step 'Migrating and seeding'
    & php artisan migrate --seed --force
    if ($LASTEXITCODE -ne 0) {
        Write-Host ''
        Write-Host 'Migrations failed (see the error above).' -ForegroundColor Yellow
        Write-Host 'If it says a table "already exists", the table structure changed since your last setup.' -ForegroundColor Yellow
        Write-Host 'Rebuild the database (this deletes its data) by running:' -ForegroundColor Yellow
        Write-Host '    powershell -ExecutionPolicy Bypass -File scripts\setup-project.ps1 -Fresh' -ForegroundColor Yellow
        exit 1
    }
}
& php artisan storage:link 2>$null

Step 'Building front-end assets'
Run 'npm' @('run', 'build')

Write-Host ''
Write-Host 'Done. Start the app with:  php artisan serve   (then open http://127.0.0.1:8000)' -ForegroundColor Green
