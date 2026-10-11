<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Mega menu and mobile menu. Sections and brands come from the catalog (1C groups and the brand property),
 * so the menus follow the data: empty sections and placeholders are not shown.
 */
require_once __DIR__ . '/contacts.php';
require_once __DIR__ . '/showcase.php';
$contacts = \MirSport\contacts();
$menuStore = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$menuSections = \MirSport\catalog_sections($menuStore['catalog_iblock_id']);
$menuBrands = \MirSport\brand_links($menuStore['catalog_iblock_id'], $menuStore['brand_props'], $menuStore['hide_unavailable'] === 'Y');
$storeLine = implode(' · ', array_filter([$contacts['address'], $contacts['hours']]));
?>
<div class="megamenu" id="megamenu" hidden data-megamenu>
    <div class="container megamenu__inner">
      <div class="megamenu__dirs" role="tablist" aria-orientation="vertical" aria-label="Каталог">
        <button class="megamenu__dir" type="button" role="tab" id="mm-tab-catalog" tabindex="0" aria-selected="true" aria-controls="mm-catalog" data-mm-dir="mm-catalog">Разделы <span aria-hidden="true">→</span></button>
        <?php if ($menuBrands): ?>
        <button class="megamenu__dir" type="button" role="tab" id="mm-tab-brands" tabindex="-1" aria-selected="false" aria-controls="mm-brands" data-mm-dir="mm-brands">Бренды <span aria-hidden="true">→</span></button>
        <?php endif ?>
      </div>
      <div class="megamenu__panel" id="mm-catalog" role="tabpanel" aria-labelledby="mm-tab-catalog">
        <?php foreach ($menuSections as $section): ?>
        <div class="megamenu__col">
          <h3><a href="<?= htmlspecialcharsbx($section['url']) ?>"><?= htmlspecialcharsbx($section['name']) ?></a></h3>
          <?php foreach ($section['children'] as $child): ?>
          <a class="link-u" href="<?= htmlspecialcharsbx($child['url']) ?>"><?= htmlspecialcharsbx($child['name']) ?></a>
          <?php endforeach ?>
        </div>
        <?php endforeach ?>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Весь каталог</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/?sort=new">Новинки</a></div>
      </div>
      <?php if ($menuBrands): ?>
      <div class="megamenu__panel" id="mm-brands" role="tabpanel" aria-labelledby="mm-tab-brands" hidden>
        <?php foreach (array_chunk($menuBrands, (int)ceil(count($menuBrands) / min(4, count($menuBrands)))) as $column): ?>
        <div class="megamenu__col">
          <?php foreach ($column as $brand): ?>
          <a class="link-u" href="<?= htmlspecialcharsbx($brand['url']) ?>"><?= htmlspecialcharsbx($brand['name']) ?></a>
          <?php endforeach ?>
        </div>
        <?php endforeach ?>
      </div>
      <?php endif ?>
    </div>
  </div>

  <nav class="mobile-menu" id="mobile-menu" aria-label="Меню" hidden data-mobile-menu>
    <?php foreach ($menuSections as $section): ?>
    <?php if ($section['children']): ?>
    <details><summary><?= htmlspecialcharsbx($section['name']) ?></summary><div class="mobile-menu__sub"><a href="<?= htmlspecialcharsbx($section['url']) ?>">Все товары раздела</a><?php foreach ($section['children'] as $child): ?><a href="<?= htmlspecialcharsbx($child['url']) ?>"><?= htmlspecialcharsbx($child['name']) ?></a><?php endforeach ?></div></details>
    <?php else: ?>
    <a class="mobile-menu__item" href="<?= htmlspecialcharsbx($section['url']) ?>"><?= htmlspecialcharsbx($section['name']) ?></a>
    <?php endif ?>
    <?php endforeach ?>
    <a class="mobile-menu__item" href="<?= SITE_DIR ?>catalog/?sort=new">Новинки</a>
    <?php if ($menuBrands): ?>
    <details><summary>Бренды</summary><div class="mobile-menu__sub"><?php foreach ($menuBrands as $brand): ?><a href="<?= htmlspecialcharsbx($brand['url']) ?>"><?= htmlspecialcharsbx($brand['name']) ?></a><?php endforeach ?></div></details>
    <?php endif ?>
    <div class="mobile-menu__links">
      <a href="<?= SITE_DIR ?>favorites/">Избранное</a>
      <a href="<?= SITE_DIR ?>info/#delivery">Доставка и оплата</a>
      <a href="<?= SITE_DIR ?>info/#return">Возврат и обмен</a>
      <a href="<?= SITE_DIR ?>info/#sizes">Таблица размеров</a>
      <a href="<?= SITE_DIR ?>info/#contacts">Контакты</a>
      <a href="<?= SITE_DIR ?>policy/">Политика конфиденциальности</a>
    </div>
    <div class="mobile-menu__store">
      <strong>Магазин в Арзамасе</strong>
      <?php if ($storeLine !== ''): ?><span class="muted"><?= htmlspecialcharsbx($storeLine) ?></span><?php endif ?>
      <?php if ($contacts['phone_href'] !== ''): ?><a class="btn btn--primary btn--block" href="<?= htmlspecialcharsbx($contacts['phone_href']) ?>">Позвонить</a><?php endif ?>
    </div>
  </nav>
