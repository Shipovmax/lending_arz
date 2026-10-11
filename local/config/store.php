<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }
// Defaults fit the wizard demo catalog and the first real 1C catalog; other catalogs set the options in the admin
// (Settings > main options) or via Option::set('main', ...).
$list = static fn(string $option, string $default): array => array_values(array_filter(array_map(
    'trim',
    explode(',', \Bitrix\Main\Config\Option::get('main', $option, $default))
)));

return [
    'catalog_iblock_id' => (int)\Bitrix\Main\Config\Option::get('main', 'mir_sport_catalog_iblock_id', '2'),
    'consent_id' => (int)\Bitrix\Main\Config\Option::get('main', 'mir_sport_consent_id', '1'),
    // Name of the price type, as the price type is called in 1C and on the site.
    'price_code' => \Bitrix\Main\Config\Option::get('main', 'mir_sport_price_code', 'BASE'),
    // Offer (SKU) properties that carry the size, in priority order: the first one that is filled is shown.
    'size_props' => $list('mir_sport_size_props', 'SIZES_SHOES,SIZES_CLOTHES,RAZMER_OBUVI_ROSSIYSKIY,RAZMER_ODEZHDY_ROSSIYSKIY,RAZMER_RAMY,RAZMER'),
    // Offer properties that carry the color. The wizard demo uses the ColorReference directory, 1C sends a plain list.
    'color_props' => $list('mir_sport_color_props', 'COLOR_REF,TSVET_ROSSIYSKIY,TSVET_POSTAVSHCHIKA'),
    // Hide products and offers that have nothing in stock: 1C sends every color/size combination of a model, even the
    // ones that were never stocked, and the shop sells what is on the shelf.
    'hide_unavailable' => \Bitrix\Main\Config\Option::get('main', 'mir_sport_hide_unavailable', 'Y') === 'N' ? 'N' : 'Y',
    // Product properties: article and brand, in priority order.
    'article_props' => $list('mir_sport_article_props', 'ARTNUMBER,CML2_ARTICLE'),
    'brand_props' => $list('mir_sport_brand_props', 'BRAND_REF,BREND,CML2_MANUFACTURER'),
];
