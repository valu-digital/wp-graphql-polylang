<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit802095370b1b2f0d9a1f93f0b0d29fa7
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPGraphQL\\Extensions\\Polylang\\' => 30,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPGraphQL\\Extensions\\Polylang\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'WPGraphQL\\Extensions\\Polylang\\Helpers' => __DIR__ . '/../..' . '/src/Helpers.php',
        'WPGraphQL\\Extensions\\Polylang\\LanguageRootQueries' => __DIR__ . '/../..' . '/src/LanguageRootQueries.php',
        'WPGraphQL\\Extensions\\Polylang\\MenuItem' => __DIR__ . '/../..' . '/src/MenuItem.php',
        'WPGraphQL\\Extensions\\Polylang\\PolylangTypes' => __DIR__ . '/../..' . '/src/PolylangTypes.php',
        'WPGraphQL\\Extensions\\Polylang\\PostObject' => __DIR__ . '/../..' . '/src/PostObject.php',
        'WPGraphQL\\Extensions\\Polylang\\StringsTranslations' => __DIR__ . '/../..' . '/src/StringsTranslations.php',
        'WPGraphQL\\Extensions\\Polylang\\TermObject' => __DIR__ . '/../..' . '/src/TermObject.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit802095370b1b2f0d9a1f93f0b0d29fa7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit802095370b1b2f0d9a1f93f0b0d29fa7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit802095370b1b2f0d9a1f93f0b0d29fa7::$classMap;

        }, null, ClassLoader::class);
    }
}
