<?php

namespace Kkigomi\Plugin\Passkeys;

class Member
{
    use Traits\TableTrait;
    use Traits\SessionTrait;

    private ?array $memberInfo = null;
    private ?array $credential = null;

    function __construct(string $memberId)
    {
        global $g5;

        $tables = self::passkeyTables();
        $g5table = self::g5Tables();

        $memberInfo = sql_fetch("SELECT
            `mb_no`,
            `mb_id`,
            `mb_name`,
            `mb_nick`,
            `mb_certify`,
            `mb_leave_date`,
            `mb_intercept_date`,
            (SELECT `userhandle` FROM `{$tables['member']}` WHERE `mb_no` = `member`.`mb_no`) AS `userhandle`
            FROM `{$g5table['member_table']}` AS `member`
            WHERE `mb_id` = '{$memberId}'
        ");

        $this->memberInfo = $memberInfo;
    }

    public function isLogged()
    {
        return $this->getLoggedSession() === $this->getId();
    }

    public function isLoggedPasskey(): bool
    {
        $authenticated = $this->getAuthenticatedSesstion();

        if (!$authenticated) {
            return false;
        }

        if ($authenticated['member_id'] !== $this->getId()) {
            return false;
        }

        return $this->isLogged();
    }

    public function validSessionAuth()
    {
    }

    public function isValid(): bool
    {
        return !!$this->getNo() && !$this->leaved() && !$this->blocked();
    }

    /**
     * 탈퇴한 회원이면 true
     */
    public function leaved(): bool
    {
        return !!$this->memberInfo['mb_leave_date'];
    }

    /**
     * 차단된 회원이면 true
     * @return bool
     */
    public function blocked(): bool
    {
        return !!$this->memberInfo['mb_intercept_date'];
    }

    public function getNo(): ?int
    {
        return (int) isset($this->memberInfo['mb_no']) ? $this->memberInfo['mb_no'] : null;
    }

    public function getId(): ?string
    {
        return $this->memberInfo['mb_id'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->memberInfo['mb_name'] ?? null;
    }

    public function getNick(): ?string
    {
        return $this->memberInfo['mb_nick'] ?? null;
    }

    public function getUserhandle(bool $createIfNotExists = false): ?string
    {
        if ($createIfNotExists === true && !$this->memberInfo['userhandle'] && $this->isLogged()) {
            $tables = self::passkeyTables();
            $memberToken = \bin2hex(random_bytes(16));
            sql_query("INSERT INTO {$tables['member']} (mb_no, userhandle) VALUES ('{$this->getNo()}', '{$memberToken}');");

            $this->memberInfo['userhandle'] = $memberToken;
        }

        return $this->memberInfo['userhandle'];
    }

    public function getCredential(): ?array
    {
        if ($this->credential !== null) {
            return $this->credential;
        }

        $tables = self::passkeyTables();

        $query = sql_query("SELECT * FROM {$tables['credential']} WHERE `mb_no` = {$this->getNo()}");

        if ($query && $query->num_rows) {
            $this->credential = [];
            while ($fetch = sql_fetch_array($query)) {
                $fetch['raw_credential_id'] = \hex2bin($fetch['credential_id']);
                $fetch['credential_publickey'] = \base64_decode($fetch['credential_publickey']);
                $this->credential[$fetch['credential_id']] = $fetch;
            }
        }

        return $this->credential;
    }

    public function getPublicKey(string $credentialId)
    {
        $credential = $this->getCredential();

        $publickeyList = array_reduce($credential, function ($carry, $item) {
            $carry[$item['credential_id']] = $item;
            return $carry;
        }, []);

        return $publickeyList[$credentialId] ?? null;
    }

    public function getCountPublickey(): int
    {
        $credential = $this->getCredential();

        return count($credential ?? []);
    }

    // public function getAllPublickey()
    // {
    //     return $this->getCredential();
    // }

    public function getCredentialIdList()
    {
        $list = $this->getCredential() ?? [];
        return array_column($list, 'raw_credential_id');
    }

    public function clearAllRegistration()
    {
        $tables = self::passkeyTables();

        \g5_delete_cache('passkey-wrong-password_user-' . $this->getNo());

        sql_query("DELETE FROM `{$tables['credential']}` WHERE `mb_no` = {$this->getNo()}");
        sql_query("DELETE FROM `{$tables['member']}` WHERE `mb_no` = {$this->getNo()}");
    }

    public function leave(int $memberNo)
    {
        if ($memberNo !== $this->getNo()) {
            return;
        }

        $this->clearAllRegistration();

        unset($_SESSION['kg_passkey_authenticated']);
        unset($_SESSION['kg_passkey_authentication_challenge']);
        unset($_SESSION['kg_passkey_authentication_temporal']);
    }

    public function getAuthentication()
    {
        return $_SESSION['kg_passkey_authenticated'] ?? false;
    }

    public function removeCredential(string $credentialId)
    {
        $tables = self::passkeyTables();

        sql_query("DELETE FROM `{$tables['credential']}`
            WHERE `mb_no` = {$this->getNo()} AND `credential_id` = '{$credentialId}'");
    }

    public function logout()
    {
        if (function_exists('social_provider_logout')) {
            \social_provider_logout();
        }

        session_unset();
        session_destroy();

        // 자동로그인 해제
        set_cookie('ck_mb_id', '', 0);
        set_cookie('ck_auto', '', 0);
    }
}
