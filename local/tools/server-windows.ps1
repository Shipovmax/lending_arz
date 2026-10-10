# Manage the Linux Docker stand from Windows, including an SSH session.
param([ValidateSet('start','status','stop','logs','build')][string]$Action = 'status')
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$project = Split-Path (Split-Path $PSScriptRoot)
$env:DOCKER_CONFIG = Join-Path $env:USERPROFILE '.docker-mir-sport-server'
$env:DOCKER_HOST = 'npipe:////./pipe/dockerDesktopLinuxEngine'
Remove-Item Env:DOCKER_CONTEXT -ErrorAction SilentlyContinue
if (!(Test-Path $env:DOCKER_CONFIG)) {
    New-Item -ItemType Directory $env:DOCKER_CONFIG | Out-Null
    $originalPath = Join-Path $env:USERPROFILE '.docker/config.json'
    $pluginPaths = @()
    if (Test-Path $originalPath) {
        $original = Get-Content $originalPath -Raw | ConvertFrom-Json
        $pluginPaths = @($original.cliPluginsExtraDirs | Where-Object { $_ })
    }
    if (!$pluginPaths.Count) {
        $bin = Split-Path (Get-Command docker).Source
        $pluginPaths = @((Join-Path (Split-Path $bin) 'cli-plugins'))
    }
    # A registry entry without credentials suppresses automatic wincred discovery.
    @{auths=@{'https://index.docker.io/v1/'=@{}}; cliPluginsExtraDirs=$pluginPaths} |
        ConvertTo-Json -Depth 5 |
        Set-Content (Join-Path $env:DOCKER_CONFIG 'config.json') -Encoding ascii
}
Set-Location $project
switch ($Action) {
    'start'  { docker compose up -d }
    'status' { docker compose ps }
    'stop'   { docker compose stop }
    'logs'   { docker compose logs --tail 100 }
    'build'  { docker compose build php db }
}
if ($LASTEXITCODE -ne 0) { throw 'Docker command failed.' }
