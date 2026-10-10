# Run elevated once to allow LAN access and keep the plugged-in server awake.
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$name = 'MirSport-HTTP-LAN'
if (!(Get-NetFirewallRule -Name $name -ErrorAction SilentlyContinue)) {
    New-NetFirewallRule -Name $name -DisplayName 'Mir Sport HTTP from local subnet' `
        -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8080 `
        -RemoteAddress LocalSubnet -Profile Any | Out-Null
}
powercfg /change standby-timeout-ac 0
if ($LASTEXITCODE -ne 0) { throw 'Could not disable sleep on AC power.' }
powercfg /change hibernate-timeout-ac 0
if ($LASTEXITCODE -ne 0) { throw 'Could not disable hibernation on AC power.' }
$dockerBin = Split-Path (Get-Command docker).Source
$dockerDesktop = Join-Path (Split-Path (Split-Path $dockerBin)) 'Docker Desktop.exe'
if (!(Test-Path $dockerDesktop)) { throw 'Docker Desktop executable was not found.' }
$run = 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Run'
if (!(Test-Path $run)) { New-Item -Path $run | Out-Null }
Set-ItemProperty -Path $run -Name 'Docker Desktop' -Value ('"' + $dockerDesktop + '"')
Write-Output 'LAN port 8080 allowed; sleep/hibernation on AC disabled; Docker starts at Windows sign-in.'
