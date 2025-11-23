<?php

/**
 * Rhapsody Framework
 *
 * Front Controller
 *
 * This file is the single entry point for all requests. It's responsible for
 * bootstrapping the application, setting up error handling, the service container,
 * and handing the request off to the router.
 */

// 1. Register the Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// --- START DEBUG COLLECTOR ---
\Core\Debug::getInstance()->start();

// --- ADD MAINTENANCE MODE CHECK ---
$maintenanceFile = __DIR__ . '/storage/framework/down';
if ( file_exists( $maintenanceFile ) ) {
    http_response_code( 503 );
    echo "<h1>Be right back.</h1><p>We are currently performing scheduled maintenance. Please check back soon.</p>";
    exit();
}
// --- END MAINTENANCE MODE CHECK ---

// 2. Define the project root path for reliability
$rootPath = dirname( __FILE__ );

// 3. Load environment variables from the .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable( $rootPath );
    $dotenv->load();
} catch ( \Dotenv\Exception\InvalidPathException $e ) {
    die( 'Could not find .env file. Please ensure it exists in the project root: ' . $rootPath );
}

// 4. Register Error Handling (Whoops)
// This provides beautiful, detailed error pages during development but
// should be disabled in a production environment for security.
$config = require_once $rootPath . '/config.php';
if ( $config['app_env'] === 'development' ) {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler( new \Whoops\Handler\PrettyPageHandler );
    $whoops->register();
}

// 5. Start the session
// This makes the $_SESSION superglobal available for our authentication system.
\Core\Session::start();

// 6. Bootstrap the application and get the service container
// This is the core of the dependency injection system. The container
// now knows how to build all our core services.
$container = require_once $rootPath . '/bootstrap.php';

// 7. Use necessary core classes
use Core\Request;
use Core\Router;

// 8. Create the Request object
// This object encapsulates all information about the incoming HTTP request.
$request = new Request();

// 9. Load the application routes from cache if available
$routeCachePath = $rootPath . '/storage/cache/routes/routes.php';
if ( file_exists( $routeCachePath ) && $config['app_env'] === 'production' ) {
    $routes = require_once $routeCachePath;
    Router::setRoutes( $routes );
} else {
    require_once $rootPath . '/routes/web.php';
    require_once $rootPath . '/routes/api.php';
}

// 10. Dispatch the request through the router, passing the container
// The router will execute global middleware (like CSRF), find the matching route,
// execute its specific middleware (like auth), and finally use the container
// to build and run the controller.
$response     = Router::dispatch( $request, $container );
$matchedRoute = Router::getMatchedRoute();

// --- INJECT DEBUG TOOLBAR ---
if ( $config['app_env'] === 'development' ) {
    // Get the headers from the response
    $headers = $response->getHeaders();
    // Default to 'text/html' if no content type is set
    $contentType = $headers['Content-Type'] ?? 'text/html';

    // ONLY inject the toolbar if this is an HTML response.
    // This prevents breaking our JSON API responses.
    if ( str_contains( $contentType, 'text/html' ) ) {
        $debug = \Core\Debug::getInstance();
        $debug->end( $response, $config, $container, $matchedRoute ); // Pass final data to the collector
        $toolbar     = new \Core\Toolbar( $debug->getData() );
        $toolbarHtml = $toolbar->render();

        $content = $response->getContent();
        // Inject toolbar before closing body tag, or append if not found
        $bodyEndPosition = strripos( $content, '</body>' );
        if ( $bodyEndPosition !== false ) {
            $content = substr_replace( $content, $toolbarHtml, $bodyEndPosition, 0 );
        } else {
            $content .= $toolbarHtml;
        }
        $response->setContent( $content );

        // --- Inject update notifications if available (in development) ---

        /** @var \App\Services\NotificationService $notificationService */
        $notificationService = $container->resolve( \App\Services\NotificationService::class );
        $response            = $notificationService->injectBanner( $response );
    }
}

// 11. Send the response back to the client
$response->send();
