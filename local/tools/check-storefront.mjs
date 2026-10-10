/**
 * Browser smoke checks using isolated guest sessions; never submits an order.
 * Install Playwright and Chromium first. Run:
 *   node local/tools/check-storefront.mjs
 * Optional: BASE_URL, QA_OUTPUT_DIR, PLAYWRIGHT_MODULE (absolute package path),
 * PLAYWRIGHT_BROWSERS_PATH. Reports/screenshots default to /tmp/mir-storefront-qa.
 */
import { createRequire } from 'node:module';
import { mkdir, writeFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.BASE_URL || 'http://localhost:8080';
const output = process.env.QA_OUTPUT_DIR || '/tmp/mir-storefront-qa';
await mkdir(output, { recursive: true });
const browser = await chromium.launch({ headless: true });
const reports = [];

try {
  for (const width of [375, 1280]) {
    const context = await browser.newContext({ viewport: { width, height: 900 } });
    const page = await context.newPage();
    const report = { width, checks: [], errors: [] };
    page.on('pageerror', error => report.errors.push(error.message));
    page.on('console', message => {
      if (message.type() === 'error' && !message.text().includes('404')) {
        report.errors.push(message.text());
      }
    });
    // Fail closed if a future change accidentally submits the checkout form.
    await page.route('**/*', async route => {
      const body = route.request().postData() || '';
      if (/(?:soa-action|action)(?:=|%3D)saveOrderAjax/.test(body)) {
        report.errors.push('Blocked unexpected order submission');
        await route.abort();
      } else {
        await route.continue();
      }
    });
    const go = async path => {
      const response = await page.goto(base + path, { waitUntil: 'networkidle' });
      return response.status();
    };
    const screenshot = name => page.screenshot({ path: `${output}/${width}-${name}.png`, fullPage: true });
    const check = async (name, fn) => {
      try {
        const evidence = await fn();
        report.checks.push({ name, passed: true, evidence });
      } catch (error) {
        report.checks.push({ name, passed: false, error: error.message });
      }
    };
    const openFilters = async () => {
      if (width === 375) await page.locator('[data-filters-toggle]').click();
    };
    let productUrl;
    await check('Public pages and genuine 404', async () => {
      const statuses = {};
      for (const path of ['/', '/catalog/', '/favorites/', '/personal/cart/', '/info/', '/policy/', '/search/']) {
        statuses[path] = await go(path);
        assert.equal(statuses[path], 200, path);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `Overflow: ${path}`);
        await screenshot(path.replaceAll('/', '_'));
      }
      statuses['missing'] = await go('/qa-nonexistent-page-123/');
      assert.equal(statuses.missing, 404);
      return statuses;
    });
    await go('/catalog/');
    const cookie = page.locator('[data-cookie-choice="necessary"]');
    if (await cookie.isVisible()) await cookie.click();
    await check('Size, color and brand filters', async () => {
      const evidence = [];
      for (const legend of ['Размеры одежды', 'Цвет', 'Бренд']) {
        await go('/catalog/');
        await openFilters();
        const group = page.locator('[data-smart-filter] fieldset').filter({ has: page.locator('legend', { hasText: legend }) });
        const label = group.locator('label').first();
        const choice = await label.innerText();
        const name = await label.locator('input').getAttribute('name');
        await Promise.all([page.waitForURL(url => url.searchParams.has(name)), label.click()]);
        await page.waitForLoadState('networkidle');
        assert.equal(await page.locator(`[name="${name}"][type="checkbox"]`).isChecked(), true);
        evidence.push({ legend, choice, url: page.url() });
      }
      return evidence;
    });
    await check('Price and stock filters', async () => {
      await go('/catalog/');
      await openFilters();
      const input = page.locator('[aria-label="Цена до"]');
      const name = await input.getAttribute('name');
      await input.fill('800');
      await Promise.all([page.waitForURL(url => url.searchParams.get(name) === '800'), input.press('Tab')]);
      await page.waitForLoadState('networkidle');
      const priceUrl = page.url();
      await go('/catalog/');
      await Promise.all([page.waitForURL(url => url.searchParams.get('stock') === 'Y'), page.locator('a.pill[role="button"]').filter({ hasText: 'В наличии' }).click()]);
      await page.waitForLoadState('networkidle');
      return { priceUrl, stockUrl: page.url() };
    });
    await check('SKU selection and adding to cart', async () => {
      await go('/catalog/');
      productUrl = await page.locator('a').filter({ hasText: 'Туфли Ультра Лайн' }).first().getAttribute('href');
      assert.ok(productUrl, 'QA fixture not found; choose an available product with SKU');
      await go(productUrl);
      await screenshot('product');
      const root = page.locator('[data-product-json]');
      assert.equal(await root.count(), 1);
      await page.locator('[data-add-to-cart]').click();
      assert.equal(await page.locator('[data-size-error]').isVisible(), true);
      const colors = page.locator('[data-color]');
      if (await colors.count() > 1) await colors.nth(1).click();
      const size = page.locator('[data-size]:not(:disabled)').first();
      await size.click();
      const chosenSize = await size.innerText();
      await page.locator('[data-add-to-cart]').click();
      await page.locator('[data-added]').waitFor({ state: 'visible' });
      assert.equal(await page.locator('[data-add-error]').isVisible(), false);
      return { productUrl, chosenSize };
    });
    await check('Basket quantity and persistence', async () => {
      await go('/personal/cart/');
      const input = page.locator('[data-entity="basket-item-quantity-field"]').first();
      assert.equal(await input.inputValue(), '1');
      await page.locator('[data-entity="basket-item-quantity-plus"]').first().click();
      await page.waitForFunction(() => document.querySelector('[data-entity="basket-item-quantity-field"]')?.value === '2');
      await page.waitForTimeout(1200);
      await page.reload({ waitUntil: 'networkidle' });
      assert.equal(await input.inputValue(), '2');
      assert.match(await page.locator('main').innerText(), /Товары, 2 шт\./);
      await screenshot('cart');
      return { quantity: 2 };
    });
    await check('Checkout to required fields without submitting', async () => {
      await go('/personal/order/make/');
      const stages = [];
      for (let i = 0; i < 6; i++) {
        stages.push(await page.locator('main').innerText());
        const next = page.getByRole('button', { name: 'Далее', exact: true });
        let visible;
        for (let j = 0; j < await next.count(); j++) {
          if (await next.nth(j).isVisible()) { visible = next.nth(j); break; }
        }
        if (!visible) break;
        await visible.click();
        await page.waitForTimeout(700);
      }
      await screenshot('checkout');
      const consent = page.locator('[data-bx-user-consent] input[type="checkbox"]');
      assert.equal(await consent.isChecked(), false);
      assert.equal(await consent.isVisible(), true);
      const controls = await page.locator('[data-save-button]').evaluateAll(xs => xs.map(x => ({ text: x.innerText.trim(), visible: !!x.getClientRects().length })));
      assert.equal(controls.filter(x => x.visible).length, 1, 'Exactly one visible order button');
      const required = await page.locator('input[name^="ORDER_PROP_"]').evaluateAll(xs => xs.filter(x => x.getClientRects().length).map(x => ({ name: x.name, value: x.value })));
      return { controls, consentChecked: false, required, stages };
    });
    await check('Remove basket item', async () => {
      await go('/personal/cart/');
      const remove = page.locator('[data-entity="basket-item-delete"]').first();
      if (await remove.count()) {
        await remove.click();
        await page.waitForTimeout(1200);
      }
      await page.reload({ waitUntil: 'networkidle' });
      assert.match(await page.locator('main').innerText(), /Корзина пуста/);
      return { empty: true };
    });
    reports.push(report);
    await context.close();
    await writeFile(`${output}/report.json`, JSON.stringify(reports, null, 2));
    console.log(`${width}px: ${report.checks.filter(x => x.passed).length}/${report.checks.length} checks, ${report.errors.length} JS errors`);
  }
} finally {
  await browser.close();
}
if (reports.some(r => r.errors.length || r.checks.some(c => !c.passed))) process.exitCode = 1;
