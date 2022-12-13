<?php
// \dump('PASSKEY BOOTSTRAP');
if (!defined('_GNUBOARD_')) {
    exit;
}

define('KG_PASSKEY_VERSION', '0.1.0');
define('KG_PASSKEY_VERSION_ID', 100);

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
        if(function_exists('\debug')) \debug('needMigration');
        return true;
    }
    return false;
}

Kkigomi\Plugin\Passkeys\Passkeys::setPath(KG_PASSKEYS_PATH, KG_PASSKEYS_URL);
Kkigomi\Plugin\Passkeys\Passkeys::getinstance();
Kkigomi\Plugin\Passkeys\Passkeys::loadAssets();

// AJAX 요청 시 `alert()`, `alert_close()` 응답을 JSON 포맷으로 변경
add_event('alert', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerAlertHookAction'
], \G5_HOOK_DEFAULT_PRIORITY, 4);
add_event('alert_close', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerAlertHookAction'
], \G5_HOOK_DEFAULT_PRIORITY, 1);

// 아웃로그인 폼에 패스키 모달 추가
add_replace('outlogin_content', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerOutloginContent'
], \G5_HOOK_DEFAULT_PRIORITY, 2);

// bbs/login_check.php에서 비밀번호 공백 체크 무시
add_replace('check_empty_member_login_password', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerCheckEmptyMemberLoginPassword'
], 1, 2);

// bbs/login_check.php에서 비밀번호 검증 무시
add_replace('login_check_need_not_password', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerLoginCheckNeedNotPassword'
], 1, 5);

// /bbs/register_form.php 폼 접근 시 비밀번호 입력되면 패스키 세션 인증 생성
add_event('register_form_before', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerRegisterFormBefore'
], 1);
add_event('passkey-manage:before', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerRegisterFormBefore'
], 1);

// bbs/member_confirm.php 에서 비밀번호 체크와 비밀번호 비교과정을 무시합니다.
add_replace('member_confirm_next_url', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerMemberConfirmNextUrl'
], 1, 1);

// 회원 탈퇴 시 데이터 정리
add_event('member_leave', [
    Kkigomi\Plugin\Passkeys\HookListener::class,
    'listenerMemberLeave'
], 1, 1);
