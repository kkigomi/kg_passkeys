<?php

namespace Kkigomi\Plugin\Passkeys;

function responseJson($data, bool $exit = false): void
{
    header('Content-Type: application/json');
    echo json_encode($data);

    if ($exit === true) {
        exit;
    }
}

class Passkeys
{
    use Traits\TableTrait;

    private static ?string $pluginPath;
    private static ?string $pluginUrl;
    private static ? Member $currentMember = null;
    private static $webauthn;

    private function __construct()
    {
        // 업데이트 체크 및 업데이트 실행
        if (
            !empty($_SESSION['ss_mb_id'])
            && \is_admin($_SESSION['ss_mb_id']) === 'super'
            && $this->needMigration()
        ) {
            $this->migration();
        }
    }

    public static function getinstance(): self
    {
        static $instance = null;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    public static function setPath(string $path, string $url): void
    {
        self::$pluginPath = $path;
        self::$pluginUrl = $url;
    }

    public static function pluginUrl(): string
    {
        return self::$pluginUrl;
    }

    public static function pluginPath(): string
    {
        return self::$pluginPath;
    }

    public static function loadAssets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        self::importJs('/assets/passkeys.umd.js');
        // self::importCss('/assets/passkeys.css');
        \add_javascript('<script>kkigomiPasskey.boot(' . json_encode([
            'url' => self::pluginUrl(),
            'isMember' => !!$GLOBALS['is_member']
        ], JSON_UNESCAPED_SLASHES) . ');</script>', 10);
    }

    private static function importJs(string $assetPath, int $order = 0): void
    {
        $url = self::$pluginUrl . $assetPath;
        $code = '<script src="' . $url . '?ver=' . filemtime(self::$pluginPath . $assetPath) . '"></script>';

        add_javascript($code, 10);
    }

    private static function importCss(string $assetPath, int $order = 0): void
    {
        $url = self::$pluginUrl . $assetPath;
        $code = '<link rel="stylesheet" href="' . $url . '?ver=' . filemtime(self::$pluginPath . $assetPath) . '">';

        add_stylesheet($code, 10);
    }

    private function needMigration(): bool
    {
        $tables = self::passkeyTables();

        // cache에서 업데이트 기록이 있으면 패스
        $cacheData = g5_get_cache('kg-passkeys-updated') ?: 0;
        if ($cacheData >= \KG_PASSKEY_VERSION_ID) {
            return false;
        }

        // 테이블 없으면 설치
        if (!sql_query("DESC {$tables['member']}") || !sql_query("DESC {$tables['credential']}")) {
            return true;
        }

        return false;
    }

    private function migration(): bool
    {
        $tables = self::passkeyTables();

        $memberTableDesc = sql_query("DESC {$tables['member']}");
        if (!$memberTableDesc) {
            sql_query("CREATE TABLE IF NOT EXISTS `{$tables['member']}` (
                `mb_no` INT(11) NOT NULL,
                `userhandle` VARCHAR(64) NOT NULL,
                `password_method` int(11) NOT NULL DEFAULT '0',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`mb_no`)
            )");
        }

        $credentialTabelDesc = sql_query("DESC {$tables['credential']}");
        if (!$credentialTabelDesc) {
            sql_query("CREATE TABLE IF NOT EXISTS `{$tables['credential']}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `mb_no` INT(11) NOT NULL,
                `authenticator_name` VARCHAR(191) NULL,
                `credential_id` VARCHAR(191) NOT NULL,
                `signature_counter` int(11) NOT NULL DEFAULT 0,
                `credential_publickey` text NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `lastused_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )");
        }

        // cache에 기록
        g5_set_cache('kg-passkeys-updated', \KG_PASSKEY_VERSION_ID);

        $this->notifyMigrated();

        return true;
    }

    private function notifyMigrated()
    {
        $g5tables = self::g5Tables();

        $memoDate = \G5_TIME_YMDHIS;
        $memoVersion = \KG_PASSKEY_VERSION;
        $memoMemberId = $_SESSION['ss_mb_id'];
        sql_query("INSERT INTO `{$g5tables['memo_table']}`
            (`me_recv_mb_id`, `me_send_mb_id`, `me_send_datetime`, `me_memo`, `me_read_datetime`, `me_type`, `me_send_ip`)
            VALUES
            ('{$memoMemberId}', '{$memoMemberId}', '{$memoDate}', 'Passkeys 플러그인 업데이트 완료. Version: {$memoVersion}', '0000-00-00 00:00:00', 'recv', '::1')
        ");
        $memoCount = get_memo_not_read($memoMemberId);
        sql_query("UPDATE `{$g5tables['member_table']}` SET `mb_memo_cnt` = {$memoCount} WHERE `mb_id` = '{$memoMemberId}'");
    }
}
