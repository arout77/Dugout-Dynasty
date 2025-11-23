<?php

namespace Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\Middleware;
use App\Middleware\VerifyCsrfTokenMiddleware;

/**
 * The Rhapsody Router.
 *
 * This class is responsible for matching incoming requests to controller actions,
 * executing middleware, and using the service container to resolve controllers
 * and their dependencies.
 */
class Router
{
    /**
     * The collection of registered routes.
     * @var Route[]
     */
    protected static array $routes = [];

    /**
     * A map of middleware keys to their fully qualified class names.
     * This allows for using short, memorable keys in route definitions.
     * @var array
     */
    protected static array $middlewareMap = [
        'auth'  => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
    ];

    // --- ADD GLOBAL MIDDLEWARE ---
    protected static array $globalMiddleware = [
        VerifyCsrfTokenMiddleware::class,
    ];

    /**
     * The route that was successfully matched.
     * @var Route|null
     */
    protected static ?Route $matchedRoute = null;

    /**
     * Adds a new route to the collection.
     *
     * @param string $method The HTTP method.
     * @param string $path The URI path.
     * @param mixed $callback The controller action or closure.
     * @return Route The newly created route object, to allow for method chaining.
     */
    protected static function add( string $method, string $path, mixed $callback ): Route
    {
        // We now create Route objects directly. This is needed for var_export to work.
        $route          = new Route( $method, $path, $callback );
        self::$routes[] = $route;
        return $route;
    }

    /**
     * Registers a GET route.
     */
    public static function get( string $path, mixed $callback ): Route
    {
        return self::add( 'get', $path, $callback );
    }

    /**
     * Registers a POST route.
     */
    public static function post( string $path, mixed $callback ): Route
    {
        return self::add( 'post', $path, $callback );
    }

    /**
     * Finds the matching route, executes its middleware, and dispatches it.
     * This is the main entry point for the router.
     *
     * @param Request $request The incoming request object.
     * @param Container $container The application's service container.
     * @return Response
     */
    public static function dispatch( Request $request, Container $container ): Response
    {
        foreach ( self::$globalMiddleware as $middlewareClass ) {
            $middleware = $container->resolve( $middlewareClass );
            $middleware->handle( $request );
        }

        $path   = $request->getPath();
        $method = $request->getMethod();

        foreach ( self::$routes as $route ) {
            if ( $route->matches( $method, $path ) ) {
                self::$matchedRoute = $route;

                // Resolve and execute any middleware attached to the route.
                $middlewareKey = $route->getMiddleware();
                if ( $middlewareKey ) {
                    $middlewareClass = self::$middlewareMap[$middlewareKey] ?? null;
                    if ( $middlewareClass && class_exists( $middlewareClass ) ) {
                        $middleware = new $middlewareClass(); // Middleware are simple enough to be new'ed up.
                        $middleware->handle( $request );
                    }
                }

                // If middleware didn't exit, execute the main controller action.
                return self::execute( $route, $request, $container );
            }
        }

        return self::handleNotFound();
    }

    /**
     * Executes the controller action for a matched route.
     * This method uses the service container to build the controller,
     * which automatically injects all dependencies.
     *
     * @param Route $route The matched route object.
     * @param Request $request The incoming request object.
     * @param Container $container The application's service container.
     * @return Response
     */
    protected static function execute( Route $route, Request $request, Container $container ): Response
    {
        $callback = $route->getCallback();
        $params   = $route->getParams();

        if ( is_array( $callback ) ) {
            $controllerClass = $callback[0];
            $action          = $callback[1];

            // Use the container to build the controller. This is the core of DI.
            $controller = $container->resolve( $controllerClass );

            // Call the action, passing the request and any URL parameters.
            return $controller->{$action}( $request, ...$params );
        }

        if ( $callback instanceof \Closure ) {
            $response = new Response();
            $response->setContent( call_user_func( $callback, $request, ...$params ) );
            return $response;
        }

        return self::handleNotFound();
    }

    /**
     * Handles the case where no route is found.
     */
    protected static function handleNotFound(): Response
    {
        $response = new Response();
        $response->setStatusCode( 404 );
        $response->setContent( "<h1>404 Not Found</h1>" );
        return $response;
    }

    /**
     * Returns the last successfully matched route.
     */
    public static function getMatchedRoute(): ?Route
    {
        return self::$matchedRoute;
    }

    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * @param array $routes
     */
    public static function setRoutes( array $routes ): void
    {
        self::$routes = $routes;
    }
}
