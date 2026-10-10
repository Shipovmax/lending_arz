<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Smart filter in the prototype's markup (.fgroup, .check, .size-grid .pill, .price-range).
 * A plain GET form; script.js submits it on every change.
 *
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);

$items = [];
foreach ($arResult['ITEMS'] as $item) {
    if (!empty($item['PRICE'])) {
        if (isset($item['VALUES']['MIN']['VALUE'], $item['VALUES']['MAX']['VALUE']) && $item['VALUES']['MAX']['VALUE'] > $item['VALUES']['MIN']['VALUE']) {
            $items[] = $item;
        }
        continue;
    }
    if (!empty($item['VALUES'])) {
        $items[] = $item;
    }
}
// Order as in the prototype: price, size, color, then the rest.
$rank = static function (array $item): int {
    if (!empty($item['PRICE'])) {
        return 0;
    }
    if (str_starts_with((string)$item['CODE'], 'SIZES_')) {
        return 1;
    }
    return $item['CODE'] === 'COLOR_REF' ? 2 : 3;
};
usort($items, static fn(array $a, array $b): int => $rank($a) <=> $rank($b));
$isActive = false;
foreach ($arResult['ITEMS'] as $item) {
    foreach ($item['VALUES'] as $value) {
        if (!empty($value['CHECKED']) || (isset($value['HTML_VALUE']) && $value['HTML_VALUE'] !== '' && !empty($item['PRICE']))) {
            $isActive = true;
        }
    }
}
$isSize = static fn(array $item): bool => str_starts_with((string)$item['CODE'], 'SIZES_');
?>
<form name="<?= htmlspecialcharsbx($arResult['FILTER_NAME']) ?>_form" action="<?= htmlspecialcharsbx($arParams['MIR_RESET_URL']) ?>" method="get" data-smart-filter>
  <?php foreach (($arParams['MIR_KEEP'] ?? []) as $name => $value): if ($value === null) { continue; } ?>
  <input type="hidden" name="<?= htmlspecialcharsbx($name) ?>" value="<?= htmlspecialcharsbx($value) ?>">
  <?php endforeach ?>
  <input type="hidden" name="set_filter" value="Y">

  <?php foreach ($items as $item): ?>
  <?php if (!empty($item['PRICE'])):
      $min = $item['VALUES']['MIN'];
      $max = $item['VALUES']['MAX']; ?>
  <fieldset class="fgroup">
    <legend>Цена, ₽</legend>
    <div class="price-range">
      <input class="input" type="number" inputmode="numeric" name="<?= htmlspecialcharsbx($min['CONTROL_NAME']) ?>" value="<?= htmlspecialcharsbx($min['HTML_VALUE']) ?>" aria-label="Цена от" placeholder="от <?= (int)floor($min['VALUE']) ?>">
      <input class="input" type="number" inputmode="numeric" name="<?= htmlspecialcharsbx($max['CONTROL_NAME']) ?>" value="<?= htmlspecialcharsbx($max['HTML_VALUE']) ?>" aria-label="Цена до" placeholder="до <?= (int)ceil($max['VALUE']) ?>">
    </div>
  </fieldset>
  <?php elseif ($isSize($item)): ?>
  <fieldset class="fgroup">
    <legend><?= htmlspecialcharsbx(htmlspecialchars_decode((string)$item['NAME'])) ?></legend>
    <div class="size-grid">
      <?php foreach ($item['VALUES'] as $value): ?>
      <label class="pill">
        <input class="visually-hidden" type="checkbox" name="<?= htmlspecialcharsbx($value['CONTROL_NAME']) ?>" value="<?= htmlspecialcharsbx($value['HTML_VALUE']) ?>"<?= !empty($value['CHECKED']) ? ' checked' : '' ?><?= !empty($value['DISABLED']) && empty($value['CHECKED']) ? ' disabled' : '' ?>><?= htmlspecialcharsbx(htmlspecialchars_decode((string)$value['VALUE'])) ?>
      </label>
      <?php endforeach ?>
    </div>
  </fieldset>
  <?php else: ?>
  <fieldset class="fgroup">
    <legend><?= htmlspecialcharsbx(htmlspecialchars_decode((string)$item['NAME'])) ?></legend>
    <?php foreach ($item['VALUES'] as $value):
        $disabled = !empty($value['DISABLED']) && empty($value['CHECKED']);
        $file = $value['FILE']['SRC'] ?? ''; ?>
    <label class="check">
      <input type="checkbox" name="<?= htmlspecialcharsbx($value['CONTROL_NAME']) ?>" value="<?= htmlspecialcharsbx($value['HTML_VALUE']) ?>"<?= !empty($value['CHECKED']) ? ' checked' : '' ?><?= $disabled ? ' disabled' : '' ?>>
      <?php if ($file !== ''): ?><span class="swatch-dot" style="background-image: url('<?= htmlspecialcharsbx($file) ?>')" aria-hidden="true"></span><?php endif ?>
      <?= htmlspecialcharsbx(htmlspecialchars_decode((string)$value['VALUE'])) ?>
      <?php if (isset($value['ELEMENT_COUNT'])): ?><span class="check__count"><?= (int)$value['ELEMENT_COUNT'] ?></span><?php endif ?>
    </label>
    <?php endforeach ?>
  </fieldset>
  <?php endif ?>
  <?php endforeach ?>

  <noscript><button class="btn btn--sm btn--primary" type="submit">Показать</button></noscript>
  <?php if ($isActive): ?>
  <a class="btn btn--sm" href="<?= htmlspecialcharsbx($arParams['MIR_RESET_URL']) ?>">Сбросить фильтры</a>
  <?php endif ?>
</form>
