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

// Which properties are sizes and colors is configured in local/config/store.php.
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$isSize = static fn(array $item): bool => in_array((string)$item['CODE'], $store['size_props'], true);
$isColor = static fn(array $item): bool => in_array((string)$item['CODE'], $store['color_props'], true);

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
$rank = static function (array $item) use ($isSize, $isColor): int {
    if (!empty($item['PRICE'])) {
        return 0;
    }
    if ($isSize($item)) {
        return 1;
    }
    return $isColor($item) ? 2 : 3;
};
// One "Размер" block: the sizes come from several properties (1C's own and the one taken from names).
$mergedSize = null;
foreach ($items as $k => $item) {
    if (empty($item['PRICE']) && $isSize($item)) {
        if ($mergedSize === null) {
            $mergedSize = $k;
        } else {
            $items[$mergedSize]['VALUES'] = array_merge($items[$mergedSize]['VALUES'], $item['VALUES']);
            unset($items[$k]);
        }
    }
}
if ($mergedSize !== null && count(array_filter($items, static fn($i) => empty($i['PRICE']) && $isSize($i))) === 1) {
    $items[$mergedSize]['NAME'] = 'Размер';
}
usort($items, static fn(array $a, array $b): int => $rank($a) <=> $rank($b));
// Long lists are folded: the first values stay visible, the rest opens with "Показать ещё".
$visibleLimit = 8;
$isActive = false;
foreach ($arResult['ITEMS'] as $item) {
    foreach ($item['VALUES'] as $value) {
        if (!empty($value['CHECKED']) || (isset($value['HTML_VALUE']) && $value['HTML_VALUE'] !== '' && !empty($item['PRICE']))) {
            $isActive = true;
        }
    }
}
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
  <?php elseif ($isSize($item)):
      require_once $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/format.php';
      $labels = \MirSport\size_labels(array_map(static fn($v) => htmlspecialchars_decode((string)$v['VALUE']), $item['VALUES']));
      usort($item['VALUES'], static fn($a, $b) => strnatcasecmp($labels[htmlspecialchars_decode((string)$a['VALUE'])] ?? '', $labels[htmlspecialchars_decode((string)$b['VALUE'])] ?? '')); ?>
  <fieldset class="fgroup">
    <legend><?= htmlspecialcharsbx(htmlspecialchars_decode((string)$item['NAME'])) ?></legend>
    <?php $folded = count($item['VALUES']) > $visibleLimit && !array_filter($item['VALUES'], static fn($v) => !empty($v['CHECKED'])); ?>
    <div class="size-grid">
      <?php foreach ($item['VALUES'] as $n => $value): ?>
      <label class="pill"<?= $folded && $n >= $visibleLimit ? ' hidden data-more' : '' ?>>
        <input class="visually-hidden" type="checkbox" name="<?= htmlspecialcharsbx($value['CONTROL_NAME']) ?>" value="<?= htmlspecialcharsbx($value['HTML_VALUE']) ?>"<?= !empty($value['CHECKED']) ? ' checked' : '' ?><?= !empty($value['DISABLED']) && empty($value['CHECKED']) ? ' disabled' : '' ?>><?php $raw = htmlspecialchars_decode((string)$value['VALUE']); ?><?= htmlspecialcharsbx($labels[$raw] ?? $raw) ?>
      </label>
      <?php endforeach ?>
    </div>
    <?php if ($folded): ?><button class="more-btn" type="button" data-more-toggle>Показать ещё <?= count($item['VALUES']) - $visibleLimit ?></button><?php endif ?>
  </fieldset>
  <?php else: ?>
  <fieldset class="fgroup">
    <legend><?= htmlspecialcharsbx(htmlspecialchars_decode((string)$item['NAME'])) ?></legend>
    <?php $folded = count($item['VALUES']) > $visibleLimit && !array_filter($item['VALUES'], static fn($v) => !empty($v['CHECKED'])); ?>
    <?php foreach (array_values($item['VALUES']) as $n => $value):
        $disabled = !empty($value['DISABLED']) && empty($value['CHECKED']);
        $file = $value['FILE']['SRC'] ?? ''; ?>
    <label class="check"<?= $folded && $n >= $visibleLimit ? ' hidden data-more' : '' ?>>
      <input type="checkbox" name="<?= htmlspecialcharsbx($value['CONTROL_NAME']) ?>" value="<?= htmlspecialcharsbx($value['HTML_VALUE']) ?>"<?= !empty($value['CHECKED']) ? ' checked' : '' ?><?= $disabled ? ' disabled' : '' ?>>
      <?php if ($file !== ''): ?><span class="swatch-dot" style="background-image: url('<?= htmlspecialcharsbx($file) ?>')" aria-hidden="true"></span><?php endif ?>
      <?= htmlspecialcharsbx(htmlspecialchars_decode((string)$value['VALUE'])) ?>
      <?php if (isset($value['ELEMENT_COUNT'])): ?><span class="check__count"><?= (int)$value['ELEMENT_COUNT'] ?></span><?php endif ?>
    </label>
    <?php endforeach ?>
    <?php if ($folded): ?><button class="more-btn" type="button" data-more-toggle>Показать ещё <?= count($item['VALUES']) - $visibleLimit ?></button><?php endif ?>
  </fieldset>
  <?php endif ?>
  <?php endforeach ?>

  <noscript><button class="btn btn--sm btn--primary" type="submit">Показать</button></noscript>
  <?php if ($isActive): ?>
  <a class="btn btn--sm" href="<?= htmlspecialcharsbx($arParams['MIR_RESET_URL']) ?>">Сбросить фильтры</a>
  <?php endif ?>
</form>
