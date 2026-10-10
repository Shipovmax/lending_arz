param([string]$BackupDirectory = (Join-Path $env:USERPROFILE 'mir-sport-backup'))
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$project = Split-Path (Split-Path $PSScriptRoot)
$backup = $BackupDirectory
& (Join-Path $PSScriptRoot 'server-windows.ps1') -Action status
$env:COMPOSE_PROJECT_NAME = 'mir_sport_server'
Set-Location $project
# Verify both archive payloads before creating any target volumes.
$manifest = Get-Content (Join-Path $backup 'manifest.json') -Raw | ConvertFrom-Json
foreach ($name in @('runtime.tar.gz','database.sql.gz')) {
    $entry = $manifest.files.$name
    $path = Join-Path $backup $name
    if (!$entry -or (Get-FileHash -Algorithm SHA256 $path).Hash.ToLowerInvariant() -ne $entry.sha256 -or (Get-Item $path).Length -ne $entry.bytes) { throw 'Payload verification failed.' }
}
$all = @(docker volume ls --format '{{.Name}}')
if ($LASTEXITCODE -ne 0) { throw 'Could not inspect volumes.' }
foreach ($name in @('mir_sport_server_bitrix_www','mir_sport_server_bitrix_db')) {
    if ($all -contains $name) { throw 'Target volume exists; refusing to overwrite.' }
}
# No web workers start before both runtime and SQL have been restored.
docker compose run --rm --no-deps -v "${backup}:/migration:ro" --entrypoint tar php -xzf /migration/runtime.tar.gz -C /var/www/html
if ($LASTEXITCODE -ne 0) { throw 'Runtime restore failed.' }
docker compose up -d --wait --wait-timeout 180 db
if ($LASTEXITCODE -ne 0) { throw 'Database did not become healthy.' }
$db = docker compose ps -q db
if ($LASTEXITCODE -ne 0 -or !$db) { throw 'Database container not found.' }
docker cp (Join-Path $backup 'database.sql.gz') "${db}:/tmp/mir-sport-database.sql.gz"
if ($LASTEXITCODE -ne 0) { throw 'SQL copy failed.' }
docker cp (Join-Path $PSScriptRoot 'import-database.sh') "${db}:/tmp/import-database.sh"
if ($LASTEXITCODE -ne 0) { throw 'Import helper copy failed.' }
docker compose exec -T db bash /tmp/import-database.sh
if ($LASTEXITCODE -ne 0) { throw 'SQL restore failed. Web services remain stopped.' }
docker compose up -d php nginx
if ($LASTEXITCODE -ne 0) { throw 'Web services failed to start.' }
docker compose ps
