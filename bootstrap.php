<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

define('KG_PASSKEY_VERSION', '0.1.1');
define('KG_PASSKEY_VERSION_ID', 101);

if (PHP_VERSION_ID < 70400) {
    return;
}

if (passkeysPluginNeedMigration() === true) {
    return;
}

function passkeysPluginNeedMigration()
{
    $updatedCacheData = g5_get_cache('kg-passkeys-updated') ?: 0;
    if ($GLOBALS['is_admin'] !== 'super' && $updatedCacheData < \KG_PASSKEY_VERSION_ID) {
        return true;
    }
    return false;
}

Kkigomi\Plugin\Passkeys\Passkeys::setPath(KG_PASSKEYS_PATH, KG_PASSKEYS_URL);
Kkigomi\Plugin\Passkeys\Passkeys::getinstance();
Kkigomi\Plugin\Passkeys\Passkeys::loadAssets();
Kkigomi\Plugin\Passkeys\HookListener::getInstance();
