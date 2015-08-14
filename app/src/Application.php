<?php

namespace App;

use App\User\UserProvider;
use Flint\Application as Flint;
use Silex\Application as Silex;
use Silex\ServiceProviderInterface;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class Application extends Flint
{
    use Silex\MonologTrait;
    use Silex\SecurityTrait;
    use Silex\SwiftmailerTrait;
    use Silex\TwigTrait;

    public function __construct($debug = false)
    {
        $basePath = realpath(__DIR__ . '/..');

        $params = [
            'paths' => [
                'base'      => $basePath,
                'templates' => $basePath . '/templates',
                'webroot'   => $basePath . '/../webroot',
                'config'    => $basePath . '/config',
                'logs'      => $basePath . '/var/logs',
                'cache'     => $basePath . '/var/cache',
            ],
        ];

        parent::__construct($basePath, $debug, $params);
        $this->bootstrap();
    }

    public static function create($debug = false)
    {
        return new static($debug);
    }

    protected function bootstrap()
    {
        // Set ref to app.
        $app = $this;

        // Initializ E-mail service. (Must be before configuration is loaded, otherwise defaults will override)
        $app->register(new SwiftmailerServiceProvider());

        // Load configuration.
        $app['config.loader']->setCacheDir($app['paths']['cache'] . '/config');
        $app->configure($app['paths']['config'] . '/config.yml');

        // Initialize logging.
        $app->register(
            new MonologServiceProvider(),
            [
                'monolog.logfile' => $app['paths']['logs'] . '/log',
            ]
        );

        // Initialize templating eingine.
        $app->register(
            new TwigServiceProvider(),
            ['twig.options' => array_merge($app['twig.options'], ['cache' => $app['paths']['cache'] . '/twig'])]
        );

        // Load twig paths.
        foreach ($app['twig.paths'] as $path) {
            $app['twig.loader.filesystem']->addPath($app['paths']['templates'] . $path['path'], $path['namespace']);
        }

        // Initialize validator.
        $app->register(new ValidatorServiceProvider());

        // Initialize Database.
        $app->register(
            new DoctrineServiceProvider(),
            ['db.options' => isset($app['testing']) ? $app['testdb.options'] : $app['db.options']]
        );

        // Initialize session manager.
        $app->register(new SessionServiceProvider());

        $app['session.storage.handler'] = $app->share(function () use ($app) {
            return new PdoSessionHandler(
                $app['db']->getWrappedConnection(),
                [
                    'db_table' => 'session',
                    'db_id_col' => 'sess_id',
                    'db_data_col' => 'sess_data',
                    'db_time_col' => 'sess_time',
                    'db_lifetime_col' => 'sess_lifetime',
                ],
                $app['session.storage.options']
            );
        });

        // /* Remove this line to enable Security.
        // Authentication and access control.
        $app->register(
            new SecurityServiceProvider(),
            [
                'security.firewalls' => [
                    'login_path' => [
                        'pattern' => '^/user/login$',
                        'anonymous' => true,
                    ],

                    'default' => [
                        'pattern' => '^.*$',
                        'form' => ['login_path' => '/login', 'check_path' => '/authenticate'],
                        'logout' => ['logout_path' => '/logout', 'invalidate_session' => true],
                        'users' => $app->share(function ($app) {
                            return new UserProvider($app);
                        }),
                        'anonymous' => true,
                    ],
                ],
            ]
        );

        $app['security.access_rules'] = [
            ['^/login$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
        ];

        // For password encryption.
        $app['security.encoder.digest'] = $app->share(function ($app) {
            return new BCryptPasswordEncoder($app['bcrypt.difficulty']);
        });
        /**/
    }
}
