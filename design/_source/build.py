"""Build the static prototype: wrap each page in the shared head, header, footer and cookie banner.

Usage: python3 design/_source/build.py   (writes design/*.html)
The output is plain HTML/CSS/JS with no build step needed to open it.
"""
import html
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).parent  # _source/: partials and pages
OUT = pathlib.Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT.parent  # design/

# Demo products. In Bitrix these come from the catalog iblock synced with 1C.
PRODUCTS = {
    1: dict(name="Кроссовки беговые мужские", dir="sport", price=4290, old=5490, sizes=["40", "41", "42", "43", "44", "45"], stock=True, meta="Размеры 40–45"),
    2: dict(name="Футбольный мяч, размер 5", dir="sport", price=1490, old=None, sizes=[], stock=True, meta="Размер 5"),
    3: dict(name="Куртка спортивная женская", dir="sport", price=3890, old=None, sizes=["42", "44", "46", "48"], stock=False, meta="Размеры 42–48 · 3 цвета"),
    4: dict(name="Костюм тренировочный подростковый", dir="sport", price=2990, old=3590, sizes=["42", "44"], stock=True, meta="Рост 146–170 см"),
    5: dict(name="Кеды мужские", dir="sport", price=2590, old=None, sizes=["41", "42", "43", "44"], stock=True, meta="Размеры 41–44"),
    6: dict(name="Футболка спортивная мужская", dir="sport", price=990, old=1290, sizes=["46", "48", "50"], stock=True, meta="Размеры 46–50"),
    7: dict(name="Спиннинг 2,4 м, тест 5–20 г", dir="fish", price=3190, old=None, sizes=[], stock=True, meta="Длина 2,4 м"),
    8: dict(name="Катушка безынерционная 2500", dir="fish", price=2450, old=None, sizes=[], stock=True, meta="Шпуля 2500"),
    9: dict(name="Набор воблеров, 5 шт.", dir="fish", price=1190, old=1490, sizes=[], stock=False, meta="Длина 70 мм"),
}
LISTS = {
    "home": [1, 2, 7, 3],
    "catalog": [1, 2, 3, 4, 5, 6, 7, 8, 9],
    "similar": [5, 6, 4, 2],
    "favorites": [1, 7, 3],
}


def rub(n: int) -> str:
    return f"{n:,}".replace(",", " ") + " ₽"


def card(pid: int, fav: bool = False) -> str:
    p = PRODUCTS[pid]
    name = html.escape(p["name"])
    old = f'<span class="price-old"><span class="visually-hidden">Старая цена </span>{rub(p["old"])}</span>' if p["old"] else ""
    stock = '<span class="card__stock ok">В наличии в магазине</span>' if p["stock"] else '<span class="card__stock muted">Под заказ, 3–5 дней</span>'
    pressed = "true" if fav else "false"
    return f"""      <article class="card" data-id="{pid}" data-dir="{p['dir']}" data-stock="{int(p['stock'])}" data-sale="{int(bool(p['old']))}" data-sizes="{','.join(p['sizes'])}">
        <div class="card__media">
          <a class="card__photo photo" href="product.html" tabindex="-1" aria-hidden="true">Фото товара</a>
          <button class="icon-btn card__fav" type="button" aria-pressed="{pressed}" aria-label="Добавить в избранное: {name}" data-fav><svg class="icon"><use href="#i-heart"/></svg></button>
        </div>
        <div class="card__price"><span class="price">{rub(p['price'])}</span>{old}</div>
        <a class="card__name link-u" href="product.html">{name}</a>
        <span class="card__meta">{html.escape(p['meta'])}</span>
        {stock}
        <button class="btn btn--sm card__buy" type="button" data-buy>В корзину</button>
      </article>"""


HEAD = """<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title}</title>
<meta name="description" content="{description}">
<meta property="og:title" content="{title}">
<meta property="og:description" content="{description}">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="assets/css/tokens.css">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
"""

TAIL = """<script src="assets/js/main.js" defer></script>
</body>
</html>
"""


def build() -> None:
    # Assets live in design/assets and are edited there directly; only HTML is generated.
    header = (ROOT / "partials/header.html").read_text(encoding="utf-8")
    footer = (ROOT / "partials/footer.html").read_text(encoding="utf-8")
    cookie = (ROOT / "partials/cookie.html").read_text(encoding="utf-8")
    for page in sorted((ROOT / "pages").glob("*.html")):
        text = page.read_text(encoding="utf-8")
        m = re.match(r"<!-- meta: (.*?) \| (.*?) \| (.*?) -->\n", text)
        if not m:
            raise SystemExit(f"{page.name}: missing meta line")
        title, description, nav = m.groups()
        body = text[m.end():]

        def cards(match: re.Match) -> str:
            key = match.group(1)
            return "\n".join(card(pid, fav=(key == "favorites")) for pid in LISTS[key])

        body = re.sub(r"<!--CARDS:(\w+)-->", cards, body)
        head = header
        if nav != "none":
            head = head.replace(f'data-nav="{nav}"', f'data-nav="{nav}" aria-current="page"')
        out = (
            HEAD.format(title=html.escape(title), description=html.escape(description))
            + head
            + '\n<main id="main">\n'
            + body
            + "</main>\n\n"
            + footer
            + "\n"
            + cookie
            + TAIL
        )
        (OUT / page.name).write_text(out, encoding="utf-8")
        print("built", page.name)


if __name__ == "__main__":
    build()
