param(
    [switch]$Full
)

$ErrorActionPreference = "Stop"

$Root = Split-Path $PSScriptRoot -Parent
Set-Location $Root

$LocalConfig = Join-Path $PSScriptRoot "deploy.local.ps1"
if (-not (Test-Path $LocalConfig)) {
    throw "scripts/deploy.local.ps1 がありません。scripts/deploy.local.ps1.example をコピーして、サーバー情報を書いてください。"
}

. $LocalConfig

foreach ($Name in @("Key", "SshHost", "SshUser", "SshPort", "RemoteBase", "RemoteApp", "PublicUrl")) {
    $Value = Get-Variable -Name $Name -ErrorAction SilentlyContinue
    if (-not $Value -or [string]::IsNullOrWhiteSpace([string] $Value.Value)) {
        throw "deploy.local.ps1 に $Name を設定してください。"
    }
}

$Target = "${SshUser}@${SshHost}"

if (-not (Test-Path $Key)) {
    throw "SSH key not found: $Key"
}

function Find-OpenSshExe {
    param([Parameter(Mandatory = $true)][string]$Name)

    $candidates = @()
    if ([IntPtr]::Size -eq 4) {
        $candidates += (Join-Path $env:SystemRoot "Sysnative\OpenSSH\$Name")
    }
    $candidates += (Join-Path $env:SystemRoot "System32\OpenSSH\$Name")

    $command = Get-Command $Name -CommandType Application -ErrorAction SilentlyContinue
    if ($command -and $command.Source) {
        $candidates += $command.Source
    }

    foreach ($candidate in $candidates) {
        if ($candidate -and (Test-Path -LiteralPath $candidate)) {
            return $candidate
        }
    }

    throw "$Name not found. Install OpenSSH Client from Windows Settings."
}

$SshExe = Find-OpenSshExe "ssh.exe"
$ScpExe = Find-OpenSshExe "scp.exe"

$Ssh = @("-p", $SshPort, "-i", $Key, "-o", "BatchMode=yes")
$Scp = @("-P", $SshPort, "-i", $Key, "-o", "BatchMode=yes")

Write-Host "フロントをビルドしています..."
& npm run build
if ($LASTEXITCODE -ne 0) {
    throw "npm run build に失敗しました"
}

$Tar = Join-Path $env:TEMP "oshilog-deploy.tar.gz"
if (Test-Path $Tar) {
    Remove-Item $Tar -Force
}

$Exclude = @(
    "--exclude=bootstrap/cache",
    "--exclude=public/hot",
    "--exclude=public/storage",
    "--exclude=database/database.sqlite",
    "--exclude=database/database.sqlite-journal"
)

$Paths = @(
    "app",
    "bootstrap",
    "config",
    "database/migrations",
    "database/seeders",
    "lang",
    "resources",
    "routes",
    "public",
    "composer.json",
    "composer.lock",
    "artisan",
    "vite.config.js"
)

if ($Full) {
    $Paths += "vendor"
}

Write-Host "アップロード用のファイルをまとめています..."
& tar -czf $Tar @Exclude -C $Root @Paths
if ($LASTEXITCODE -ne 0) {
    throw "tar に失敗しました"
}

Write-Host "本番サーバーへ送っています..."
& $ScpExe @Scp $Tar "${Target}:${RemoteBase}/oshilog-incoming.tar.gz"
if ($LASTEXITCODE -ne 0) {
    throw "アップロードに失敗しました"
}

$Remote = @"
set -e
cd $RemoteApp
tar -xzf $RemoteBase/oshilog-incoming.tar.gz
rm -f $RemoteBase/oshilog-incoming.tar.gz
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache storage/app/private/livewire-tmp storage/app/public
if [ -L public/storage ]; then
  rm -f public/storage
fi
mkdir -p public/storage
if [ -d storage/app/public ]; then
  cp -a storage/app/public/. public/storage/
fi
chmod -R ug+rwx storage public/storage
/usr/bin/php8.4 artisan migrate --force
/usr/bin/php8.4 artisan view:clear
/usr/bin/php8.4 artisan config:clear
echo DEPLOY_OK
"@

Write-Host "サーバーで反映しています..."
& $SshExe @Ssh $Target $Remote
if ($LASTEXITCODE -ne 0) {
    throw "サーバー側の反映に失敗しました"
}

Remove-Item $Tar -Force -ErrorAction SilentlyContinue
Write-Host "完了: $PublicUrl"
