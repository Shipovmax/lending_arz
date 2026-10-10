<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once __DIR__ . '/include/contacts.php';
require_once __DIR__ . '/include/page.php';
require_once __DIR__ . '/include/socials.php';
$contacts = \MirSport\contacts();
?>
<?php $APPLICATION->AddBufferContent('\MirSport\title_close') ?>
</main>

<footer class="footer">
  <div class="container footer__top">
    <div class="footer__brand">
      <a class="logo" href="<?= SITE_DIR ?>"><span class="logo__text">МИР СПОРТА</span><span class="logo__mark" aria-hidden="true"></span></a>
      <p class="footer__about">Спорттовары и товары для рыбалки. Магазин в Арзамасе и доставка по всей России.</p>
      <div class="footer__contacts">
        <?= \MirSport\link_or_text($contacts['phone'], $contacts['phone_href'], 'footer__phone') ?>
        <span><?= htmlspecialcharsbx($contacts['hours']) ?></span>
        <?= \MirSport\link_or_text($contacts['email'], $contacts['email_href'], 'link-line') ?>
      </div>
      <div class="socials">
        <?php foreach (\MirSport\socials() as $social): ?>
        <a href="<?= htmlspecialcharsbx($social['href']) ?>" target="_blank" rel="noopener" aria-label="<?= htmlspecialcharsbx($social['label']) ?>"><?php if ($social['icon'] !== ''): ?><svg class="icon icon--sm" aria-hidden="true"><use href="#<?= $social['icon'] ?>"/></svg><?php else: ?><?= htmlspecialcharsbx($social['text']) ?><?php endif ?></a>
        <?php endforeach ?>
      </div>
    </div>
    <details class="footer__col" open>
      <summary>Каталог</summary>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/?sort=new">Новинки</a>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/">Мужчинам</a>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/">Женщинам</a>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/">Подросткам</a>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/shoes/">Обувь</a>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/">Рыбалка</a>
      <a class="link-u" href="<?= SITE_DIR ?>catalog/">Скидки</a>
    </details>
    <details class="footer__col" open>
      <summary>Покупателям</summary>
      <a class="link-u" href="<?= SITE_DIR ?>info/#order">Как оформить заказ</a>
      <a class="link-u" href="<?= SITE_DIR ?>info/#delivery">Доставка</a>
      <a class="link-u" href="<?= SITE_DIR ?>info/#payment">Оплата</a>
      <a class="link-u" href="<?= SITE_DIR ?>info/#return">Возврат и обмен</a>
      <a class="link-u" href="<?= SITE_DIR ?>info/#sizes">Таблица размеров</a>
    </details>
    <details class="footer__col" open>
      <summary>Магазин</summary>
      <a class="link-u" href="<?= SITE_DIR ?>info/#contacts">Контакты и адрес</a>
      <a class="link-u" href="<?= SITE_DIR ?>info/#requisites">Реквизиты</a>
      <a class="link-u" href="<?= SITE_DIR ?>policy/">Политика конфиденциальности</a>
    </details>
  </div>
  <div class="container">
    <div class="footer__bar">
      <span>Принимаем к оплате:</span>
      <span class="payments"><span>МИР</span><span>Visa</span><span>Mastercard</span><span>СБП</span></span>
      <span>Доставка: СДЭК · Самовывоз в Арзамасе</span>
    </div>
    <div class="footer__bar footer__legal">
      <span>© Мир спорта, <?= date('Y') ?>. <?= htmlspecialcharsbx($contacts['legal']) ?>.</span>
      <span class="payments payments--links">
        <a class="link-u" href="<?= SITE_DIR ?>policy/">Политика обработки персональных данных</a>
        <a class="link-u" href="<?= SITE_DIR ?>info/#requisites">Публичная оферта</a>
        <a class="link-u" href="<?= SITE_DIR ?>policy/#cookie">Использование cookie</a>
      </span>
    </div>
  </div>
</footer>

<div class="cookie" role="region" aria-label="Уведомление о cookie" hidden data-cookie>
  <div class="container cookie__inner">
    <p class="cookie__text">Мы используем cookie: обязательные — чтобы работали корзина и оформление заказа, аналитические — чтобы улучшать сайт. Аналитические включаются только с вашего согласия. <a class="link-line" href="<?= SITE_DIR ?>policy/#cookie">Подробнее</a></p>
    <div class="cookie__actions">
      <button class="btn btn--sm" type="button" data-cookie-choice="necessary">Только обязательные</button>
      <button class="btn btn--sm btn--primary" type="button" data-cookie-choice="all">Принять все</button>
    </div>
  </div>
</div>
</body>
</html>
