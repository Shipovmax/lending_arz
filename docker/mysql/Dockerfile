FROM mysql:8.0

# Windows bind mounts appear world-writable and MySQL ignores such config files.
COPY bitrix.cnf /etc/mysql/conf.d/bitrix.cnf
RUN chmod 0644 /etc/mysql/conf.d/bitrix.cnf
