<?php
/**
 * Product type for the catalog filter. 1C puts different things into one group (ice skates group holds sticks, laces,
 * shorts, pucks), and gives no property for it, so the type is the first word of the product name:
 * "Клюшка хок. Fischer …" -> "Клюшка". Stored in the product property "Тип товара" (list, code TIP_TOVARA).
 * A word that fewer than 3 products start with goes to "Другое". Safe to run after every import (also called by
 * setup-1c-catalog.php):  docker compose exec -T php php /var/www/html/local/tools/fill-product-types.php [--dry-run]
 * Fragile by design: it depends on how 1C writes the names.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
while (ob_get_level()) {
    ob_end_flush();
}
\Bitrix\Main\Loader::includeModule('iblock');
global $DB;
$dry = in_array('--dry-run', $argv ?? [], true);
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$iblockId = (int)$store['catalog_iblock_id'];
const TYPE_CODE = 'TIP_TOVARA';

// Abbreviations 1C uses as the first word.
$aliases = ['наб.3-ой' => 'Набор защиты', 'наб.' => 'Набор', 'защита' => 'Защита', 'сумка-рюкзак' => 'Сумка', 'матрас-кровать' => 'Матрас-кровать'];

$names = [];
$rows = $DB->Query("select ID, NAME from b_iblock_element where IBLOCK_ID=$iblockId and ACTIVE='Y'");
while ($x = $rows->Fetch()) {
    $first = trim((string)preg_split('/[\s,]+/u', trim(html_entity_decode((string)$x['NAME'])), 2)[0], " .,:;()\"'");
    $key = mb_strtolower($first);
    $label = $aliases[$key] ?? ($first === '' || !preg_match('/\p{L}/u', $first) ? '' : mb_strtoupper(mb_substr($first, 0, 1)) . mb_strtolower(mb_substr($first, 1)));
    $names[(int)$x['ID']] = $label;
}
$count = array_count_values(array_filter($names));
$type = [];
foreach ($names as $id => $label) {
    $type[$id] = ($label !== '' && $count[$label] >= 3) ? $label : 'Другое';
}
$stat = array_count_values($type);
arsort($stat);
if ($dry) {
    echo "types: " . count($stat) . "\n";
    foreach (array_slice($stat, 0, 30, true) as $k => $n) {
        echo "  $k: $n\n";
    }
    exit;
}

$prop = $DB->Query("select ID from b_iblock_property where IBLOCK_ID=$iblockId and CODE='" . TYPE_CODE . "'")->Fetch();
$propId = $prop ? (int)$prop['ID'] : (int)(new \CIBlockProperty())->Add([
    'IBLOCK_ID' => $iblockId, 'NAME' => 'Тип товара', 'CODE' => TYPE_CODE, 'ACTIVE' => 'Y', 'SORT' => 80,
    'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'L', 'MULTIPLE' => 'N', 'XML_ID' => 'mir_sport_type_from_name',
]);
$enum = [];
$rows = $DB->Query("select ID, VALUE from b_iblock_property_enum where PROPERTY_ID=$propId");
while ($x = $rows->Fetch()) {
    $enum[$x['VALUE']] = (int)$x['ID'];
}
foreach (array_keys($stat) as $label) {
    if (!isset($enum[$label])) {
        $enum[$label] = (int)\CIBlockPropertyEnum::Add(['PROPERTY_ID' => $propId, 'VALUE' => $label, 'XML_ID' => md5($label), 'SORT' => $label === 'Другое' ? 9999 : 500]);
    }
}
$current = [];
$rows = $DB->Query("select IBLOCK_ELEMENT_ID id, VALUE_ENUM en from b_iblock_element_property where IBLOCK_PROPERTY_ID=$propId");
while ($x = $rows->Fetch()) {
    $current[(int)$x['id']] = (int)$x['en'];
}
$written = 0;
foreach ($type as $id => $label) {
    if (($current[$id] ?? 0) === $enum[$label]) {
        continue;
    }
    \CIBlockElement::SetPropertyValuesEx($id, $iblockId, [TYPE_CODE => $enum[$label]]);
    $written++;
}
\CIBlock::clearIblockTagCache($iblockId);
echo "product types: " . count($stat) . " values, written $written\n";
