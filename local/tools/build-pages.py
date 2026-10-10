"""Build a clearly labelled static preview for GitHub Pages; never export Bitrix data."""
from pathlib import Path
import shutil
import subprocess
import sys

root = Path(__file__).resolve().parents[2]
out = root / "dist"
out.mkdir(exist_ok=True)
subprocess.run([sys.executable, str(root / "design/_source/build.py"), str(out)], check=True)
shutil.copytree(root / "design/assets", out / "assets", dirs_exist_ok=True)
# Use the same local font as the Bitrix storefront.
shutil.copytree(root / "local/templates/mir_sport/fonts", out / "assets/fonts", dirs_exist_ok=True)
shutil.copyfile(root / "local/templates/mir_sport/css/fonts.css", out / "assets/css/fonts.css")
preview_css = """
.preview-notice { padding: 12px 16px; background: var(--c-surface); color: var(--c-text); font-size: var(--fs-14); text-align: center; }
.pill, .size, .check, .more-btn, .chip, .qty button, .cart-item__remove,
.topbar__links a, .megamenu__col a, .footer__col a, .footer__legal a,
.breadcrumbs a, .link-u, .link-line { min-height: var(--tap); }
.qty button, .size, .cart-item__remove { min-width: var(--tap); }
"""
(out / "assets/css/preview.css").write_text(preview_css)
for page in out.glob("*.html"):
    content = page.read_text()
    # Remove the remote Google Fonts imports from the preview.
    import re
    content = re.sub(r'<link[^>]*href="https://fonts\.(?:googleapis|gstatic)\.com[^>]*>\s*', '', content)
    content = content.replace('</head>', '<link rel="stylesheet" href="assets/css/fonts.css"><link rel="stylesheet" href="assets/css/preview.css"></head>')
    content = content.replace('<body>', '<body><aside class="preview-notice" role="note">Демонстрация «Мир спорта». Товары и цены — примеры. Заказы не отправляются, оплата недоступна.</aside>')
    content = content.replace('Заказ оформлен', 'Демонстрационный сценарий завершён')
    content = content.replace('Номер заказа: [номер из 1С]. Мы позвоним на', 'Заказ не отправлен. Телефон, введённый для проверки:')
    content = content.replace(', чтобы подтвердить заказ и доставку.', '. Данные остались в вашем браузере.')
    page.write_text(content)
(out / '.nojekyll').touch()
(out / 'robots.txt').write_text('User-agent: *\nDisallow: /\n')
print(f'Built {len(list(out.glob("*.html")))} preview pages in {out}')
