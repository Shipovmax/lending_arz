<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Pagination as in the prototype (ol.pagination).
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);

if (!$arResult['NavShowAlways'] && (int)$arResult['NavPageCount'] <= 1) {
    return;
}

$query = $arResult['NavQueryString'] !== '' ? $arResult['NavQueryString'] . '&amp;' : '';
$path = htmlspecialcharsbx($arResult['sUrlPath']);
$base = $path . '?' . $query;
$num = (int)$arResult['NavNum'];
$current = (int)$arResult['NavPageNomer'];
$count = (int)$arResult['NavPageCount'];

$link = static function (int $page) use ($base, $path, $num, $arResult): string {
    if ($page === 1) {
        return $path . ($arResult['NavQueryString'] !== '' ? '?' . $arResult['NavQueryString'] : '');
    }
    return $base . 'PAGEN_' . $num . '=' . $page;
};
?>
<nav aria-label="Страницы">
  <ol class="pagination">
    <?php if ((int)$arResult['nStartPage'] > 1): ?>
    <li><a href="<?= $link(1) ?>">1</a></li>
    <?php if ((int)$arResult['nStartPage'] > 2): ?><li><span>…</span></li><?php endif ?>
    <?php endif ?>
    <?php for ($page = (int)$arResult['nStartPage']; $page <= (int)$arResult['nEndPage']; $page++): ?>
    <li><a href="<?= $link($page) ?>"<?= $page === $current ? ' aria-current="page"' : '' ?>><?= $page ?></a></li>
    <?php endfor ?>
    <?php if ((int)$arResult['nEndPage'] < $count): ?>
    <?php if ((int)$arResult['nEndPage'] < $count - 1): ?><li><span>…</span></li><?php endif ?>
    <li><a href="<?= $link($count) ?>"><?= $count ?></a></li>
    <?php endif ?>
  </ol>
</nav>
