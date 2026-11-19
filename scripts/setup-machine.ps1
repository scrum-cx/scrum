# Installs / verifies the tools Scrum needs on a Windows PC with XAMPP.
# Run once per PC in PowerShell:
#   powershell -ExecutionPolicy Bypass -File scripts\setup-machine.ps1
# Expects XAMPP (PHP 8.2 + MariaDB) installed at C:\xampp.
$ErrorActionPreference = 'Stop'

$Xampp   = 'C:\xampp'
$PhpDir  = Join-Path $Xampp 'php'
$PhpIni  = Join-Path $PhpDir 'php.ini'
$Mysql   = Join-Path $Xampp 'mysql\bin\mysql.exe'
$results = @()

function Step($msg) { Write-Host "==> $msg" -ForegroundColor Cyan }
function Check($name, $ok, $detail) {
    $script:results += [pscustomobject]@{ Tool = $name; Status = $(if ($ok) { 'PASS' } else { 'FAIL' }); Detail = $detail }
}
function Refresh-Path {
    $env:Path = [Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [Environment]::GetEnvironmentVariable('Path', 'User')
}
function Winget-Install($id) {
    winget install --id $id -e --silent --accept-package-agreements --accept-source-agreements
}

# --- Git & Node.js via winget ---
Step 'Git'
if (-not (Get-Command git -ErrorAction SilentlyContinue)) { Winget-Install 'Git.Git'; Refresh-Path }
Step 'Node.js'
if (-not (Get-Command node -ErrorAction SilentlyContinue)) { Winget-Install 'OpenJS.NodeJS'; Refresh-Path }

# --- XAMPP PHP on PATH ---
Step 'PHP (XAMPP)'
if (-not (Test-Path (Join-Path $PhpDir 'php.exe'))) { throw "XAMPP PHP not found in $PhpDir. Install XAMPP (PHP 8.2) first." }
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
if ($userPath -notlike "*$PhpDir*") {
    [Environment]::SetEnvironmentVariable('Path', "$userPath;$PhpDir", 'User')
    Refresh-Path
}

# --- Enable PHP extensions Laravel needs ---
Step 'PHP extensions (zip, gd)'
$ini = Get-Content $PhpIni -Raw
$changed = $false
foreach ($ext in 'zip', 'gd') {
    if ($ini -match "(?m)^\s*;\s*extension\s*=\s*$ext\s*$") {
        $ini = $ini -replace "(?m)^\s*;\s*extension\s*=\s*$ext\s*$", "extension=$ext"
        $changed = $true
    }
}
if ($changed) { Copy-Item $PhpIni "$PhpIni.bak" -Force; Set-Content $PhpIni $ini -NoNewline }

# --- Composer ---
Step 'Composer'
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { Winget-Install 'Composer.Composer'; Refresh-Path }

# --- Verify ---
# Windows PowerShell turns anything a native tool writes to stderr (e.g. Composer's
# "PHP version ..." notice) into a terminating error under 'Stop'. Checks below rely
# on exit codes / output instead, so relax it from here on.
$ErrorActionPreference = 'Continue'
Step 'Verifying'
$phpV = (php -r 'echo PHP_VERSION;')
Check 'PHP' ([version]$phpV -ge [version]'8.2.0') $phpV
$mods = (php -m) -join ' '
foreach ($m in 'zip', 'gd', 'pdo_mysql', 'mbstring', 'openssl', 'fileinfo') { Check "ext-$m" ($mods -match "\b$m\b") '' }
Check 'Composer' ([bool](Get-Command composer -ErrorAction SilentlyContinue)) ((composer --version 2>$null) -join '')
Check 'Node.js'  ([bool](Get-Command node -ErrorAction SilentlyContinue)) ((node -v 2>$null) -join '')
Check 'npm'      ([bool](Get-Command npm -ErrorAction SilentlyContinue)) ((npm -v 2>$null) -join '')
Check 'Git'      ([bool](Get-Command git -ErrorAction SilentlyContinue)) ((git --version 2>$null) -join '')
$dbOk = $false; $dbV = 'not reachable - start MySQL in the XAMPP Control Panel'
if (Test-Path $Mysql) {
    $v = & $Mysql -h127.0.0.1 -uroot -N -e 'SELECT VERSION();' 2>$null
    if ($LASTEXITCODE -eq 0) { $dbOk = $true; $dbV = "$v" }
}
Check 'MariaDB' $dbOk $dbV
$gitName = git config --global user.name
Check 'Git identity' ([bool]$gitName) $(if ($gitName) { $gitName } else { 'run: git config --global user.name / user.email' })

$results | Format-Table -AutoSize
if ($results.Status -contains 'FAIL') { Write-Host 'Some checks failed - fix them, then re-run.' -ForegroundColor Yellow; exit 1 }
Write-Host 'Machine ready. Next: scripts\setup-project.ps1' -ForegroundColor Green
