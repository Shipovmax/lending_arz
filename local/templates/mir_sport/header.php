<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

require_once __DIR__ . '/include/contacts.php';
require_once __DIR__ . '/include/page.php';

$asset = Asset::getInstance();
$asset->addCss(SITE_TEMPLATE_PATH . '/css/fonts.css');
$asset->addCss(SITE_TEMPLATE_PATH . '/css/tokens.css');
$asset->addCss(SITE_TEMPLATE_PATH . '/css/main.css');
$asset->addCss(SITE_TEMPLATE_PATH . '/css/bitrix.css');
$asset->addJs(SITE_TEMPLATE_PATH . '/js/shell.js');

$contacts = \MirSport\contacts();
$searchQuery = (string)\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('q');
?><!doctype html>
<html lang="<?= LANGUAGE_ID ?>">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="<?= SITE_TEMPLATE_PATH ?>/images/favicon.svg" type="image/svg+xml">
<title><?php $APPLICATION->AddBufferContent('\MirSport\browser_title') ?></title>
<?php $APPLICATION->ShowHead() ?>
</head>
<body>
<?php $APPLICATION->ShowPanel() ?>
<?php include __DIR__ . '/include/sprite.php' ?>

<a class="skip-link" href="#main">Перейти к содержимому</a>

<header class="header" data-header>
  <div class="topbar">
    <div class="container topbar__inner">
      <span class="topbar__place"><svg class="icon icon--sm"><use href="#i-pin"/></svg>Арзамас, <?= htmlspecialcharsbx($contacts['address']) ?> · <?= htmlspecialcharsbx($contacts['hours']) ?></span>
      <nav class="topbar__links" aria-label="Информация">
        <a class="link-u" href="<?= SITE_DIR ?>info/#delivery">Доставка и оплата</a>
        <a class="link-u" href="<?= SITE_DIR ?>info/#return">Возврат и обмен</a>
        <a class="link-u" href="<?= SITE_DIR ?>info/#contacts">Контакты</a>
        <?= \MirSport\link_or_text($contacts['phone'], $contacts['phone_href'], 'topbar__phone') ?>
      </nav>
    </div>
  </div>

  <div class="container header__row">
    <button class="icon-btn menu-btn" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Открыть меню" data-menu-toggle>
      <svg class="icon"><use href="#i-menu"/></svg>
    </button>

    <a class="logo" href="<?= SITE_DIR ?>" aria-label="Мир спорта, на главную">
      <span class="logo__text">МИР СПОРТА</span>
      <span class="logo__mark" aria-hidden="true"></span>
    </a>

    <button class="catalog-btn" type="button" aria-expanded="false" aria-controls="megamenu" data-megamenu-toggle>
      <svg class="icon icon--sm"><use href="#i-menu"/></svg>Каталог
    </button>

    <?php /* TODO: replace with bitrix:search.title once the catalog is indexed; it will fill [data-suggest]. */ ?>
    <div class="search" data-search>
      <form class="search__form" role="search" action="<?= SITE_DIR ?>search/" method="get">
        <label class="search__field">
          <svg class="icon icon--sm"><use href="#i-search"/></svg>
          <span class="visually-hidden">Поиск по каталогу</span>
          <input class="search__input" type="search" name="q" autocomplete="off" placeholder="Кроссовки, спиннинг или артикул" value="<?= htmlspecialcharsbx($searchQuery) ?>" data-search-input>
        </label>
        <button class="search__clear" type="button" aria-label="Очистить поиск"<?= $searchQuery === '' ? ' hidden' : '' ?> data-search-clear>×</button>
        <button class="search__submit" type="submit">Найти</button>
        <button class="search__cancel" type="button" data-search-close>Отмена</button>
      </form>
      <div class="suggest" id="suggest" hidden data-suggest></div>
    </div>

    <nav class="header-icons" aria-label="Личное">
      <a class="header-icon header-icon--store" href="<?= SITE_DIR ?>info/#contacts"><svg class="icon"><use href="#i-pin"/></svg><span class="header-icon__label">Магазин</span></a>
      <a class="header-icon header-icon--fav" href="<?= SITE_DIR ?>favorites/"><svg class="icon"><use href="#i-heart"/></svg><span class="header-icon__label">Избранное</span><span class="badge" data-fav-count hidden>0</span></a>
      <?php
      $APPLICATION->IncludeComponent(
          'bitrix:sale.basket.basket.line',
          'mir_icon',
          [
              'PATH_TO_BASKET' => SITE_DIR . 'personal/cart/',
              'PATH_TO_ORDER' => SITE_DIR . 'personal/order/make/',
              'SHOW_NUM_PRODUCTS' => 'Y',
              'SHOW_EMPTY_VALUES' => 'Y',
              'SHOW_TOTAL_PRICE' => 'N',
              'SHOW_PERSONAL_LINK' => 'N',
              'SHOW_AUTHOR' => 'N',
              'SHOW_REGISTRATION' => 'N',
              'SHOW_PRODUCTS' => 'N',
              'POSITION_FIXED' => 'N',
              'HIDE_ON_BASKET_PAGES' => 'N',
          ],
          false
      );
      ?>
    </nav>
  </div>

  <?php /* TODO: build the nav, mega menu and mobile menu from catalog sections once the section tree is agreed. Static markup follows the prototype.
     Items without a matching demo section lead to /catalog/ for now; the prototype's ?dir=fish and ?sale=1 map to catalog sections and the smart filter, and aria-current="page" is set once the targets differ. */ ?>
  <nav class="nav" aria-label="Разделы каталога">
    <ul class="container nav__list">
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/?sort=new"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/?sort=new') ?> data-nav="new">Новинки</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/') ?> data-nav="men">Мужчинам</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/') ?> data-nav="women">Женщинам</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/') ?> data-nav="teen">Подросткам</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/shoes/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/shoes/') ?> data-nav="shoes">Обувь</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/accessories/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/accessories/') ?> data-nav="gear">Инвентарь</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/') ?> data-nav="fish">Рыбалка</a></li>
      <li><a class="nav__link" href="<?= SITE_DIR ?>catalog/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/') ?> data-nav="brands">Бренды</a></li>
      <li><a class="nav__link nav__link--sale" href="<?= SITE_DIR ?>catalog/"<?= \MirSport\nav_current('' . SITE_DIR . 'catalog/') ?> data-nav="sale">Скидки</a></li>
    </ul>
  </nav>

  <?php include __DIR__ . '/include/menus.php' ?>
</header>

<main id="main" tabindex="-1">
<?php $APPLICATION->AddBufferContent('\MirSport\title_open') ?>
