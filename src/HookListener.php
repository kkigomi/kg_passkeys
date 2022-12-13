<?php
namespace Kkigomi\Plugin\Passkeys;

use stdClass;
use Throwable;

class HookListener
{
    use Traits\SingletonTrait;
    use Traits\SessionTrait;

    protected $currentMember;

    protected function singletonInstanceInit()
    {
        $this->currentMember = MemberFactory::currentMember();
    }

    /**
     * `alert` Event Hook
     * - 패스키 플러그인 내일 파일에서 alert(), alert_close() 함수의 응답을 JSON으로 변환
     */
    public function listenerAlertHookAction($msg = null, $url = null, $error = null, $post = null)
    {
        if (stripos($_SERVER['SCRIPT_FILENAME'], \KG_PASSKEYS_PATH) !== -1 && !!stripos(implode('.', [$_SERVER['HTTP_ACCEPT']]), 'json')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['msg' => $msg, 'url' => $url, 'error' => $error, 'post' => $post]);
            exit;
        }
    }

    /**
     * `outlogin_content` Replace Hook
     * 아웃로그인 스킨 출력 시 passkey 동작에 필요한 기능 주입
     *   - asset 로드
     */
    public function listenerOutloginContent($content = null)
    {
        global $is_member;

        Passkeys::loadAssets();
    }

    /**
     * Hook: check_empty_member_login_password
     * /bbs/login_check.php에서 비밀번호 공백 체크 무시
     */
    public function listenerCheckEmptyMemberLoginPassword($is_input_password = null, $mb_id = null)
    {
        // 비로그인 상태. 패스키 세션만 존재

        if ($this->isPasskeyLogged($mb_id)) {
            return false;
        }

        return $is_input_password;
    }

    /**
     * Hook: login_check_need_not_password
     * bbs/login_check.php에서 비밀번호 검증 무시
     */
    public function listenerLoginCheckNeedNotPassword($is_social_password_check = null, $mb_id = null, $mb_password = null, $mb = null, $is_social_login = null)
    {
        if ($this->isPasskeyLogged($mb_id)) {
            return true;
        }

        return $is_social_password_check;
    }

    /**
     * `member_confirm_next_url` Event Hook
     */
    public function listenerMemberConfirmNextUrl($url = '')
    {
        $currentMember = MemberFactory::currentMember();

        try {
            if ($this->validateSessionAuth($currentMember->getId())) {
                $provider_name = 'passkeys';
                $social_token = social_nonce_create($provider_name);
                set_session('social_link_token', $social_token);

                $params = array('provider' => $provider_name);

                goto_url(get_params_merge_url($params, $url));
            } else {
                $script = '<script>kkigomiPasskey.preferredSessionAuth(\'passkey\');</script>';
                add_javascript($script, 10);
            }
        } catch (Throwable $e) {
            if (
                $e instanceof Exceptions\NotFoundTemporalAuthSessionException
                || $e instanceof Exceptions\TimeoutTemporalAuthSessionException
            ) {
                $script = '<script>kkigomiPasskey.preferredSessionAuth(\'passkey\');</script>';
                add_javascript($script, 10);
            } else {
                // \alert('오류가 발생했습니다(Passkeys)');
            }
        }

        return $url;
    }

    /**
     * `register_form_before` Event Hook
     */
    public function listenerRegisterFormBefore(): void
    {
        if (!isset($_POST['mb_password']) || trim($_POST['mb_password'])) {
            return;
        }

        $currentMember = MemberFactory::currentMember();
        $webauthn = new WebAuthn($currentMember, []);

        $postData = new stdClass();
        $postData->password = $_POST['mb_password'];

        try {
            $webauthn->sessionAuth('password', $postData);
        } catch (Throwable $e) {
            alert('비밀번호 입력제한을 초과하여 로그아웃합니다', \G5_URL);
        }
    }

    /**
     * `member_leave` Event Hook
     */
    public function listenerMemberLeave($member = null): void
    {
        if ($member && is_array($member)) {
            $leavedMember = MemberFactory::whereId($member['mb_id']);
            $leavedMember->leave($member['mb_no']);
        }
    }
}
