<?php
/**
 * Read-only report on the catalog that 1C imports. Run on the server:
 *   docker compose exec -T php php /var/www/html/local/tools/catalog-report.php [hours]
 * Shows sections with product counts, what changed in the last [hours] (default 3), prices, stock,
 * photo sizes and offer properties that look like sizes or colors but are not configured yet.
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
\Bitrix\Main\Loader::includeModule('catalog');
global $DB;
$hours = max(1, (int)($argv[1] ?? 3));
$store = require $_SERVER['DOCUMENT_ROOT'] . '/local/config/store.php';
$p = (int)$store['catalog_iblock_id'];
$sku = \CCatalogSku::GetInfoByProductIBlock($p);
$o = $sku ? (int)$sku['IBLOCK_ID'] : 0;
$one = static fn(string $sql) => array_values($DB->Query($sql)->Fetch() ?: [null])[0];

echo "Каталог: инфоблок товаров $p, предложений $o\n";
echo "Товаров: " . $one("select count(*) from b_iblock_element where IBLOCK_ID=$p") . ", предложений: " . $one("select count(*) from b_iblock_element where IBLOCK_ID=$o") . "\n";
echo "Последнее изменение: " . $one("select max(TIMESTAMP_X) from b_iblock_element where IBLOCK_ID in ($p,$o)") . "\n";
echo "За $hours ч: новых товаров " . $one("select count(*) from b_iblock_element where IBLOCK_ID=$p and DATE_CREATE > now() - interval $hours hour")
    . ", новых предложений " . $one("select count(*) from b_iblock_element where IBLOCK_ID=$o and DATE_CREATE > now() - interval $hours hour")
    . ", изменено товаров " . $one("select count(*) from b_iblock_element where IBLOCK_ID=$p and TIMESTAMP_X > now() - interval $hours hour") . "\n";

echo "\nРазделы (товаров / в наличии):\n";
$r = $DB->Query("select s.ID, s.NAME, s.DEPTH_LEVEL, s.DATE_CREATE,
    (select count(distinct se.IBLOCK_ELEMENT_ID) from b_iblock_section_element se join b_iblock_section c on c.ID=se.IBLOCK_SECTION_ID where c.IBLOCK_ID=$p and c.LEFT_MARGIN>=s.LEFT_MARGIN and c.RIGHT_MARGIN<=s.RIGHT_MARGIN) n,
    (select count(distinct se.IBLOCK_ELEMENT_ID) from b_iblock_section_element se join b_iblock_section c on c.ID=se.IBLOCK_SECTION_ID join b_catalog_product cp on cp.ID=se.IBLOCK_ELEMENT_ID where c.IBLOCK_ID=$p and c.LEFT_MARGIN>=s.LEFT_MARGIN and c.RIGHT_MARGIN<=s.RIGHT_MARGIN and cp.AVAILABLE='Y') av
    from b_iblock_section s where s.IBLOCK_ID=$p order by s.LEFT_MARGIN");
while ($x = $r->Fetch()) {
    echo str_repeat('  ', (int)$x['DEPTH_LEVEL']) . "{$x['NAME']}: {$x['n']} / {$x['av']}" . ((int)$x['n'] === 0 ? '  (пустой, на сайте скрыт)' : '') . "\n";
}

echo "\nЦены: " . $one("select count(*) from b_catalog_price") . ", тип цены: " . $one("select NAME from b_catalog_group where BASE='Y'")
    . " (на сайте: {$store['price_code']})\n";
echo "Предложений в наличии: " . $one("select count(*) from b_catalog_product cp join b_iblock_element e on e.ID=cp.ID where e.IBLOCK_ID=$o and cp.QUANTITY>0")
    . ", товаров в наличии: " . $one("select count(*) from b_catalog_product cp join b_iblock_element e on e.ID=cp.ID where e.IBLOCK_ID=$p and cp.AVAILABLE='Y'") . "\n";
echo "Складов: " . $one("select count(*) from b_catalog_store") . "\n";

echo "\nФото (детальные, по ширине): ";
$r = $DB->Query("select case when f.WIDTH<=300 then 'до 300' when f.WIDTH<1200 then '301-1199' else '1200+' end w, count(*) n from b_iblock_element e join b_file f on f.ID=e.DETAIL_PICTURE where e.IBLOCK_ID in ($p,$o) group by w");
while ($x = $r->Fetch()) {
    echo "{$x['w']}: {$x['n']}; ";
}
echo "\nТоваров без фото: " . $one("select count(*) from b_iblock_element where IBLOCK_ID=$p and coalesce(DETAIL_PICTURE,0)=0 and coalesce(PREVIEW_PICTURE,0)=0") . "\n";

echo "\nСвойства предложений с данными:\n";
$configured = array_merge($store['size_props'], $store['color_props']);
$r = $DB->Query("select pr.CODE, pr.NAME, pr.PROPERTY_TYPE, count(distinct ep.IBLOCK_ELEMENT_ID) n from b_iblock_property pr join b_iblock_element_property ep on ep.IBLOCK_PROPERTY_ID=pr.ID where pr.IBLOCK_ID=$o and pr.CODE not like 'CML2\\_%' group by pr.ID order by n desc");
while ($x = $r->Fetch()) {
    $role = in_array($x['CODE'], $store['size_props'], true) ? 'размер' : (in_array($x['CODE'], $store['color_props'], true) ? 'цвет' : '');
    $hint = $role === '' && preg_match('/^(размер|цвет)/iu', $x['NAME']) ? '  НЕ НАСТРОЕНО (setup-1c-catalog.php добавит)' : '';
    echo "  {$x['CODE']} ({$x['NAME']}, {$x['PROPERTY_TYPE']}): {$x['n']}" . ($role ? "  [$role]" : '') . "$hint\n";
}
echo "\nНастройки витрины: размеры=" . implode(',', $store['size_props']) . "; цвета=" . implode(',', $store['color_props'])
    . "; скрывать без наличия=" . $store['hide_unavailable'] . "; разделы на главной=" . (\Bitrix\Main\Config\Option::get('main', 'mir_sport_home_sections', '') ?: '(авто: первые 5 разделов)') . "\n";
