<?php
namespace MirSport;

use Bitrix\Main\IO\File;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Contact data of the shop. Each value lives in its own file next to this one
 * (phone.txt, hours.txt, address.txt, email.txt, legal.txt) and is edited as plain text.
 */
function contacts(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $read = static function (string $name): string {
        $file = new File(__DIR__ . '/' . $name . '.txt');
        return $file->isExists() ? trim(strip_tags($file->getContents())) : '';
    };

    $phone = $read('phone');
    $digits = preg_replace('/\D+/', '', $phone);
    // 8XXXXXXXXXX and XXXXXXXXXX (without the country code) are Russian numbers.
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits[0] = '7';
    } elseif (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    $email = $read('email');

    $cache = [
        'phone' => $phone,
        // A tel: link only for a real number, not for the "[+7 (___)...]" placeholder.
        // Until phone.txt holds a real number the link uses the prototype's placeholder.
        'phone_href' => strlen($digits) >= 10 ? 'tel:+' . $digits : '',
        'hours' => $read('hours'),
        'address' => $read('address'),
        'email' => $email,
        'email_href' => filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $email : '',
        'legal' => $read('legal'),
    ];

    return $cache;
}

/** Link when the value is real, plain text for a placeholder. */
function link_or_text(string $text, string $href, string $class): string
{
    $text = htmlspecialcharsbx($text);
    $classAttr = $class !== '' ? ' class="' . htmlspecialcharsbx($class) . '"' : '';
    if ($href === '') {
        return '<span' . $classAttr . '>' . $text . '</span>';
    }
    return '<a' . $classAttr . ' href="' . htmlspecialcharsbx($href) . '">' . $text . '</a>';
}
