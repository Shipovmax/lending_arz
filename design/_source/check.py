"""Smoke test of the prototype: screenshots at 1280 and 375, console errors, key interactions."""
import pathlib
import sys

from playwright.sync_api import sync_playwright

DIST = pathlib.Path(sys.argv[1]).resolve()
SHOTS = pathlib.Path(sys.argv[2]).resolve()
SHOTS.mkdir(parents=True, exist_ok=True)
PAGES = ["index", "catalog", "product", "cart", "info", "policy", "favorites", "404"]
problems = []


def url(name, suffix=""):
    return (DIST / f"{name}.html").as_uri() + suffix


with sync_playwright() as p:
    browser = p.chromium.launch()
    for width, tag in ((1280, "d"), (375, "m")):
        ctx = browser.new_context(viewport={"width": width, "height": 900})
        page = ctx.new_page()
        page.on("console", lambda m: m.type == "error" and "fonts.g" not in m.text and problems.append(f"[{tag}] console: {m.text}"))
        page.on("pageerror", lambda e: problems.append(f"[{tag}] pageerror: {e}"))
        for name in PAGES:
            page.goto(url(name))
            page.wait_for_timeout(150)
            overflow = page.evaluate("document.documentElement.scrollWidth - window.innerWidth")
            if overflow > 1:
                problems.append(f"[{tag}] {name}: horizontal overflow {overflow}px")
            page.screenshot(path=str(SHOTS / f"{tag}-{name}.png"), full_page=True)
        ctx.close()

    # Interactions on desktop
    ctx = browser.new_context(viewport={"width": 1280, "height": 900})
    page = ctx.new_page()
    page.on("pageerror", lambda e: problems.append(f"pageerror: {e}"))
    page.goto(url("index"))
    page.click("[data-cookie-choice=all]")
    page.fill("[data-search-input]", "крос")
    page.wait_for_timeout(100)
    rows = page.locator(".suggest__row").count()
    if rows < 2:
        problems.append(f"search: expected suggestions, got {rows}")
    page.screenshot(path=str(SHOTS / "d-search.png"))
    page.keyboard.press("Escape")
    page.click("[data-megamenu-toggle]")
    page.hover("[data-mm-dir=mm-fish]")
    if not page.is_visible("#mm-fish"):
        problems.append("megamenu: fish panel not shown")
    page.screenshot(path=str(SHOTS / "d-megamenu.png"))

    page.goto(url("catalog"))
    total = page.locator("[data-count]").inner_text()
    page.click("[data-quick=sale]")
    sale = page.locator("[data-count]").inner_text()
    page.click("[data-dir=fish]")
    fish = page.locator("[data-count]").inner_text()
    if not (total == "6" and sale == "3" and fish == "1"):
        problems.append(f"catalog counts unexpected: all={total} sale={sale} fish+sale={fish}")

    page.goto(url("product"))
    page.click("[data-add-to-cart]")
    if not page.is_visible("[data-size-error]"):
        problems.append("product: no size error")
    page.click(".size[data-size='42']")
    page.click("[data-add-to-cart]")
    if not page.is_visible("[data-added]"):
        problems.append("product: added notice not shown")

    page.goto(url("cart"))
    page.click("[data-submit]")
    if not page.is_visible("[data-field=name] .err"):
        problems.append("checkout: no name error")
    page.fill("[name=name]", "Иван")
    page.fill("[name=phone]", "+7 900 123-45-67")
    page.click("[data-submit]")
    if not page.is_visible("[data-consent-error]"):
        problems.append("checkout: no consent error")
    page.check("[data-consent]")
    page.click("[data-submit]")
    if not page.is_visible("[data-success]"):
        problems.append("checkout: success not shown")

    page.goto(url("info", "#sizes"))
    if not page.is_visible("#sizes") or page.is_visible("#delivery"):
        problems.append("info: hash section switching broken")

    # Mobile menu
    mctx = browser.new_context(viewport={"width": 375, "height": 812})
    m = mctx.new_page()
    m.goto(url("index"))
    m.click("[data-cookie-choice=necessary]")
    m.click("[data-menu-toggle]")
    if not m.is_visible("[data-mobile-menu]"):
        problems.append("mobile: menu not shown")
    m.screenshot(path=str(SHOTS / "m-menu.png"))
    m.goto(url("catalog"))
    m.click("[data-filters-toggle]")
    if not m.is_visible("[data-filters]"):
        problems.append("mobile: filters not shown")
    m.screenshot(path=str(SHOTS / "m-filters.png"))
    browser.close()

print("\n".join(problems) if problems else "ALL CHECKS PASSED")
