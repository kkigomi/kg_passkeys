<?php

namespace Kkigomi\Plugin\Passkeys;

use ErrorException;
use lbuchs\WebAuthn\WebAuthn as LbuchsWebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\Attestation\AuthenticatorData;
use Throwable;

class Webauthn
{
    use Traits\TableTrait;
    use Traits\SessionTrait;

    const AUTH_PASSKEY = 'passkey';
    const AUTH_PASSWORD = 'password';

    private LbuchsWebAuthn $webauthn;
    private string $rpName = 'Kkigomi G5 Webauthn';
    private string $rpId;
    private bool $userVerification = true;
    private int $timeout = 60;
    private bool $allowUsb = true;
    private bool $allowNfc = true;
    private bool $allowBle = true;
    private bool $allowInternal = true;
    private $requireResidentKey = true;
    private $requireUserVerification = true;
    private ?bool $crossPlatformAttachment = null;
    private string $username;
    private ? Member $member = null;

    public function __construct(? Member $member = null, ?array $options)
    {
        $this->member = $member;

        $this->rpId = $_SERVER['SERVER_NAME'];
        $this->webauthn = new LbuchsWebAuthn($this->rpName, $this->rpId, null);

        if (!empty($options['userVerification'])) {
            $this->userVerification = $options['userVerification'];
        }
        if (!empty($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
    }

    public function setMember(Member $member)
    {
        $this->member = $member;
    }

    public function getMember(): ? Member
    {
        return $this->member ?: null;
    }

    public function getMemberCredentialIds()
    {
        $credentialIds = [];

        if ($this->member) {
            $credentialIds = $this->member->getCredentialIdList();
        }

        return $credentialIds;
    }

    public function getCredentialRequestOptions()
    {
        $requestOptions = $this->webauthn->getGetArgs($this->getMemberCredentialIds(), $this->timeout, $this->allowUsb, $this->allowNfc, $this->allowBle, $this->allowInternal, $this->userVerification);

        if ($this->member && $this->member->isLogged() && !$this->member->getCountPublickey()) {
            throw new Exceptions\NotFoundPublicKeyException($this->member);
        }

        $this->updateChallengeKey();

        if ($this->member) {
            $requestOptions->member = [
                'id' => $this->member->getId(),
                'no' => $this->member->getNo(),
            ];
        }

        return $requestOptions;
    }

    /**
     * @throws \Error
     * @return \stdClass
     */
    public function getCredentialCreateOptions()
    {
        if (!$this->member->isLogged()) {
            throw new \Error('로그인 해주세요');
        }

        $excludeCredentialIds = [];

        if ($credential = $this->member->getCredential()) {
            foreach ($credential as $row) {
                $excludeCredentialIds[] = hex2bin($row['credential_id']);
            }
        }

        $createOptions = $this->webauthn->getCreateArgs($this->member->getUserhandle(true), $this->member->getId(), $this->member->getId(), $this->timeout, $this->requireResidentKey, $this->requireUserVerification, $this->crossPlatformAttachment, $excludeCredentialIds);

        $this->updateChallengeKey();

        return $createOptions;
    }

    public function getSessionChallenge()
    {
        return new ByteBuffer(\hex2bin($_SESSION[static::$sessionKeyChallenge]));
    }

    private function updateChallengeKey()
    {
        $_SESSION[static::$sessionKeyChallenge] = \bin2hex($this->webauthn->getChallenge()->getBinaryString());
    }

    /**
     * @param mixed $postData
     * @throws Exceptions\InvalidPublicKeyException 등록되지않은 패스키
     * @throws Exceptions\NotMatchedUserhandleException 요청한 userhandle이 일치하지 않음
     * @return \stdClass
     */
    public function authentication($postData)
    {
        $tables = self::passkeyTables();
        $g5tables = self::g5Tables();

        $clientDataJSON = base64_decode($postData->clientDataJSON);
        $authenticatorData = base64_decode($postData->authenticatorData);
        $signature = base64_decode($postData->signature);
        $credentialId = bin2hex(base64_decode($postData->id));
        $autoLogin = $postData->autoLogin;
        $challenge = $this->getSessionChallenge();
        $userHandle = base64_decode($postData->userHandle);

        if ($this->member && $this->member->isLogged()) {
            // userhandle이 일치하는지 확인
            if ($userHandle !== $this->member->getUserhandle()) {
                throw new Exceptions\NotMatchedUserhandleException($this->member);
            }
        } else {
            // 로그인 상태가 아니면 userhandle이 일치하는 회원정보 가져옴
            $member = MemberFactory::whereUserHandle($userHandle);

            if (!$member) {
                throw new Exceptions\InvalidPublicKeyException();
            }

            $this->setMember(MemberFactory::whereUserHandle($userHandle));
        }

        // 인증서 목록
        $credential = $this->member->getPublicKey($credentialId);

        // 등록된 패스키가 없는데도 인증 요청이라면
        if ($credential === null) {
            throw new Exceptions\NotFoundPublicKeyException($this->member);
        }

        try {
            $this->webauthn->processGet($clientDataJSON, $authenticatorData, $signature, $credential['credential_publickey'], $challenge, (int) $credential['signature_counter'], $this->requireUserVerification);
        } catch (Throwable $e) {
            throw new Exceptions\InvalidPublicKeyException($this->member);
        }

        $authenticatorObj = new AuthenticatorData($authenticatorData);
        $updateSignatureCounter = (int) $authenticatorObj->getSignCount();

        // 인증 사용시간 저장
        sql_query("UPDATE `{$tables['credential']}` SET `signature_counter` = {$updateSignatureCounter}, `lastused_at` = CURRENT_TIMESTAMP WHERE `credential_id` = '{$credentialId}'");

        $return = new \stdClass();
        $return->success = true;
        $return->member_id = $this->member->getId();

        $query = sql_query("SELECT * FROM `{$g5tables['member_table']}` where `mb_no` = {$this->member->getNo()}");
        $result = sql_fetch_array($query);

        $_SESSION['kg_passkey_authenticated'] = [
            'member_no' => $this->member->getNo(),
            'member_id' => $this->member->getId(),
            'userhandle' => $this->member->getUserhandle(),
            'timestamp' => \G5_SERVER_TIME
        ];

        unset($_SESSION['kg_passkey_authentication_challenge']);

        return $return;
    }

    /**
     * @throws Exceptions\NoLoggedException 로그인되어있지 않음
     * @throws Exceptions\NotMatchedUserhandleException 요청의 userhandle이 일치하지 않음
     */
    private function checkLoggedMember(?string $userhandle = null): void
    {
        // 로그인 상태 확인
        if (!$this->member) {
            throw new Exceptions\NoLoggedException();
        }

        // 회원의 userhandle과 요청의 userhandle 일치 확인
        if ($userhandle && $this->member->getUserhandle() !== $userhandle) {
            throw new Exceptions\NotMatchedUserhandleException();
        }
    }

    public function registrations($postData)
    {
        $this->checkLoggedMember();
        $this->validateSessionAuth();

        $tables = self::passkeyTables();

        $clientDataJSON = base64_decode($postData->clientDataJSON);
        $attestationObject = base64_decode($postData->attestationObject);
        $authenticatorName = $postData->authenticatorName;
        $challenge = $this->getSessionChallenge();

        $data = $this->webauthn->processCreate($clientDataJSON, $attestationObject, $challenge, true, true);

        $data->userId = $this->member->getUserhandle(true);
        $data->userName = $this->member->getName();
        $data->userDisplayName = $this->member->getId();

        // 인증정보 저장
        $credentialId = \bin2hex($data->credentialId);
        $signatureCounter = $data->signatureCounter ?? 0;
        $credentialPublicKey = \base64_encode($data->credentialPublicKey);
        sql_query("INSERT INTO `{$tables['credential']}`
            (`mb_no`, `credential_id`, `signature_counter`, `credential_publickey`, `authenticator_name`)
            VALUES
            ({$this->member->getNo()}, '{$credentialId}', {$signatureCounter}, '{$credentialPublicKey}', '{$authenticatorName}')
        ");
    }

    public function sessionAuth(string $type, $postData)
    {
        try {
            if (isset($postData->userHandle)) {
                $userHandle = base64_decode($postData->userHandle);
                $this->checkLoggedMember($userHandle);
            }
        } catch (Throwable $e) {
        }

        if ($postData->password) {
            $password = trim($postData->password);
        }

        $time = \G5_SERVER_TIME;
        $failedCacheKey = 'passkey-wrong-password_user-' . $this->member->getNo();

        if ($type === $this::AUTH_PASSWORD && !empty($password)) {
            $member = \get_member($this->member->getId());
            $failedCount = \g5_get_cache($failedCacheKey) ?: 0;

            if (\check_password($password, $member['mb_password'])) {
                $_SESSION[static::$sessionKeyTemporal] = [
                    'authType' => $this::AUTH_PASSWORD,
                    'memberId' => $this->member->getId(),
                    'authTime' => $time,
                    /* 10분 제한 */
                    'expires' => $time + 600,
                ];
                \g5_delete_cache($failedCacheKey);
            } else {
                \g5_set_cache($failedCacheKey, ++$failedCount, 86_400);

                // 비밀번호 3회 이상 틀리거나 패스키 로그인 상태에서 틀리면 로그아웃
                if ($failedCount >= 3 || $this->member->isLoggedPasskey()) {
                    $this->member->logout();
                    throw new Exceptions\ExceedWrongPasswordException();
                }
            }
        } else if ($type === $this::AUTH_PASSKEY) {
            $return = $this->authentication($postData);

            if ($return->success) {
                $_SESSION[static::$sessionKeyTemporal] = [
                    'authType' => $this::AUTH_PASSKEY,
                    'memberId' => $this->member->getId(),
                    'authTime' => \G5_SERVER_TIME,
                    /* 30분 제한 */
                    'expires' => $time + 1_800,
                ];
            } else {
                return $return;
            }
        }

        unset($_SESSION[static::$sessionKeyChallenge]);
    }

    public function validateSessionAuth(): bool
    {
        $temporalSession = $this->getTemporalSession();

        // 회원 정보가 없음
        if (!$this->member) {
            return false;
        }

        // 현재 로그인된 회원이 아님
        if (!$this->isLogged($this->member->getId())) {
            return false;
        }

        // 인증 세션이 없음
        if (!isset($temporalSession)) {
            throw new Exceptions\NotFoundTemporalAuthSessionException();
        }

        // 인증 세션의 회원이 다름
        if ($temporalSession['memberId'] !== $this->member->getId()) {
            return false;
        }

        // 인증 세션이 만료됨
        if ($temporalSession['authType'] === $this::AUTH_PASSWORD) {
            if ($temporalSession['expires'] < \G5_SERVER_TIME) {
                // 만료된 세션 제거
                unset($_SESSION[static::$sessionKeyTemporal]);

                throw new Exceptions\TimeoutTemporalAuthSessionException();
            }
        }

        return true;
    }

    public function clearRegistrations()
    {
        // 인증 세션이 없음
        if (!$this->validateSessionAuth()) {
            return false;
        }

        // DB에서 회원의 모든 데이터 제거
        $this->member->clearAllRegistration();

        $this->clearPasskeysSession();
    }

    public function removeCredential(string $credentialId)
    {
        // 인증 세션이 없음
        if (!$this->validateSessionAuth()) {
            return false;
        }

        // DB에서 인증키 제거
        $this->member->removeCredential($credentialId);
    }

}
