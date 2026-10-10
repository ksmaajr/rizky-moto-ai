[CmdletBinding()]
param(
    [string]$AgentKitRepository = "https://github.com/AlekseiUL/gpt-image-2-5-agent-kit.git"
)

$ErrorActionPreference = "Stop"

function Write-Step([string]$Message) {
    Write-Host "[AgentKit Setup] $Message" -ForegroundColor Cyan
}

$projectRoot = (Get-Location).Path
if (-not (Test-Path (Join-Path $projectRoot "artisan"))) {
    throw "Jalankan script dari root project Laravel (folder yang berisi artisan)."
}

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    throw "Git tidak ditemukan di PATH. Install Git for Windows terlebih dahulu."
}

$pythonLauncher = Get-Command py -ErrorAction SilentlyContinue
if (-not $pythonLauncher) {
    throw "Python Launcher (py.exe) tidak ditemukan. Install Python 3.10+ dan aktifkan opsi Python Launcher."
}

$pythonVersion = & py -3 -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}')"
if ($LASTEXITCODE -ne 0) {
    throw "Python 3 tidak tersedia melalui 'py -3'. Install Python 3.10+."
}
$versionParts = $pythonVersion.Split(".")
if ([int]$versionParts[0] -lt 3 -or ([int]$versionParts[0] -eq 3 -and [int]$versionParts[1] -lt 10)) {
    throw "AgentKit membutuhkan Python 3.10+. Versi terdeteksi: $pythonVersion"
}

$runtimeRoot = Join-Path $projectRoot "storage\app\agent-ai\agent-kit"
$venvRoot = Join-Path $runtimeRoot ".venv"
$pythonExe = Join-Path $venvRoot "Scripts\python.exe"

New-Item -ItemType Directory -Force -Path (Split-Path $runtimeRoot -Parent) | Out-Null

if (-not (Test-Path (Join-Path $runtimeRoot ".git"))) {
    if (Test-Path $runtimeRoot) {
        throw "Folder $runtimeRoot sudah ada tetapi bukan checkout Git. Pindahkan/backup folder tersebut lalu jalankan ulang."
    }

    Write-Step "Mengunduh AgentKit upstream ke storage lokal (tidak mengubah file aplikasi)."
    & git clone --depth 1 $AgentKitRepository $runtimeRoot
    if ($LASTEXITCODE -ne 0) {
        throw "Git clone AgentKit gagal."
    }
} else {
    Write-Step "Checkout AgentKit sudah ada; tidak menjalankan git pull otomatis."
}

if (-not (Test-Path $pythonExe)) {
    Write-Step "Membuat Python virtual environment."
    & py -3 -m venv $venvRoot
    if ($LASTEXITCODE -ne 0) {
        throw "Pembuatan virtual environment gagal."
    }
}

Write-Step "Memasang package AgentKit ke virtual environment."
& $pythonExe -m pip install --upgrade pip
if ($LASTEXITCODE -ne 0) { throw "Upgrade pip gagal." }
& $pythonExe -m pip install -e $runtimeRoot
if ($LASTEXITCODE -ne 0) { throw "Instalasi package AgentKit gagal." }

Write-Step "Memeriksa CLI dalam mode help (tidak membuat image dan tidak memakai kuota)."
Push-Location $runtimeRoot
try {
    & $pythonExe -m gpt_image25_agent --help | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "Modul gpt_image25_agent tidak dapat dijalankan."
    }
} finally {
    Pop-Location
}

$envFile = Join-Path $projectRoot ".env"
if (-not (Test-Path $envFile)) {
    throw "File .env tidak ditemukan. Buat .env dan jalankan php artisan key:generate sebelum setup AgentKit."
}

$envLines = @(Get-Content $envFile)
$settings = [ordered]@{
    # Dotenv treats backslashes as escape sequences; forward slashes are valid on Windows.
    "AGENT_AI_PYTHON_BINARY" = $pythonExe.Replace('\', '/')
    "AGENT_AI_MODULE" = "gpt_image25_agent"
    "AGENT_AI_WORKER_DRIVER" = "local"
    "AGENT_AI_QUEUE" = "agentkit"
    "AGENT_AI_WORKER_COUNT" = "3"
}
foreach ($key in $settings.Keys) {
    $line = "$key=$($settings[$key])"
    $found = $false
    for ($i = 0; $i -lt $envLines.Count; $i++) {
        if ($envLines[$i] -match "^\s*$([regex]::Escape($key))=") {
            $envLines[$i] = $line
            $found = $true
        }
    }
    if (-not $found) {
        $envLines += $line
    }
}
Set-Content -Path $envFile -Value $envLines -Encoding utf8

Write-Host ""
Write-Host "AgentKit lokal siap dipanggil oleh Laravel." -ForegroundColor Green
Write-Host "Python: $pythonExe"
Write-Host "Runtime: $runtimeRoot"
Write-Host "Konfigurasi .env diperbarui; jika Laravel memakai config cache, bersihkan cache sebelum pengujian."
Write-Host "Langkah berikutnya: siapkan Codex CLI/login terpisah. Script ini TIDAK melakukan login dan tidak membuat image."
