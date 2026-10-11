<?php
/** Provision the receiving side of CommerceML; credentials arrive only on stdin. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

try {
    foreach (['iblock', 'catalog', 'sale'] as $module) {
        if (!Loader::includeModule($module)) {
            throw new RuntimeException('Required module is missing: ' . $module);
        }
    }
    $apply = in_array('--apply', $argv, true);
    $input = json_decode(stream_get_contents(STDIN), true, 16, JSON_THROW_ON_ERROR);
    $login = (string)($input['login'] ?? 'exchange_1c');
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $login)) {
        throw new RuntimeException('Invalid exchange login.');
    }
    $groupCode = 'MIR_SPORT_1C_EXCHANGE';
    $userCode = 'mir_sport_exchange_1c';
    $group = GroupTable::getRow(['filter' => ['=STRING_ID' => $groupCode], 'select' => ['ID', 'ACTIVE']]);
    $user = UserTable::getRow(['filter' => ['=LOGIN' => $login], 'select' => ['ID', 'XML_ID', 'ACTIVE', 'EXTERNAL_AUTH_ID']]);
    if ($user && ($user['XML_ID'] !== $userCode || !empty($user['EXTERNAL_AUTH_ID']))) {
        throw new RuntimeException('Login already belongs to another account; no changes applied.');
    }
    if ($user && in_array(1, array_map('intval', CUser::GetUserGroup((int)$user['ID'])), true)) {
        throw new RuntimeException('Exchange user must not be an administrator.');
    }
    $site = \Bitrix\Main\SiteTable::getRow(['filter' => ['=ACTIVE' => 'Y'], 'select' => ['LID'], 'order' => ['DEF' => 'DESC', 'SORT' => 'ASC']]);
    if (!$site || !\Bitrix\Iblock\TypeTable::getRow(['filter' => ['=ID' => 'catalog'], 'select' => ['ID']])) {
        throw new RuntimeException('An active site and the catalog iblock type must exist first.');
    }
    $options = [
        '1C_IBLOCK_TYPE' => 'catalog',
        '1C_SITE_LIST' => $site['LID'],
        '1C_USE_OFFERS' => 'Y',
        '1C_FORCE_OFFERS' => 'N',
        '1C_INTERVAL' => '20',
        '1C_FILE_SIZE_LIMIT' => '1048576',
        '1C_USE_ZIP' => 'Y',
        '1C_USE_CRC' => 'Y',
        '1C_ELEMENT_ACTION' => 'N',
        '1C_SECTION_ACTION' => 'N',
        '1C_SKIP_ROOT_SECTION' => 'N',
        '1C_TRANSLIT_ON_ADD' => 'Y',
        '1C_TRANSLIT_ON_UPDATE' => 'N',
        '1C_TRANSLIT_REPLACE_CHAR' => '-',
        '1C_DISABLE_CHANGE_PRICE_NAME' => 'Y',
        '1C_IBLOCK_CACHE_MODE' => CIBlockCMLImport::IBLOCK_CACHE_FINAL,
        // Keep 1C photos at full size: by default Bitrix shrinks the detail picture to 300x300 and the preview to 100x100.
        '1C_DETAIL_RESIZE' => 'N',
        '1C_GENERATE_PREVIEW' => 'Y',
        '1C_PREVIEW_WIDTH' => '600',
        '1C_PREVIEW_HEIGHT' => '600',
    ];
    // Validate credentials before creating a group or changing any settings.
    $password = (string)($input['password'] ?? '');
    if ($apply && $user && $password === '') {
        throw new RuntimeException('Restore the existing exchange password before applying.');
    }
    if ($apply && !$user && (strlen($password) < 20 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password))) {
        throw new RuntimeException('A strong password of at least 20 characters is required for a new account.');
    }
    if ($apply) {
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->startTransaction();
        try {
            if (!$group) {
                $added = GroupTable::add([
                    'STRING_ID' => $groupCode, 'NAME' => 'Мир спорта: обмен с 1С',
                    'DESCRIPTION' => 'Service group for standard CommerceML catalog import.',
                    'ACTIVE' => 'Y', 'IS_SYSTEM' => 'N', 'ANONYMOUS' => 'N',
                ]);
                if (!$added->isSuccess()) {
                    throw new RuntimeException('Could not create the exchange group.');
                }
                $group = ['ID' => (int)$added->getId(), 'ACTIVE' => 'Y'];
            } elseif ($group['ACTIVE'] !== 'Y') {
                throw new RuntimeException('Exchange group is inactive; review it before applying.');
            }
            if (!$user) {
                // UserTable explicitly forbids add/update; CUser handles password hashing and events.
                $api = new CUser();
                $id = $api->Add([
                    'LOGIN' => $login, 'XML_ID' => $userCode, 'ACTIVE' => 'Y',
                    'NAME' => 'Обмен с 1С', 'EMAIL' => 'exchange_1c@example.invalid',
                    'PASSWORD' => $password, 'CONFIRM_PASSWORD' => $password,
                    'GROUP_ID' => [(int)$group['ID']],
                ]);
                if (!$id) {
                    throw new RuntimeException('Could not create the exchange account; check site user policies.');
                }
                $user = ['ID' => (int)$id, 'XML_ID' => $userCode, 'ACTIVE' => 'Y'];
            } elseif ($user['ACTIVE'] !== 'Y') {
                throw new RuntimeException('Exchange account is inactive; review it before applying.');
            }
            $memberships = array_map('intval', CUser::GetUserGroup((int)$user['ID']));
            if (!in_array((int)$group['ID'], $memberships, true)) {
                CUser::SetUserGroup((int)$user['ID'], array_merge($memberships, [(int)$group['ID']]));
            }
            // Preserve existing importer groups, never add this account to administrators.
            $allowed = array_filter(explode(',', Option::get('catalog', '1C_GROUP_PERMISSIONS', '1')));
            $allowed[] = (string)$group['ID'];
            $options['1C_GROUP_PERMISSIONS'] = implode(',', array_unique($allowed));
            foreach ($options as $name => $value) {
                Option::set('catalog', $name, $value);
            }
            $connection->commitTransaction();
        } catch (Throwable $error) {
            $connection->rollbackTransaction();
            throw $error;
        }
    }
    $actual = [];
    foreach (array_keys($options) as $name) {
        $actual[$name] = Option::get('catalog', $name, '[not set]');
    }
    echo json_encode([
        'mode' => $apply ? 'applied' : 'check', 'login' => $login,
        'user_id' => $user ? (int)$user['ID'] : null,
        'group_id' => $group ? (int)$group['ID'] : null,
        'options' => $actual, 'catalog_binding' => 'pending_1c_catalog_identifier',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Setup failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
