"""Provision Bitrix CommerceML using ignored .env credentials and stdin, never argv."""
import argparse
import json
import os
from pathlib import Path
import secrets
import subprocess

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser()
parser.add_argument('--apply', action='store_true')
args = parser.parse_args()
env_path = ROOT / '.env'
if not env_path.is_file():
    raise SystemExit('Create .env from .env.example before configuring the local stand.')
values = {}
for line in env_path.read_text().splitlines():
    if line.strip() and not line.lstrip().startswith('#') and '=' in line:
        key, value = line.split('=', 1)
        values[key.strip()] = value.strip().strip('\"\'')
login = values.get('BITRIX_1C_LOGIN', 'exchange_1c')
password = values.get('BITRIX_1C_PASSWORD', '')
command = ['docker', 'compose', 'exec', '-T', 'php', 'php', '/var/www/html/local/tools/setup-1c.php']
if args.apply and not password:
    probe = subprocess.run(command, cwd=ROOT, input=json.dumps({'login': login}), text=True, capture_output=True)
    if probe.returncode:
        raise SystemExit('Preflight failed; no credentials were generated.')
    state = json.loads(probe.stdout)
    if state['user_id'] is not None:
        raise SystemExit('Existing account has no password in .env. Restore its original password; no changes applied.')
    password = secrets.token_hex(20) + 'Aa1!'
    with env_path.open('a') as stream:
        stream.write('\n# Local CommerceML service account\n')
        if 'BITRIX_1C_LOGIN' not in values:
            stream.write(f'BITRIX_1C_LOGIN={login}\n')
        stream.write(f'BITRIX_1C_PASSWORD={password}\n')
    os.chmod(env_path, 0o600)
if args.apply:
    command.append('--apply')
result = subprocess.run(command, cwd=ROOT, input=json.dumps({'login': login, 'password': password}), text=True)
raise SystemExit(result.returncode)
