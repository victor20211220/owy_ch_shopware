<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf575909d24bb1fcee7e96483969c4fd4
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Wallee\\Sdk\\' => 11,
        ),
        'C' => 
        array (
            'CwbWalleePayment6\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Wallee\\Sdk\\' => 
        array (
            0 => __DIR__ . '/..' . '/wallee/sdk/lib',
        ),
        'CwbWalleePayment6\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitf575909d24bb1fcee7e96483969c4fd4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf575909d24bb1fcee7e96483969c4fd4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf575909d24bb1fcee7e96483969c4fd4::$classMap;

        }, null, ClassLoader::class);
    }
}