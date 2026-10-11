<?php
namespace MirSport;

use Bitrix\Main\Event;

/**
 * 1C names an offer "<product name> (<size> цв.<color>)". In the basket and the order the size and the color are shown
 * as separate lines, so the brackets only repeat them: the basket item gets the plain product name.
 */
class BasketNames
{
    /** @var array<int, string> product name by offer id */
    private static array $cache = [];

    public static function clean(Event $event): void
    {
        $item = $event->getParameter('ENTITY');
        if (!$item instanceof \Bitrix\Sale\BasketItem || !\Bitrix\Main\Loader::includeModule('catalog')) {
            return;
        }
        $name = (string)$item->getField('NAME');
        if ($name === '' || !str_ends_with($name, ')')) {
            return;
        }
        $offerId = (int)$item->getProductId();
        if ($offerId <= 0) {
            return;
        }
        if (!isset(self::$cache[$offerId])) {
            self::$cache[$offerId] = '';
            $parent = \CCatalogSku::GetProductInfo($offerId);
            if (is_array($parent) && (int)$parent['ID'] !== $offerId) {
                $row = \Bitrix\Iblock\ElementTable::getList(['select' => ['NAME'], 'filter' => ['=ID' => (int)$parent['ID']]])->fetch();
                self::$cache[$offerId] = (string)($row['NAME'] ?? '');
            }
        }
        $product = self::$cache[$offerId];
        // Only the variant brackets that follow the exact product name are cut; "(1-ск)" inside the product name stays.
        if ($product !== '' && $name !== $product && str_starts_with($name, $product . ' (')) {
            $item->setFieldNoDemand('NAME', $product);
        }
    }
}
