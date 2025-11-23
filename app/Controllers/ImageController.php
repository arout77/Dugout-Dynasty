<?php

namespace App\Controllers;

use Core\BaseController;
use Core\Request;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\ServerFactory;
use Twig\Environment;

class ImageController extends BaseController
{
    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );
    }

    /**
     * Handles dynamic image resizing and serving.
     * @param Request $request
     * @param string $path The path to the image.
     */
    public function show( Request $request, string $path )
    {
        $rootPath = dirname( __DIR__, 2 );

        // Setup Glide server
        $server = ServerFactory::create( [
            // Source: Where your original, high-quality images are stored.
            'source' => $rootPath . '/public/images/',

            // Cache: Where Glide will store the resized/compressed images.
            // This directory MUST be writable by your web server.
            'cache' => $rootPath . '/storage/cache/images/',

            // Use Symfony's response factory to correctly serve the image
            'response' => new SymfonyResponseFactory(),
        ] );

        // Get all query parameters from the request (e.g., w, h, q)
        $params = $request->getQueryParams();

        // Let Glide handle the rest: find, process, cache, and output the image.
        // It will automatically set the correct content-type headers.
        $server->outputImage( $path, $params );
    }
}
