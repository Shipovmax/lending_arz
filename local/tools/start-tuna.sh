#!/bin/bash
# Saves the tuna.am token on the Windows host and runs the tunnel as a startup task.
set -euo pipefail

read -r -s -p "Токен tuna (не отображается): " TOKEN; echo
read -r -p "Бесплатный адрес из my.tuna.am (вида name-1234.ru.tuna.am): " ADDR
TOKEN="${TOKEN#tuna config save-token }"
[[ "$TOKEN" =~ ^tt_[A-Za-z0-9_.-]+$ ]] || { echo "Токен должен начинаться с tt_, остановка."; exit 1; }
[[ "$ADDR" =~ ^([a-z0-9-]+)\.(ru|nl)\.tuna\.am$ ]] || { echo "Адрес должен быть вида имя.nl.tuna.am или имя.ru.tuna.am."; exit 1; }
SUB="${BASH_REMATCH[1]}"; LOC="${BASH_REMATCH[2]}"

{
  printf '$token = "%s"\n$sub = "%s"\n$loc = "%s"\n' "$TOKEN" "$SUB" "$LOC"
  cat <<'EOF'
$dir = "C:\Users\maxim\mir-sport-tunnel\tuna"
$log = "$dir\tuna.log"
& "$dir\tuna.exe" config save-token $token | Out-Null
Remove-Variable token
Unregister-ScheduledTask -TaskName "MirSportTunnel" -Confirm:$false -ErrorAction SilentlyContinue
Stop-ScheduledTask -TaskName "MirSportTuna" -ErrorAction SilentlyContinue
Get-Process tuna -ErrorAction SilentlyContinue | Stop-Process -Force
# Endless retry: tuna exits when DNS is not ready right after boot.
$action = New-ScheduledTaskAction -Execute "cmd.exe" -Argument "/c `"for /l %i in (0,0,1) do (`"$dir\tuna.exe`" http 8080 --location=$loc --subdomain=$sub --inspect=false >> `"$log`" 2>&1 & timeout /t 15 /nobreak >nul)`"" -WorkingDirectory $dir
$trigger = New-ScheduledTaskTrigger -AtStartup
$trigger.Delay = "PT1M"
$principal = New-ScheduledTaskPrincipal -UserId "maxim" -LogonType S4U
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit ([TimeSpan]::Zero) -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName "MirSportTuna" -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force | Out-Null
Start-ScheduledTask -TaskName "MirSportTuna"
Start-Sleep -Seconds 15
"Task: " + (Get-ScheduledTask -TaskName "MirSportTuna").State
Get-Content $log -Tail 20
EOF
} | ssh -i ~/.ssh/mir-sport-windows maxim@192.168.1.26 'powershell -NoProfile -Command -' 2>&1 | iconv -f cp866 -t utf-8
