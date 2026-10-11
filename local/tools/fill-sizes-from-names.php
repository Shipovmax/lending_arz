<?php
/**
 * Workaround for offers whose size 1C does not send as a property: the size is only in the offer name,
 * for example "Коньки … (р.8,полнота R)", "… (34-37 цв.черно-белый)", "… (рост 130/140)",
 * "Клюшка … (загиб 92L, жесткость 60)". The size is cut out of the name and stored in an offer property
 * "Размер" (list, code RAZMER_IZ_NAZVANIYA), so the product page, filter and basket use it like any other size.
 *
 * Only offers without a size from 1C are touched; the script is safe to run again after every import:
 *   docker compose exec -T php php /var/www/html/local/tools/fill-sizes-from-names.php
 * It is fragile by design: if 1C changes how the names are written, the size is not recognised (the offer then
 * simply has no size). Run `... --dry-run` to see the result without writing.
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

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
global $DB;
$dry = in_array('--dry-run', $argv ?? [], true);
const SIZE_CODE = 'RAZMER_IZ_NAZVANIYA';

/** Size text from the part of the offer name that follows the product name, or '' when there is none. */
function size_from_suffix(string $suffix): string
{
    $suffix = trim($suffix);
    if ($suffix === '' || $suffix[0] !== '(') {
        return '';
    }
    $inner = trim($suffix, "() \t");
    $inner = preg_replace('/[\s,]*цвет?\b.*$/iu', '', $inner);   // "34-37 цв.черно-белый" -> "34-37"
    $inner = preg_replace('/\s*цв\..*$/iu', '', $inner);
    // The first comma that is not a decimal comma ends the size: "р.8,полнота R" -> "р.8", "р.8,5" stays.
    if (preg_match('/^(.*?\d),(?!\d)/u', $inner, $m) || preg_match('/^([^,]*?),(?!\d)/u', $inner, $m)) {
        $head = $m[1];
        // Stick bend and stiffness belong together: "загиб 92L, жесткость 60".
        $inner = preg_match('/^загиб/iu', $inner) ? $inner : $head;
    }
    $inner = trim($inner, " ,;");
    if ($inner === '') {
        return '';
    }
    $inner = preg_replace('/^р\.?\s*/iu', '', $inner);            // "р.8" -> "8"
    $inner = preg_replace('/^рама\s+/iu', '', $inner);            // frame size words
    $inner = preg_replace('/\s+/u', ' ', $inner);
    // A size must carry a digit or be a clothes letter size; anything else is a color or a note, not a size.
    if (!preg_match('/\d/u', $inner) && !preg_match('/^(XXS|XS|S|M|L|XL|XXL|XXXL|[LRМЛ]|[SMLX]{1,3}\/?[SMLX]{0,3})$/iu', $inner)) {
        return '';
    }
    return mb_strlen($inner) > 40 ? '' : $inner;
}

$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$sku = \CCatalogSku::GetInfoByProductIBlock((int)$store['catalog_iblock_id']);
if (!$sku) {
    exit("no offers iblock\n");
}
$offersId = (int)$sku['IBLOCK_ID'];
$linkId = (int)$sku['SKU_PROPERTY_ID'];

// Property "Размер" for offers; created once.
$prop = $DB->Query("select ID from b_iblock_property where IBLOCK_ID=$offersId and CODE='" . SIZE_CODE . "'")->Fetch();
$propId = $prop ? (int)$prop['ID'] : 0;
if (!$propId && !$dry) {
    $propId = (int)(new \CIBlockProperty())->Add([
        'IBLOCK_ID' => $offersId,
        'NAME' => 'Размер',
        'CODE' => SIZE_CODE,
        'ACTIVE' => 'Y',
        'SORT' => 90,
        'PROPERTY_TYPE' => 'L',
        'LIST_TYPE' => 'L',
        'MULTIPLE' => 'N',
        'XML_ID' => 'mir_sport_size_from_name',
    ]);
    echo "property created: " . SIZE_CODE . " (#$propId)\n";
}

// Size properties that 1C fills itself; offers that have any of them are left alone.
$own = [];
$rows = $DB->Query("select ID from b_iblock_property where IBLOCK_ID=$offersId and CODE in ('" . implode("','", array_map([$DB, 'ForSql'], array_diff($store['size_props'], [SIZE_CODE]))) . "')");
while ($x = $rows->Fetch()) {
    $own[] = (int)$x['ID'];
}
$ownList = $own ? implode(',', $own) : '0';

$enum = [];
if ($propId) {
    $rows = $DB->Query("select ID, VALUE from b_iblock_property_enum where PROPERTY_ID=$propId");
    while ($x = $rows->Fetch()) {
        $enum[$x['VALUE']] = (int)$x['ID'];
    }
}

$rows = $DB->Query("select e.ID, e.NAME, p.NAME pname,
    (select count(*) from b_iblock_element_property z where z.IBLOCK_ELEMENT_ID=e.ID and z.IBLOCK_PROPERTY_ID in ($ownList) and z.VALUE<>'') has_own
    from b_iblock_element e
    join b_iblock_element_property ep on ep.IBLOCK_ELEMENT_ID=e.ID and ep.IBLOCK_PROPERTY_ID=$linkId
    join b_iblock_element p on p.ID=ep.VALUE_NUM
    where e.IBLOCK_ID=$offersId");
$stat = ['offers' => 0, 'own size' => 0, 'parsed' => 0, 'no size' => 0, 'written' => 0];
$values = [];
$todo = [];
while ($x = $rows->Fetch()) {
    $stat['offers']++;
    if ((int)$x['has_own'] > 0) {
        $stat['own size']++;
        continue;
    }
    $size = size_from_suffix(mb_strlen($x['NAME']) > mb_strlen($x['pname']) && str_starts_with($x['NAME'], $x['pname'])
        ? mb_substr($x['NAME'], mb_strlen($x['pname'])) : '');
    if ($size === '') {
        $stat['no size']++;
        $todo[(int)$x['ID']] = '';
        continue;
    }
    $stat['parsed']++;
    $values[$size] = ($values[$size] ?? 0) + 1;
    $todo[(int)$x['ID']] = $size;
}

if ($dry) {
    arsort($values);
    echo "dry run: " . json_encode($stat, JSON_UNESCAPED_UNICODE) . "\nmost common sizes: ";
    foreach (array_slice($values, 0, 25, true) as $v => $n) {
        echo "$v ($n); ";
    }
    echo "\n";
    exit;
}

foreach (array_keys($values) as $value) {
    if (!isset($enum[$value])) {
        $enum[$value] = (int)\CIBlockPropertyEnum::Add(['PROPERTY_ID' => $propId, 'VALUE' => $value, 'XML_ID' => md5($value)]);
    }
}
$current = [];
$rows = $DB->Query("select IBLOCK_ELEMENT_ID id, VALUE_ENUM en from b_iblock_element_property where IBLOCK_PROPERTY_ID=$propId");
while ($x = $rows->Fetch()) {
    $current[(int)$x['id']] = (int)$x['en'];
}
foreach ($todo as $id => $size) {
    $want = $size === '' ? 0 : $enum[$size];
    if (($current[$id] ?? 0) === $want) {
        continue;
    }
    \CIBlockElement::SetPropertyValuesEx($id, $offersId, [SIZE_CODE => $want > 0 ? $want : false]);
    $stat['written']++;
}
echo json_encode($stat, JSON_UNESCAPED_UNICODE) . "\n";
\CIBlock::clearIblockTagCache($offersId);
echo "done; run setup-1c-catalog.php next (filter, offer selection, index)\n";
