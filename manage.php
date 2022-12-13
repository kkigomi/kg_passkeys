<?php
// 패스키 개인 설정 페이지
include './_common.php';
include_once './vendor/autoload.php';

$g5['title'] = '패스키 관리';

if (passkeysPluginNeedMigration() === true) {
    alert('Kkigomi Passkeys 플러그인의 설치 및 업데이트가 필요합니다. 관리자에게 문의하세요.');
    return;
}

run_event('passkey-manage:before');

$currentMember_f8261535c9c14023ba18acbc77ac9f03 = Kkigomi\Plugin\Passkeys\MemberFactory::currentMember();
$kkigomiWebauthn = new Kkigomi\Plugin\Passkeys\Webauthn($currentMember_f8261535c9c14023ba18acbc77ac9f03, []);

try {
    $result = $kkigomiWebauthn->validateSessionAuth();
    if (!$result) {
        alert('잘못된 접근입니다.');
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
    $returnUrl = \G5_URL;

    if ($e instanceof Kkigomi\Plugin\Passkeys\Exceptions\ExceedWrongPasswordException) {
        $errorMessage = '비밀번호 입력제한을 초과하여 로그아웃합니다';
        // @FIXME 상대 경로 가져오는 함수가 없나봐...
        $returnUrl = '../../bbs/logout.php';
    }

    alert($errorMessage, $returnUrl);
    exit;
}

include_once('./_head.php');

if (file_exists($member_skin_path . '/passkeys_manage.skin.php')) {
    include_once($member_skin_path . '/passkeys_manage.skin.php');
} else {
    include_once(\KG_PASSKEYS_PATH . '/skin/member/default/passkeys_manage.skin.php');
}

include_once('./_tail.php');
