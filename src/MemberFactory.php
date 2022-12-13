<?php

namespace Kkigomi\Plugin\Passkeys;

class MemberFactory
{
    use Traits\TableTrait;

    private static $loggedSessionKey = 'ss_mb_id';
    private static $currentMember = null;
    private static $memberItem = [];

    private function __construct()
    {
    }

    public static function whereId(string $id): ? Member
    {
        if (isset(self::$memberItem[$id])) {
            return self::$memberItem[$id];
        }

        self::$memberItem[$id] = new Member($id);

        return self::$memberItem[$id];
    }

    public static function whereUserHandle(string $userHandle): ? Member
    {
        $tables = self::passkeyTables();
        $g5 = self::g5Tables();

        $memberInfo = sql_fetch("SELECT `member`.`mb_id`
            FROM
                `{$g5['member_table']}` AS `member`,
                `{$tables['member']}` AS `passkey_member`
            WHERE
                `passkey_member`.`userhandle` = '{$userHandle}'
                AND `member`.`mb_no` = `passkey_member`.`mb_no`
        ");

        if (!$memberInfo) {
            throw new Exceptions\NotFoundUserhandleException();
        }

        return self::whereId($memberInfo['mb_id']);
    }

    public static function currentMember(): ? Member
    {
        if (!isset($_SESSION[self::$loggedSessionKey])) {
            self::$currentMember = null;
            return null;
        }

        if (self::$currentMember) {
            return self::$currentMember;
        }

        self::$currentMember = self::whereId($_SESSION[self::$loggedSessionKey]);

        return self::$currentMember;
    }
}
