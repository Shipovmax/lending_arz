<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/urlrewrite.php';

CHTTP::SetStatus('404 Not Found');
@define('ERROR_404', 'Y');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @global CMain $APPLICATION */
$APPLICATION->SetTitle('Страница не найдена');
// The page prints its own h1 (from the prototype's 404.html).
$APPLICATION->SetPageProperty('hide_h1', 'Y');
?>
<div class="container not-found">
  <div class="section" style="flex: 1 1 420px">
    <span class="not-found__code" aria-hidden="true">404</span>
    <h1 class="page-title">Такой страницы нет</h1>
    <p class="muted" style="max-width: 460px">Возможно, товар закончился и снят с продажи или в ссылке опечатка. Воспользуйтесь поиском в шапке или начните с разделов ниже.</p>
    <div class="hero__actions">
      <a class="btn btn--primary" href="<?= SITE_DIR ?>">На главную</a>
      <a class="btn" href="<?= SITE_DIR ?>catalog/">В каталог</a>
    </div>
  </div>
  <nav class="side-nav" style="flex: 1 1 360px" aria-label="Популярные разделы">
    <span class="side-nav__title">Популярные разделы</span>
    <a href="<?= SITE_DIR ?>catalog/shoes/">Обувь <span aria-hidden="true">→</span></a>
    <a href="<?= SITE_DIR ?>catalog/sportswear/">Спортивная одежда <span aria-hidden="true">→</span></a>
    <a href="<?= SITE_DIR ?>catalog/t-shirts/">Футболки <span aria-hidden="true">→</span></a>
    <a href="<?= SITE_DIR ?>catalog/pants/">Штаны <span aria-hidden="true">→</span></a>
    <a href="<?= SITE_DIR ?>catalog/accessories/">Аксессуары <span aria-hidden="true">→</span></a>
    <a href="<?= SITE_DIR ?>catalog/">Весь каталог <span aria-hidden="true">→</span></a>
  </nav>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
