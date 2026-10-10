"""Export a private Bitrix migration bundle without changing the running stand."""
import datetime
import gzip
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile

ROOT = Path(__file__).resolve().parents[2]
os.umask(0o077)
destination = ROOT / '.migration' / datetime.datetime.now().strftime('%Y%m%d-%H%M%S')
destination.mkdir(parents=True, mode=0o700)

def capture(command, target, compress=False):
    # Keep diagnostics private: server output may contain configuration details.
    with tempfile.TemporaryFile() as diagnostic:
        with (gzip.open(target, 'wb') if compress else target.open('wb')) as output:
            process = subprocess.Popen(command, cwd=ROOT, stdout=subprocess.PIPE, stderr=diagnostic)
            try:
                shutil.copyfileobj(process.stdout, output)
            finally:
                process.stdout.close()
            if process.wait() != 0:
                raise RuntimeError('Export command failed. Incomplete bundle must not be restored.')

try:
    if not (ROOT / '.env').is_file():
        raise RuntimeError('Local .env is required for migration.')
    subprocess.run(['docker', 'compose', 'exec', '-T', 'php', 'php', '-r',
                    'exit(is_file("/var/www/html/bitrix/.settings.php") ? 0 : 1);'],
                   cwd=ROOT, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    capture(['docker', 'compose', 'exec', '-T', 'db', 'sh', '-c',
             'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --single-transaction --quick '
             '--no-tablespaces --set-gtid-purged=OFF -u"$MYSQL_USER" "$MYSQL_DATABASE"'],
            destination / 'database.sql.gz', compress=True)
    # Bind mounts are shipped with the project, not copied into the runtime volume.
    exclusions = ['local', 'index.php', '404.php', 'catalog', 'personal/cart',
                  'personal/order/make', 'personal/order/payment', 'search', 'info',
                  'policy', 'favorites', 'bitrix/cache', 'bitrix/managed_cache',
                  'bitrix/stack_cache', 'bitrix/html_pages', 'bitrix/backup', 'bitrix/tmp']
    capture(['docker', 'compose', 'exec', '-T', 'php', 'tar', '-czf', '-',
             *[f'--exclude=./{path}' for path in exclusions], '-C', '/var/www/html', '.'],
            destination / 'runtime.tar.gz')
    tracked = subprocess.check_output(['git', 'ls-files', '-z'], cwd=ROOT).decode().split('\0')
    # Include this export tool even before its first commit; omit unrelated untracked work.
    paths = set(filter(None, tracked)) | {'.env', 'local/tools/export-stand.py'}
    for path in ('local/.settings.php', 'local/.settings_extra.php', 'local/php_interface/dbconn.php'):
        if (ROOT / path).is_file():
            paths.add(path)
    with tarfile.open(destination / 'project.tar.gz', 'w:gz') as archive:
        for path in sorted(paths):
            source = ROOT / path
            if source.is_file() or source.is_symlink():
                archive.add(source, arcname=path, recursive=False)
    payloads = ('project.tar.gz', 'runtime.tar.gz', 'database.sql.gz')
    manifest = {'created_at': datetime.datetime.now(datetime.timezone.utc).isoformat(),
                'git_commit': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip(),
                'contains_secrets': True, 'files': {}}
    for name in payloads:
        digest = hashlib.sha256()
        with (destination / name).open('rb') as stream:
            for block in iter(lambda: stream.read(1024 * 1024), b''):
                digest.update(block)
        manifest['files'][name] = {'sha256': digest.hexdigest(), 'bytes': (destination / name).stat().st_size}
    (destination / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    bundle = destination / 'mir-sport-migration.tar'
    with tarfile.open(bundle, 'w') as archive:
        for name in (*payloads, 'manifest.json'):
            archive.add(destination / name, arcname=name)
    print(json.dumps({'bundle': str(bundle), 'bytes': bundle.stat().st_size,
                      'contains_secrets': True, 'source_modified': False}, indent=2))
except Exception:
    # Preserve partial files privately for diagnosis, but never mark them complete.
    raise SystemExit('Export failed. Private partial files are in .migration; do not restore them.')
