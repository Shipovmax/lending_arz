<?php
namespace MirSport;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** Price as in the prototype: "4 290 ₽" with non-breaking spaces. */
function rub(float $value): string
{
    return number_format(round($value), 0, ',', "\u{00A0}") . "\u{00A0}₽";
}

/** Russian plural: plural(3, 'цвет', 'цвета', 'цветов'). */
function plural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    return $n1 === 1 ? $one : $many;
}

/** Resized image URL, or '' when there is no file. */
function image(array|int|null $file, int $width, int $height): string
{
    $id = is_array($file) ? (int)($file['ID'] ?? 0) : (int)$file;
    if ($id <= 0) {
        return '';
    }
    $resized = \CFile::ResizeImageGet($id, ['width' => $width, 'height' => $height], BX_RESIZE_IMAGE_PROPORTIONAL, true);
    return is_array($resized) ? (string)$resized['src'] : '';
}
