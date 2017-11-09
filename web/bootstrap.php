<?php
use Silex\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

// Config setting
$app['resources'] = Yaml::parse(file_get_contents(__DIR__ . '/../mvc/config/resources.yml'));

// Debug setting
$app['debug'] = $app['resources']['debug'];

// nativeDB
$app['nativeDB'] = new \Model\NativeDB($app);

// View setting
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    "twig.path" => __DIR__ . '/../mvc/src/View',
    "twig.options" => array(
        //'cache' => __DIR__ . '/../mvc/config/cache',
        'strict_variables' => true
    )
));

// Assetic setting
$app->register(new Silex\Provider\AssetServiceProvider(), array(
    'assets.version' => 'v1',
    // 'assets.version_format' => '%s?version=%s',
    // 'assets.named_packages' => array(
        // 'css' => array('version' => 'css2', 'base_path' => '/whatever-makes-sense'),
        // 'images' => array('base_urls' => array('https://img.example.com')),
    // ),
));

// Translation setting
$app->register(new Silex\Provider\LocaleServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => array('fr'),
));
$app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new Symfony\Component\Translation\Loader\YamlFileLoader());

    $translator->addResource('yaml', __DIR__ . '/../mvc/config/locales/en.yml', 'en');
    $translator->addResource('yaml', __DIR__ . '/../mvc/config/locales/fr.yml', 'fr');

    return $translator;
});

// Doctrine setting
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => [
        'driver'        => $app['resources']['database']['driver'],
        'host'          => $app['resources']['database']['host'],
        'dbname'        => $app['resources']['database']['name'],
        'user'          => $app['resources']['database']['user'],
        'password'      => $app['resources']['database']['password'],
        'charset'       => 'utf8',
        'driverOptions' => [
            1002 => 'SET NAMES utf8',
        ],
    ],
));

// Doctrine ORM setting
$app->register(new Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), array(
    'orm.proxies_dir'             => __DIR__ . '/../mvc/src/Entity/Proxy',
    'orm.auto_generate_proxies'   => $app['debug'],
    'orm.em.options'              => [
        'mappings' => [
            [
                'type'                         => 'annotation',
                'namespace'                    => 'Entity\\',
                'path'                         => __DIR__ . '/../mvc/src/Entity',
                'use_simple_annotation_reader' => false,
            ],
        ],
    ]
));

// ErrorHandler setting
$app->error(function (\Exception $e, $code) {
    // die('Write exception to database: ' . $e->getMessage());
    // serve some error page to the user
    // ok
});

// Middleware setting
$app->before(function (Request $request, Application $app) {});
$app->after(function (Request $request, Response $response) {});
$app->finish(function (Request $request, Response $response) {});

// Routing setting
$app->get('/', '\Controller\DefaultController::indexAction')->bind('index');

// Run Application
if (php_sapi_name() !== 'cli') {
    $app->run();
}
