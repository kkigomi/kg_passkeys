<?php

namespace Kkigomi\Plugin\Passkeys\Traits;

trait TableTrait
{
    public static function passkeyTables(): array
    {
        $prefix = self::g5TablePrefix() . 'kg_passkeys_';
        return [
            'credential' => $prefix . 'credential',
            'member' => $prefix . 'member'
        ];
    }

    public static function g5TablePrefix(): string
    {
        return \G5_TABLE_PREFIX;
    }

    public static function g5Tables(): array
    {
        global $g5;

        return array_filter($g5, function ($key) {
            if (function_exists('\str_ends_with')) {
                return \str_ends_with($key, '_table');
            }

            return substr_compare($key, '_table', -6) === 0;
        }, \ARRAY_FILTER_USE_KEY);
    }
}
