<?php
/**
 * 이 플러그인의 설정은 /config.custom.php 파일을 참조합니다.
 * 이 플러그인의 설정을 변경하기위해 이 파일을 직접 수정하지 마세요.
 *
 * @var bool \KG_PASSKEYS_DEBUG 플러그인의 디버깅 모드 활성화
 * @var string \KG_PASSKEYS_DIR 플러그인의 폴더명. 기본 폴더명을 변경하여 할 때 지정
 */
if (!defined('_GNUBOARD_')) {
    exit;
}
if (PHP_VERSION_ID < 70400) {
    return;
}

if (file_exists(G5_PATH . '/config.custom.php')) {
    include_once G5_PATH . '/config.custom.php';
}

if (!defined('KG_PASSKEYS_DEBUG')) {
    /** @var bool */
    define('KG_PASSKEYS_DEBUG', false);
}

if (!defined('KG_PASSKEYS_DIR')) {
    /** @var string */
    define('KG_PASSKEYS_DIR', 'kg_passkeys');
}

define('KG_PASSKEYS_PATH', G5_PLUGIN_PATH . '/' . KG_PASSKEYS_DIR);
define('KG_PASSKEYS_URL', G5_PLUGIN_URL . '/' . KG_PASSKEYS_DIR);

include_once KG_PASSKEYS_PATH . '/vendor/autoload.php';
