<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit042bfe5609a6774f561acf783e70c8ad
{
    public static $files = array (
        'a52945c8ec9235b32cfb0428f92b265a' => __DIR__ . '/../..' . '/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'l' => 
        array (
            'lbuchs\\WebAuthn\\' => 16,
        ),
        'K' => 
        array (
            'Kkigomi\\Plugin\\Passkeys\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'lbuchs\\WebAuthn\\' => 
        array (
            0 => __DIR__ . '/..' . '/lbuchs/webauthn/src',
        ),
        'Kkigomi\\Plugin\\Passkeys\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit042bfe5609a6774f561acf783e70c8ad::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit042bfe5609a6774f561acf783e70c8ad::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit042bfe5609a6774f561acf783e70c8ad::$classMap;

        }, null, ClassLoader::class);
    }
}
