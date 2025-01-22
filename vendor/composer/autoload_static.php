<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7621e6c56e8af2bb6e4b9df891dca530
{
    public static $prefixLengthsPsr4 = array (
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
        'J' => 
        array (
            'JohannSchopplich\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
        'JohannSchopplich\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/classes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'JohannSchopplich\\Lingohub\\Content' => __DIR__ . '/../..' . '/src/classes/Lingohub/Content.php',
        'JohannSchopplich\\Lingohub\\Lingohub' => __DIR__ . '/../..' . '/src/classes/Lingohub/Lingohub.php',
        'JohannSchopplich\\Lingohub\\Model' => __DIR__ . '/../..' . '/src/classes/Lingohub/Model.php',
        'JohannSchopplich\\Lingohub\\Multipart' => __DIR__ . '/../..' . '/src/classes/Lingohub/Multipart.php',
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7621e6c56e8af2bb6e4b9df891dca530::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7621e6c56e8af2bb6e4b9df891dca530::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7621e6c56e8af2bb6e4b9df891dca530::$classMap;

        }, null, ClassLoader::class);
    }
}
