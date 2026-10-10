<?php
namespace MirSport;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Social links of the footer. TODO: replace the network home pages with the shop's accounts.
 *
 * @return array<int, array{label: string, href: string, icon: string, text: string}>
 */
function socials(): array
{
    return [
        ['label' => 'Telegram', 'href' => 'https://t.me/', 'icon' => 'i-telegram', 'text' => ''],
        ['label' => 'ВКонтакте', 'href' => 'https://vk.com/', 'icon' => '', 'text' => 'VK'],
        ['label' => 'WhatsApp', 'href' => 'https://wa.me/', 'icon' => 'i-chat', 'text' => ''],
    ];
}
