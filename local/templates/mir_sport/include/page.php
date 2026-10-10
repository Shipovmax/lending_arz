<?php
namespace MirSport;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Title and h1 of inner pages. Page properties are set by the page after header.php has run,
 * so these helpers are used as buffer callbacks ($APPLICATION->AddBufferContent).
 *
 * Page property hide_h1 = "Y" turns off the wrapper and the h1 for a page that prints its own
 * (home hero, product card, catalog with its own layout). The home page is always excluded.
 */
function is_home(): bool
{
    global $APPLICATION;
    return $APPLICATION->GetCurPage(true) === SITE_DIR . 'index.php';
}

function has_page_title(): bool
{
    global $APPLICATION;
    return !is_home() && $APPLICATION->GetPageProperty('hide_h1') !== 'Y';
}

/** Browser title: the page's title, or the shop name when the page sets none. */
function browser_title(): string
{
    global $APPLICATION;
    $title = trim(strip_tags((string)$APPLICATION->GetTitle('title')));
    if ($title === '') {
        return 'Мир спорта';
    }
    return htmlspecialcharsbx(mb_strpos($title, 'Мир спорта') === false ? $title . ' — Мир спорта' : $title);
}

function title_open(): string
{
    global $APPLICATION;
    if (is_home()) {
        // The home page prints its hero h1 once it is ported (then it sets hide_h1 = Y); until then a hidden h1 keeps one h1 per page.
        return $APPLICATION->GetPageProperty('hide_h1') === 'Y' ? '' : '<h1 class="visually-hidden">Мир спорта</h1>';
    }
    if (!has_page_title()) {
        return '';
    }
    $title = trim(strip_tags((string)$APPLICATION->GetTitle(false)));
    return '<div class="container page">' . ($title !== '' ? '<h1 class="page-title">' . htmlspecialcharsbx($title) . '</h1>' : '');
}

function title_close(): string
{
    return has_page_title() ? '</div>' : '';
}

/**
 * ' aria-current="page"' when the menu link points to the current page. The bare /catalog/ link
 * is shared by many placeholder items, so it never counts as current.
 */
function nav_current(string $href): string
{
    $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
    $current = rtrim((string)$request->getRequestedPageDirectory(), '/') . '/';
    $path = (string)parse_url($href, PHP_URL_PATH);
    parse_str((string)parse_url($href, PHP_URL_QUERY), $query);

    if ($query) {
        foreach ($query as $key => $value) {
            if ((string)$request->get($key) !== (string)$value) {
                return '';
            }
        }
        return $current === $path ? ' aria-current="page"' : '';
    }
    if ($path === SITE_DIR . 'catalog/' || $path === '') {
        return '';
    }
    return str_starts_with($current, $path) ? ' aria-current="page"' : '';
}
