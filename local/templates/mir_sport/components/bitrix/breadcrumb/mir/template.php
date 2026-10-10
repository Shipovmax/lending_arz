<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Breadcrumbs as in the prototype (ol.breadcrumbs). The component expects the template to return a string.
 *
 * @var array $arResult
 */
if (empty($arResult)) {
    return '';
}

$items = [];
$last = count($arResult) - 1;
foreach ($arResult as $i => $item) {
    $title = $i === 0 && $item['LINK'] === SITE_DIR ? 'Главная' : htmlspecialcharsbx(trim($item['TITLE']));
    if ($i === $last || $item['LINK'] === '') {
        $items[] = '<li aria-current="page">' . $title . '</li>';
    } else {
        $items[] = '<li><a class="link-u" href="' . htmlspecialcharsbx($item['LINK']) . '">' . $title . '</a></li>';
    }
}

return '<ol class="breadcrumbs">' . implode('', $items) . '</ol>';
