<?php

namespace App;

use Igorw\Silex\ConfigServiceProvider;
use Silex\Application as Silex;
use Silex\ServiceProviderInterface;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class Application extends Silex
{
    use Silex\MonologTrait;
    use Silex\SecurityTrait;
    use Silex\SwiftmailerTrait;
    use Silex\TwigTrait;

    public function __construct(array $values = [])
    {
        $values['paths']['base']     = realpath(__DIR__ . '/..');
        $values['paths']['webroot']  = realpath(__DIR__ . '/../../webroot');
        $values['paths']['fixtures'] = $values['paths']['base'] . '/fixtures';

        parent::__construct($values);
        $this->bootstrap();
    }

    public static function create(array $values = [])
    {
        return new static($values);
    }

    protected function bootstrap()
    {
        // Set ref to app.
        $app = $this;

        // Initializ E-mail service. (Must be before configuration is loaded)
        $app->register(new SwiftmailerServiceProvider());

        // Load configuration.
        $app->register(new ConfigServiceProvider($app['paths']['base'] . "/config.yml"));

        // Set ref to environment.
        $mode = $app['mode'];

        // Initialize logging.
        $app->register(
            new MonologServiceProvider(),
            [
                'monolog.logfile' => $app['paths']['base'] . "/log",
            ]
        );

        // Initialize link builder.
        $app->register(new UrlGeneratorServiceProvider());

        // Initialize templating eingine.
        $app->register(
            new TwigServiceProvider(),
            ['twig.path' => $app['paths']['base'] . "/templates"]
        );

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
                    'db_table'      => 'session',
                    'db_id_col'     => 'session_id',
                    'db_data_col'   => 'session_value',
                    'db_time_col'   => 'session_time',
                ],
                $app['session.storage.options']
            );
        });

        /* Remove this line to enable Security.
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
                        'form' => ['login_path' => '/user/login', 'check_path' => '/authenticate'],
                        'logout' => ['logout_path' => '/user/logout'],
                        'users' => $app->share(function ($app) {
                            return new UserProvider($app['db']);
                        }),
                        'anonymous' => true,
                    ],
                ],
            ]
        );

        $app['security.access_rules'] = [
            ['^/user/login$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
        ];

        // For password encryption.
        $app['security.encoder.digest'] = $app->share(function ($app) {
            return new BCryptPasswordEncoder($app['bcrypt.difficulty']);
        });
        /**/
    }
}
