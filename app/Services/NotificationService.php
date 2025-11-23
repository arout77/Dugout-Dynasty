<?php

namespace App\Services;

use Core\Cache;
use Core\Response;

class NotificationService
{
    // The Cache manager is now injected
    /**
     * @param Cache $cache
     */
    public function __construct( protected Cache $cache )
    {}
    /**
     * Injects an update notification banner into an HTML response if an update is available.
     */
    public function injectBanner( Response $response ): Response
    {
        // Use the injected cache instance
        $newVersion = $this->cache->get( 'update_available' );

        if ( !$newVersion ) {
            return $response; // No update, do nothing
        }

        $content = $response->getContent();

        if ( !str_contains( $content, '</body>' ) ) {
            return $response;
        }

        $bannerHtml = $this->createBannerHtml( $newVersion );

        $modifiedContent = str_ireplace( '</body>', $bannerHtml . '</body>', $content );
        $response->setContent( $modifiedContent );

        return $response;
    }

    /**
     * @param string $version
     */
    private function createBannerHtml( string $version ): string
    {
        // Using inline styles to ensure the banner is always visible and styled correctly
        return <<<HTML
            <div id="rhapsody-update-banner" style="position: fixed; bottom: 0; left: 0; width: 100%; background-color: #1F2937; color: #F9FAFB; padding: 12px; z-index: 99999; display: flex; justify-content: center; align-items: center; font-family: sans-serif; font-size: 14px; border-top: 1px solid #4B5563;">
                <span class="bg-pink-700 text-white animate-pulse" style="font-weight: bold; font-size: 10px; padding: 2px 8px; border-radius: 9999px; margin-right: 12px;">UPDATE</span>
                A new version of Rhapsody is available! &nbsp;<strong>($version)</strong>
                <a href="#" id="update-notification-link" data-version="$version" style="color: #60A5FA; font-weight: bold; text-decoration: underline; margin-left: 16px;">View Details & Release Notes</a>
            </div>
        HTML;
    }
}
