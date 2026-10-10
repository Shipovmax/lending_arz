"""Check CommerceML authorization without importing any data or exposing credentials."""
import argparse
import base64
from http.cookiejar import CookieJar
import json
import os
from pathlib import Path
import subprocess
from urllib.error import HTTPError
from urllib.parse import parse_qs, urlencode, urlsplit
from urllib.request import Request, build_opener, HTTPCookieProcessor, HTTPRedirectHandler

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser()
parser.add_argument('--url')
parser.add_argument('--init-empty', action='store_true', help='Check init only when the local exchange work directory is empty.')
args = parser.parse_args()
values = {}
for line in (ROOT / '.env').read_text().splitlines():
    if line.strip() and not line.lstrip().startswith('#') and '=' in line:
        key, value = line.split('=', 1)
        values[key.strip()] = value.strip().strip('\"\'')
port = os.environ.get('HTTP_PORT', values.get('HTTP_PORT', '8080'))
if not port.isdigit() or not 1 <= int(port) <= 65535:
    raise SystemExit('Invalid HTTP_PORT.')
local_url = f'http://localhost:{port}/local/exchange/1c.php'
args.url = args.url or local_url
parts = urlsplit(args.url)
if parts.username or parts.password or parts.query or parts.fragment:
    raise SystemExit('Use an exchange URL without credentials, query or fragment.')
if parts.scheme != 'https' and parts.hostname not in ('localhost', '127.0.0.1', '::1'):
    raise SystemExit('Use HTTPS for a non-loopback exchange URL.')
if args.init_empty and args.url != local_url:
    raise SystemExit('--init-empty requires the exact URL of the current local Docker stand.')

class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        return None

def client():
    return build_opener(NoRedirect(), HTTPCookieProcessor(CookieJar()))

login = values['BITRIX_1C_LOGIN']
password = values['BITRIX_1C_PASSWORD']
opener = client()
checks = []

def request(kind, mode, auth=None, sessid=None, http_client=None):
    params = {'type': kind, 'mode': mode}
    if sessid:
        params['sessid'] = sessid
    headers = {}
    if auth:
        headers['Authorization'] = 'Basic ' + base64.b64encode(f'{login}:{auth}'.encode()).decode()
    req = Request(args.url + '?' + urlencode(params), headers=headers)
    try:
        with (http_client or opener).open(req, timeout=30) as response:
            return response.status, response.read().decode('utf-8-sig', errors='replace').strip()
    except HTTPError as error:
        if 300 <= error.code < 400:
            raise RuntimeError('Redirect refused; credentials were not forwarded.') from None
        return error.code, error.read().decode('utf-8', errors='replace').strip()

def record(name, passed):
    checks.append({'check': name, 'passed': passed})
    if not passed:
        raise RuntimeError(f'Failed: {name}. Response contents withheld to protect sessions.')

status, body = request('catalog', 'checkauth', http_client=client())
record('Anonymous authentication rejected', status in (401, 403) or body.startswith('failure') or ('USER_LOGIN' in body and not body.startswith('success')))
status, body = request('catalog', 'checkauth', auth=password + '-invalid', http_client=client())
record('Wrong password rejected', status in (401, 403) or body.startswith('failure') or ('USER_LOGIN' in body and not body.startswith('success')))
status, body = request('catalog', 'checkauth', auth=password)
lines = body.splitlines()
record('Service account authenticated', status == 200 and len(lines) >= 4 and lines[0] == 'success')
session = parse_qs(lines[3]).get('sessid', [''])[0]
record('Protocol session token returned', bool(session))
# Authorization is tested at init; checkauth alone does not establish importer permissions.
status, body = request('sale', 'init', auth=password, sessid=session)
record('Order exchange denied', status in (401, 403) or body.startswith('failure') or ('USER_LOGIN' in body and not body.startswith('success')))
status, body = request('get_catalog', 'init', auth=password, sessid=session)
record('Catalog export denied', status in (401, 403) or body.startswith('failure') or ('USER_LOGIN' in body and not body.startswith('success')))
if args.init_empty:
    if parts.hostname not in ('localhost', '127.0.0.1', '::1'):
        raise SystemExit('--init-empty applies only to the local Docker stand.')
    probe = subprocess.run(['docker', 'compose', 'exec', '-T', 'php', 'php', '-r',
        '$p="/var/www/html/upload/1c_catalog"; echo is_dir($p) ? count(array_diff(scandir($p),[".","..",".htaccess"])) + ((is_file($p."/.htaccess") && trim(file_get_contents($p."/.htaccess")) !== "Deny from All") ? 1 : 0) : 0;'],
        cwd=ROOT, text=True, capture_output=True, check=True)
    if probe.stdout.strip() != '0':
        raise SystemExit('Refusing init: existing exchange files could be deleted by Bitrix.')
    status, body = request('catalog', 'init', auth=password, sessid=session)
    record('Catalog import init allowed', status == 200 and 'file_limit=' in body and 'zip=' in body and not body.startswith('failure'))
print(json.dumps({'url': args.url, 'checks': checks, 'data_imported': False}, ensure_ascii=False, indent=2))
