<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Top-level sections as the "Направление" group of the prototype's filters (.dir-btn).
 *
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);
$current = (string)($arParams['MIR_CURRENT_CODE'] ?? '');
?>
<nav class="fgroup" aria-label="Разделы каталога">
  <span class="fgroup__title fgroup__title--line">Раздел</span>
  <a class="dir-btn" href="<?= htmlspecialcharsbx($arParams['MIR_ALL_URL']) ?>"<?= $current === '' ? ' aria-current="page"' : '' ?>>Все товары</a>
  <?php foreach ($arResult['SECTIONS'] as $section): ?>
  <a class="dir-btn" href="<?= htmlspecialcharsbx($section['SECTION_PAGE_URL']) ?>"<?= $section['CODE'] === $current ? ' aria-current="page"' : '' ?>><?= htmlspecialcharsbx(trim($section['NAME'])) ?><?php if (isset($section['ELEMENT_CNT'])): ?> <span><?= (int)$section['ELEMENT_CNT'] ?></span><?php endif ?></a>
  <?php endforeach ?>
</nav>
