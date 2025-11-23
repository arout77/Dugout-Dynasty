<?php

namespace App\Controllers;

use App\Models\User;
use Core\BaseController;
use Core\Response;
use Twig\Environment;

class SitemapController extends BaseController
{
    /**
     * @param User $userModel
     * @param Environment $twig
     */
    public function __construct(
        protected User $userModel,
        Environment $twig
    )
    {
        parent::__construct( $twig );
    }

    /**
     * @return mixed
     */
    public function generate(): Response
    {
        $users = $this->userModel->findAll();

        $baseUrl = $_ENV['APP_URL'] . ( $_ENV['APP_BASE_URL'] ?? '' );

        // Render a Twig template that is formatted as XML
        $xmlContent = $this->twig->render( 'sitemap.xml.twig', [
            'baseUrl' => $baseUrl,
            'users'   => $users,
        ] );

        $response = new Response();
        $response->setHeader( 'Content-Type', 'application/xml' );
        $response->setContent( $xmlContent );
        return $response;
    }
}
