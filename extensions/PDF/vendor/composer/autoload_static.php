<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6006a4be4713ab13c9e9035dee625c1d
{
    public static $prefixLengthsPsr4 = array (
        'h' => 
        array (
            'h4cc\\WKHTMLToPDF\\' => 17,
        ),
        'S' => 
        array (
            'Symfony\\Component\\Process\\' => 26,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'K' => 
        array (
            'Knp\\Snappy\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'h4cc\\WKHTMLToPDF\\' => 
        array (
            0 => __DIR__ . '/..' . '/h4cc/wkhtmltopdf-amd64',
        ),
        'Symfony\\Component\\Process\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/process',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Knp\\Snappy\\' => 
        array (
            0 => __DIR__ . '/..' . '/knplabs/knp-snappy/src/Knp/Snappy',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6006a4be4713ab13c9e9035dee625c1d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6006a4be4713ab13c9e9035dee625c1d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
