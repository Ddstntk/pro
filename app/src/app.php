<?php

/**
 * PHP Version 5.6
 * App.php
 *
 * @category  Social_Network
 *
 * @author    Konrad Szewczuk <konrad3szewczuk@gmail.com>
 *
 * @copyright 2018 Konrad Szewczuk
 *
 * @license   https://opensource.org/licenses/MIT MIT license
 *
 * @link      cis.wzks.uj.edu.pl/~16_szewczuk
 */


use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Sorien\Provider\DoctrineProfilerServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Service\userTokenService;
use Repository\UserRepository;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());

/**
 * TWIG
 */
$app->register(
    new TwigServiceProvider(),
    [
        'twig.path' => dirname(dirname(__FILE__)).'/templates',
    ]
);

$app->register(new HttpFragmentServiceProvider());

$app['config.photos_directory'] = __DIR__.'/../web/uploads/photos';
$app['config.download_photos_directory'] = '/uploads/photos';


$app['twig'] = $app->extend(
    'twig',
    function ($twig, $app) {
        // add custom globals, filters, tags, ...
        $twig->addGlobal(
            'photos_directory',
            $app['config.photos_directory']
        );
        $twig->addGlobal(
            'download_photos_directory',
            $app['config.download_photos_directory']
        );

        return $twig;
    }
);


/**
 * TŁUMACZENIA
 */
$app->register(new LocaleServiceProvider());
$app->register(
    new TranslationServiceProvider(),
    [
        'locale' => 'pl',
        'locale_fallbacks' => array('en'),
    ]
);
$app->extend(
    'translator',
    function ($translator, $app) {
        $translator
            ->addResource('xliff', __DIR__.'/../translations/messages.en.xlf', 'en', 'messages');
        $translator
            ->addResource('xliff', __DIR__.'/../translations/validators.en.xlf', 'en', 'validators');
        $translator
            ->addResource('xliff', __DIR__.'/../translations/messages.pl.xlf', 'pl', 'messages');
        $translator
            ->addResource('xliff', __DIR__.'/../translations/validators.pl.xlf', 'pl', 'validators');

        return $translator;
    }
);

/**
 * BAZA DANYCH
 */
$app->register(
    new DoctrineServiceProvider(),
    [
        'db.options' => [
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'projekt',
            'user'      => 'konrad',
            'password'  => 'password',
            'charset'   => 'utf8',
            'driverOptions' => [
                1002 => 'SET NAMES utf8',
            ],
        ],
    ]
);

$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());

$app->register(new SessionServiceProvider());
$app->register(
    new SecurityServiceProvider(),
    [
        'security.firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'pattern' => '^.*$',
                'form' => [
                    'login_path' => 'auth_login',
                    'check_path' => 'auth_login_check',
                    'default_target_path' => 'posts_index_paginated',
                    'username_parameter' => 'login_type[email]',
                    'password_parameter' => 'login_type[password]',
                ],
                'anonymous' => true,
                'logout' => [
                    'logout_path' => 'auth_logout',
                                    'target_url' => 'auth_login',
                ],
                'users' => function () use ($app) {
                    return new Provider\UserProvider($app['db']);
                },
            ],
        ],
        'security.access_rules' => [
            ['^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/admin', 'ROLE_ADMIN'],
            ['^.*$', 'ROLE_USER'],
        ],
        'security.role_hierarchy' => [
            'ROLE_ADMIN' => ['ROLE_USER'],
        ],
    ]
);

return $app;
