#!/bin/bash
set -euo pipefail
umask 077
export MYSQL_PWD="$MYSQL_PASSWORD"
# Refuse to import into a populated database; this migration targets a new volume.
count=$(mysql -u"$MYSQL_USER" "$MYSQL_DATABASE" -Nse 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')
if [ "$count" != 0 ]; then
    echo 'Refusing SQL restore: target database is not empty.' >&2
    exit 1
fi
# Never relay SQL diagnostics containing row values to the operator console.
if ! gzip -dc /tmp/mir-sport-database.sql.gz | mysql -u"$MYSQL_USER" "$MYSQL_DATABASE" 2>/tmp/mir-sport-import-errors.log; then
    echo 'SQL import failed; review the private diagnostic inside the container.' >&2
    exit 1
fi
mysql -u"$MYSQL_USER" "$MYSQL_DATABASE" -Nse 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()'
echo 'SQL restore completed.'
