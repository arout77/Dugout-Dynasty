<?php

// bootstrap.php

use App\Providers\EventServiceProvider;
use App\Services\NotificationService;
use Core\Cache;
use Core\Cache\CacheInterface;
use Core\Cache\FileCacheDriver;
use Core\Cache\RedisCacheDriver;
use Core\Container;
use Core\Events\EventDispatcher;
use Core\Mailer;
use Core\QueryLogger;
use Core\Session;
use Core\Validator;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Predis\Client as RedisClient;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// 1. Create a new Service Container instance.
$container = new Container();
$config    = require __DIR__ . '/config.php';

// --- EVENT DISPATCHER BINDING ---
$container->bind( EventDispatcher::class, function ( Container $c ) {
    $eventServiceProvider = new EventServiceProvider();
    return new EventDispatcher( $c, $eventServiceProvider->getListeners() );
} );

// --- DOCTRINE ENTITY MANAGER BINDING ---
$container->bind( EntityManager::class, function ( $container ) use ( $config ) {
    $paths     = [__DIR__ . '/app/Entities'];
    $isDevMode = ( $config['app_env'] ?? 'production' ) === 'development';

    // Create the SQL logger
    $sqlLogger = new QueryLogger();
    $container->bind( QueryLogger::class, fn() => $sqlLogger );

    $cache          = $isDevMode ? new ArrayAdapter() : new FilesystemAdapter( '', 0, __DIR__ . '/storage/cache/doctrine' );
    $doctrineConfig = ORMSetup::createAttributeMetadataConfiguration( $paths, $isDevMode, null, $cache );

    $doctrineConfig->setSQLLogger( $sqlLogger );

    $dbParams = [
        'driver'   => 'pdo_mysql',
        'host'     => $_ENV['DB_HOST'],
        'user'     => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS'],
        'dbname'   => $_ENV['DB_NAME'],
        'charset'  => 'utf8mb4',
    ];

    $connection = DriverManager::getConnection( $dbParams, $doctrineConfig );
    return new EntityManager( $connection, $doctrineConfig );
} );

// --- CACHE SYSTEM BINDING ---
$container->bind( CacheInterface::class, function () use ( $config ) {
    if ( $config['cache']['driver'] === 'redis' ) {
        $redisClient = new RedisClient( [
            'scheme'   => 'tcp',
            'host'     => $config['redis']['host'],
            'port'     => $config['redis']['port'],
            'password' => $config['redis']['password'] ?: null,
        ] );
        return new RedisCacheDriver( $redisClient );
    }
    return new FileCacheDriver();
} );

$container->bind( Cache::class, function ( Container $c ) {
    return new Cache( $c->resolve( CacheInterface::class ) );
} );

// --- TWIG BINDING ---
$container->bind( Environment::class, function ( Container $c ) use ( $config ) {
    $activeTheme = $config['theme'] ?? 'default';
    $paths       = [];

    // The active theme path is always the first priority.
    $activeThemePath = __DIR__ . '/views/themes/' . $activeTheme;
    if ( is_dir( $activeThemePath ) ) {
        $paths[] = $activeThemePath;
    }

    // If the active theme is not the default, add the default theme as a fallback.
    $defaultThemePath = __DIR__ . '/views/themes/default';
    if ( $activeTheme !== 'default' && is_dir( $defaultThemePath ) ) {
        $paths[] = $defaultThemePath;
    }

    // If for some reason no paths were added (e.g., bad config), fallback to default.
    if ( empty( $paths ) && is_dir( $defaultThemePath ) ) {
        $paths[] = $defaultThemePath;
    }

    if ( empty( $paths ) ) {
        throw new \Exception( "No valid theme directory found. Please check your configuration." );
    }

    $loader = new FilesystemLoader( $paths );

    // --- TWIG CACHING ENABLED ---
    $isDevelopment = ( $config['app_env'] === 'development' );
    $twigOptions   = [
        'debug' => $isDevelopment,
        'cache' => __DIR__ . '/storage/cache/twig', // The path to the cache directory
        'auto_reload' => $isDevelopment, // In dev, automatically recompile templates if they change
    ];

    $twig = new Environment( $loader, $twigOptions );
    $twig->addGlobal( 'app_url', $_ENV['APP_URL'] ?? '' );
    $twig->addGlobal( 'app_env', $_ENV['APP_ENV'] ?? 'production' );
    $auth = [
        'check' => Session::has( 'user_id' ),
        'user'  => Session::has( 'user_id' ) ? ( new \App\Models\User() )->getUserById( Session::get( 'user_id' ) ) : null,
    ];
    $twig->addGlobal( 'auth', $auth );
    $twig->addGlobal( 'base_url', $_ENV['APP_URL'] . $_ENV['APP_BASE_URL'] );
    // --- LAZY-LOADED FLASH MESSAGES ---
    // This object defers calling getFlash() until the template actually accesses the property (e.g., {{ flash.success }})
    $flash = new class {
        /**
         * @param $name
         */
        public function __get( $name )
        {
            // This magic method is called on first access in Twig, e.g., {{ flash.success }}
            // It retrieves and simultaneously removes the message from the session.
            return \Core\Session::getFlash( $name );
        }
        /**
         * @param $name
         */
        public function __isset( $name )
        {
            // This magic method is called for checks like {% if flash.success %}
            // It checks for the message without removing it.
            return \Core\Session::hasFlash( $name );
        }
    };
    $twig->addGlobal( 'flash', $flash );

    $cache = $c->resolve( Cache::class );
    $twig->addGlobal( 'update_available', $cache->get( 'update_available' ) );

    $twig->addFunction( new \Twig\TwigFunction( 'csrf_field', function () {
        $token = \Core\Session::csrfToken();
        return new \Twig\Markup( '<input type="hidden" name="_token" value="' . $token . '">', 'UTF-8' );
    } ) );
    return $twig;
} );

// --- OTHER CORE SERVICES ---
$container->bind( \App\Services\CareerService::class );
$container->bind( Mailer::class );
$container->bind( Validator::class, function ( Container $c ) {
    return new Validator( $c->resolve( EntityManager::class ) );
} );
$container->bind( \Core\Request::class, fn() => new \Core\Request() );
$container->bind( NotificationService::class, function ( Container $c ) {
    return new NotificationService( $c->resolve( Cache::class ) );
} );

// --- COMMAND BINDINGS ---
$container->bind( App\Commands\UpdateCommand::class, function () use ( $config ) {
    return new App\Commands\UpdateCommand( $config );
} );

$container->bind( App\Commands\CheckVersionCommand::class, function ( $c ) use ( $config ) {
    return new App\Commands\CheckVersionCommand(
        $config,
        $c->resolve( Mailer::class ),
        $c->resolve( Cache::class )
    );
} );

$container->bind( App\Commands\CacheClearCommand::class, function ( $c ) {
    return new App\Commands\CacheClearCommand( $c->resolve( Cache::class ) );
} );

// 3. Return the fully configured container.
return $container;
