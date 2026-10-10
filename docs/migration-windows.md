# Перенос стенда на Windows 11

Приёмник: ASUS с Windows 11, Docker Desktop с Linux containers и WSL 2. Mac и ASUS находятся в одной локальной сети. Постоянный внешний адрес обмена с 1С пока не настроен.

## Состояние на 10.10.2026

Стенд восстановлен на `192.168.1.26`, пользователь Windows — `maxim`. Проект: `C:\Users\maxim\mir-sport-server`. Ядро/загрузки находятся в Linux-томе `mir_sport_server_bitrix_www`, база — в `mir_sport_server_bitrix_db`. Восстановлены 697 таблиц; контрольные суммы пакета совпали. Docker запускается через Windows CLI; интеграция отдельной Ubuntu WSL с Docker для этого не требуется.

- Сайт: `http://192.168.1.26:8080/`.
- Админка: `http://192.168.1.26:8080/bitrix/admin/`.
- Учётные записи сохранены из базы; пароль администратора не сбрасывался. Вход `admin` с паролем из `.env` проверен через браузер и SSH-forward.
- `docker-compose.override.yml` на приёмнике задаёт `restart: unless-stopped` всем трём службам и игнорируется Git.
- Порт 8080 разрешён Windows Firewall только из локальной подсети. Внешний доступ не настроен.
- Сон и гибернация от сети отключены. Docker Desktop добавлен в автозапуск пользователя; после перезагрузки Windows нужен вход `maxim`. Перезагрузка и запуск без входа пользователя не проверены.

MySQL-конфиг теперь включается в образ через `docker/mysql/Dockerfile` с правами `0644`: файлы Windows bind mount видны как world-writable, и MySQL отказывался читать их. Настройки часового пояса и SQL действительно применены на приёмнике; авторизация CommerceML проходит.

При первоначальной настройке автозапуска команда пересоздала ключ `HKCU\Software\Microsoft\Windows\CurrentVersion\Run`. Шесть прежних записей восстановлены из сохранённых данных реестра, `StartupApproved` не менялся. Скрипт исправлен: существующий ключ больше не пересоздаётся. Приватные материалы восстановления сохранены в папке резервной копии Windows; запуск этих программ после перезагрузки не проверен.

Проверки: QA круг 1 — ГОДНО; витрина 7/7 на 375 и 1280 px, ошибок JS нет; HTTP главной/админки — 200, вход администратора успешен; CommerceML 6/6 через SSH-forward, без импорта. Бэкенд-ревью — ГОДНО. Реальная выгрузка 1С, новый заказ, эквайринг/доставка и перезагрузка Windows не проверены.

## Копия источника

```bash
python3 local/tools/export-stand.py
```

Скрипт читает работающий стенд и сохраняет приватный пакет в `.migration/<дата-время>/mir-sport-migration.tar`:

- `project.tar.gz`: рабочие версии отслеживаемых Git файлов, `.env`, существующие локальные конфигурации подключения;
- `runtime.tar.gz`: ядро и файлы из Docker-тома, включая загрузки и настройки Битрикса; bind mounts, кеш и старые резервные копии исключены;
- `database.sql.gz`: логическая копия базы MySQL через `mysqldump --single-transaction`;
- `manifest.json`: SHA-256 и размеры трёх файлов.

Пароли не выводятся и не передаются в аргументах процессов. Каталог `.migration` игнорируется Git, права каталога/файлов ограничены владельцем. Архив содержит реальные учётные данные и данные базы: передавать его по SSH/SFTP, не публиковать в GitHub или через открытый HTTP-сервер.

Копия базы транзакционно согласована для InnoDB, но база и файлы не образуют общий атомарный снимок. На время итогового переноса не оформлять заказы, не запускать обмен/обновление ядра и не менять загружаемые файлы. Если источник продолжал меняться, сделать новую копию перед переключением.

## Подготовка подключения к ASUS

На ASUS в PowerShell от администратора:

```powershell
Add-WindowsCapability -Online -Name OpenSSH.Server~~~~0.0.1.0
Start-Service sshd
Set-Service -Name sshd -StartupType Automatic
```

Проверить профиль домашней сети и правила Windows Firewall: SSH нужен из локальной сети; порт 22 на роутере во внешнюю сеть не пробрасывать. Если подключение блокируется, проверить созданное установкой правило `OpenSSH-Server-In-TCP` и текущий сетевой профиль.

В обычном PowerShell получить имя пользователя и IPv4 домашней сети:

```powershell
whoami
ipconfig
```

Первоначально для входа по паролю нужен пароль учётной записи Windows, а не PIN Windows Hello. Пароль не отправлять в чат. На текущем стенде уже проверен вход по выделенному ключу Mac; публичный ключ установлен в `C:\ProgramData\ssh\administrators_authorized_keys`, закрытый — только на Mac в `~/.ssh/mir-sport-windows`.

## Восстановление на чистый приёмник

Передать пакет по SCP/SFTP, проверить SHA-256, распаковать внешнюю оболочку в `C:\Users\maxim\mir-sport-backup` и `project.tar.gz` в отдельную папку проекта. Добавить в `.env` приёмника `COMPOSE_PROJECT_NAME=mir_sport_server` и сохранить локальную политику рестарта в `docker-compose.override.yml`. Существующие тома и данные приёмника не перезаписывать. Простого клонирования GitHub и `docker compose up -d` недостаточно: ядра и базы в репозитории нет.

В PowerShell из папки проекта:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\local\tools\server-windows.ps1 -Action build
powershell -NoProfile -ExecutionPolicy Bypass -File .\local\tools\restore-windows.ps1
```

Сценарий проверяет хеши runtime/SQL и отсутствие обоих целевых томов по точным именам. SQL импортируется только в пустую БД. PHP/nginx запускаются после восстановления. Повторный запуск на текущем сервере запрещён guard; при частичном сбое нужно сначала выяснить состояние созданных томов, а не удалять их автоматически.

Отдельная настройка Windows выполняется elevated-скриптом `local/tools/configure-server-windows.ps1`; у команды `ExecutionPolicy Bypass` область действия только запускаемый процесс, системная политика не менялась.

Mac остаётся резервным исходным стендом. Экспериментальный внешний туннель на Mac остановлен.

## Управление с Mac

```bash
ssh -i ~/.ssh/mir-sport-windows maxim@192.168.1.26
```

В Windows PowerShell:

```powershell
cd C:\Users\maxim\mir-sport-server
powershell -NoProfile -ExecutionPolicy Bypass -File .\local\tools\server-windows.ps1 -Action status
powershell -NoProfile -ExecutionPolicy Bypass -File .\local\tools\server-windows.ps1 -Action start
```

Доступны также `stop`, `logs`, `build`. Для SSH-команд используется отдельный клиентский профиль `C:\Users\maxim\.docker-mir-sport-server`: Windows Credential Manager не доступен из этой сессии по ключу. Профиль не содержит учётных данных Docker Hub; в нём сохранены только пути CLI plugins и пустая запись registry, отключающая автоматическое обращение к wincred. Основные настройки Docker Desktop не заменялись.

Рабочая копия на Windows подключена к `https://github.com/Shipovmax/lending_arz.git`, ветка `main`. После коммита и push на Mac обновить код на Windows:

```powershell
cd C:\Users\maxim\mir-sport-server
git pull --ff-only
```

Шаблон и страницы смонтированы из рабочей копии, поэтому изменения файлов видны без пересборки. После изменения Dockerfile или MySQL-конфига выполнить `server-windows.ps1 -Action build`, затем `-Action start`. Изменения схемы Битрикса применять отдельно соответствующим скриптом; `git pull` не переносит базу или загрузки.

Для защищённого локального просмотра с Mac можно открыть SSH-forward и оставить терминал работающим:

```bash
ssh -N -i ~/.ssh/mir-sport-windows -L 127.0.0.1:18080:127.0.0.1:8080 maxim@192.168.1.26
```

Тогда сайт доступен на `http://localhost:18080/`, админка — на `http://localhost:18080/bitrix/admin/`. Этот режим использовался для протокольной проверки, чтобы не передавать служебный пароль обмена открытым HTTP по LAN.
