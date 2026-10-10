<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
require_once __DIR__ . '/contacts.php';
$contacts = \MirSport\contacts();
?>
<div class="megamenu" id="megamenu" hidden data-megamenu>
    <div class="container megamenu__inner">
      <div class="megamenu__dirs" role="tablist" aria-orientation="vertical" aria-label="Направления">
        <button class="megamenu__dir" type="button" role="tab" id="mm-tab-sport" tabindex="0" aria-selected="true" aria-controls="mm-sport" data-mm-dir="mm-sport">Спорттовары <span aria-hidden="true">→</span></button>
        <button class="megamenu__dir" type="button" role="tab" id="mm-tab-fish" tabindex="-1" aria-selected="false" aria-controls="mm-fish" data-mm-dir="mm-fish">Рыбалка <span aria-hidden="true">→</span></button>
        <button class="megamenu__dir" type="button" role="tab" id="mm-tab-brands" tabindex="-1" aria-selected="false" aria-controls="mm-brands" data-mm-dir="mm-brands">Бренды <span aria-hidden="true">→</span></button>
      </div>
      <div class="megamenu__panel" id="mm-sport" role="tabpanel" aria-labelledby="mm-tab-sport">
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Мужчинам</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Куртки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Футболки и поло</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Костюмы</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Брюки и шорты</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Термобельё</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Женщинам</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Куртки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Футболки и топы</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Костюмы</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Легинсы</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Термобельё</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Подросткам</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Куртки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Костюмы</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Футболки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Обувь</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/shoes/">Обувь</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/shoes/">Кроссовки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/shoes/">Кеды</a><a class="link-u" href="<?= SITE_DIR ?>catalog/shoes/">Ботинки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/shoes/">Сланцы</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/accessories/">Инвентарь</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/accessories/">Мячи</a><a class="link-u" href="<?= SITE_DIR ?>catalog/accessories/">Фитнес</a><a class="link-u" href="<?= SITE_DIR ?>catalog/accessories/">Ракетки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/accessories/">Защита</a></div>
      </div>
      <div class="megamenu__panel" id="mm-fish" role="tabpanel" aria-labelledby="mm-tab-fish" hidden>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Удилища</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Спиннинги</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Фидеры</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Поплавочные</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Зимние</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Катушки</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Безынерционные</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Мультипликаторные</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Зимние</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Приманки</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Воблеры</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Блёсны</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Силикон</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Мормышки</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Снасти</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Леска и шнуры</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Крючки</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Грузила</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Поплавки</a></div>
        <div class="megamenu__col"><h3><a href="<?= SITE_DIR ?>catalog/">Экипировка</a></h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">Одежда</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Обувь</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">Ящики и сумки</a></div>
      </div>
      <div class="megamenu__panel" id="mm-brands" role="tabpanel" aria-labelledby="mm-tab-brands" hidden>
        <div class="megamenu__col"><h3>Спорт</h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 1]</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 2]</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 3]</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 4]</a></div>
        <div class="megamenu__col"><h3>Рыбалка</h3><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 5]</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 6]</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 7]</a><a class="link-u" href="<?= SITE_DIR ?>catalog/">[Бренд 8]</a></div>
      </div>
    </div>
  </div>

  <nav class="mobile-menu" id="mobile-menu" aria-label="Меню" hidden data-mobile-menu>
    <details><summary>Мужчинам</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/">Куртки</a><a href="<?= SITE_DIR ?>catalog/">Футболки и поло</a><a href="<?= SITE_DIR ?>catalog/">Костюмы</a><a href="<?= SITE_DIR ?>catalog/">Брюки и шорты</a><a href="<?= SITE_DIR ?>catalog/">Термобельё</a></div></details>
    <details><summary>Женщинам</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/">Куртки</a><a href="<?= SITE_DIR ?>catalog/">Футболки и топы</a><a href="<?= SITE_DIR ?>catalog/">Костюмы</a><a href="<?= SITE_DIR ?>catalog/">Легинсы</a><a href="<?= SITE_DIR ?>catalog/">Термобельё</a></div></details>
    <details><summary>Подросткам</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/">Куртки</a><a href="<?= SITE_DIR ?>catalog/">Костюмы</a><a href="<?= SITE_DIR ?>catalog/">Футболки</a><a href="<?= SITE_DIR ?>catalog/">Обувь</a></div></details>
    <details><summary>Обувь</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/shoes/">Кроссовки</a><a href="<?= SITE_DIR ?>catalog/shoes/">Кеды</a><a href="<?= SITE_DIR ?>catalog/shoes/">Ботинки</a><a href="<?= SITE_DIR ?>catalog/shoes/">Сланцы</a></div></details>
    <details><summary>Инвентарь</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/accessories/">Мячи</a><a href="<?= SITE_DIR ?>catalog/accessories/">Фитнес</a><a href="<?= SITE_DIR ?>catalog/accessories/">Ракетки</a><a href="<?= SITE_DIR ?>catalog/accessories/">Защита</a></div></details>
    <details><summary>Рыбалка</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/">Удилища</a><a href="<?= SITE_DIR ?>catalog/">Катушки</a><a href="<?= SITE_DIR ?>catalog/">Приманки</a><a href="<?= SITE_DIR ?>catalog/">Снасти</a><a href="<?= SITE_DIR ?>catalog/">Экипировка</a></div></details>
    <details><summary>Бренды</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/">[Бренд 1]</a><a href="<?= SITE_DIR ?>catalog/">[Бренд 2]</a><a href="<?= SITE_DIR ?>catalog/">[Бренд 3]</a></div></details>
    <details><summary class="mobile-menu__sale">Скидки</summary><div class="mobile-menu__sub"><a href="<?= SITE_DIR ?>catalog/">Все товары со скидкой</a></div></details>
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
      <span class="muted"><?= htmlspecialcharsbx($contacts['address']) ?> · <?= htmlspecialcharsbx($contacts['hours']) ?></span>
      <a class="btn btn--primary btn--block" href="<?= htmlspecialcharsbx($contacts['phone_href']) ?>">Позвонить</a>
    </div>
  </nav>
