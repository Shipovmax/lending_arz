#!/bin/sh
# Debug aid, runs INSIDE the php container: keeps copies of the files 1C uploads, because Bitrix deletes them after
# processing. Stored outside the web root (/var/www/exchange_capture); lost when the container is recreated.
# Keeps every prices/rests file and the first 20 files of any other type (cap: 600 files).
SRC=/var/www/html/upload/1c_catalog
DST=/var/www/exchange_capture
mkdir -p "$DST"
while true; do
  for f in "$SRC"/*.xml "$SRC"/*.zip; do
    [ -f "$f" ] || continue
    b=$(basename "$f")
    [ -e "$DST/$b" ] && continue
    type=${b%%___*}
    case "$type" in
      prices|rests) ;;
      *) [ "$(ls "$DST" | grep -c "^$type")" -ge 20 ] && continue ;;
    esac
    [ "$(ls "$DST" | wc -l)" -ge 600 ] && continue
    cp "$f" "$DST/$b"
  done
  sleep 1
done
