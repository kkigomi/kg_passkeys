<?php

namespace Kkigomi\Plugin\Passkeys\Traits;

trait SessionTrait
{
    private static string $sessionKeyLogin = 'ss_mb_id';
    private static string $sessionKeyAuthenticated = 'kg_passkey_authenticated';
    private static string $sessionKeyCache = 'kg_passkey_authentication_cache';
    private static string $sessionKeyChallenge = 'kg_passkey_authentication_challenge';
    private static string $sessionKeyTemporal = 'kg_passkey_authentication_temporal';

    final private function clearPasskeysSession()
    {
        unset($_SESSION[static::$sessionKeyAuthenticated]);
        unset($_SESSION[static::$sessionKeyCache]);
        unset($_SESSION[static::$sessionKeyChallenge]);
        unset($_SESSION[static::$sessionKeyTemporal]);
    }

    final private function getAuthenticatedSesstion()
    {
        return $_SESSION[static::$sessionKeyAuthenticated] ?? null;
    }
    final private function getChallengeSession()
    {
        return $_SESSION[static::$sessionKeyChallenge] ?? null;
    }

    final private function getTemporalSession()
    {
        return $_SESSION[static::$sessionKeyTemporal] ?? null;
    }

    final private function getLoggedSession()
    {
        return $_SESSION[static::$sessionKeyLogin] ?? null;
    }

    public function isLogged(string $memberId): bool
    {
        return $this->getLoggedSession() === $memberId;
    }

    private function isPasskeyLogged(string $memberId)
    {
        $authenticated = $this->getAuthenticatedSesstion();

        if (!$authenticated) {
            return false;
        }

        if ($authenticated['member_id'] !== $memberId) {
            return false;
        }

        return true;
    }

    private function validateSessionAuth(?string $memberId = null): bool
    {
        $temporalSession = $this->getTemporalSession();

        if (!$temporalSession) {
            return false;
        }

        if ($memberId && $temporalSession['memberId'] !== $memberId) {
            return false;
        }

        // 인증 세션 만료 확인
        if (isset($temporalSession['expires']) && $temporalSession['expires'] < \G5_SERVER_TIME) {
            // 만료된 세션 제거
            unset($temporalSession);

            return false;
        }

        return true;
    }
}
