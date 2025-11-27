<?php

use App\Controllers\AuthController;
use App\Controllers\ClubhouseController;
use App\Controllers\DocsController;
use App\Controllers\DraftController;
use App\Controllers\GameController;
use App\Controllers\PageController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use Core\Router;

// Define your application routes using the static Router methods.

// --- LEAGUE ROUTES ---
Router::post( '/league/simulate-day', [\App\Controllers\LeagueController::class, 'simulateDay'] )->middleware( 'auth' );

// --- CLUBHOUSE ROUTES ---
Router::get( '/clubhouse', [ClubhouseController::class, 'index'] )->middleware( 'auth' );
Router::post( '/api/clubhouse/save', [ClubhouseController::class, 'saveStrategy'] )->middleware( 'auth' );

// --- GAME ROUTES ---
Router::get( '/new-game', [DraftController::class, 'setup'] )->middleware( 'auth' );
Router::post( '/new-game', [DraftController::class, 'createGame'] )->middleware( 'auth' );
Router::get( '/draft', [DraftController::class, 'index'] )->middleware( 'auth' );

// --- GAMEPLAY ROUTES ---
Router::get( '/pregame', [GameController::class, 'pregame'] )->middleware( 'auth' );
Router::post( '/start-game', [GameController::class, 'startGame'] )->middleware( 'auth' );
Router::get( '/play-ball', [GameController::class, 'playBall'] )->middleware( 'auth' );
Router::post( '/api/game/sim-at-bat', [GameController::class, 'simAtBat'] )->middleware( 'auth' );
Router::get( '/game/boxscore/{id}', [GameController::class, 'showBoxScore'] )->middleware( 'auth' );

// Free Agency
Router::get( '/free-agency', [\App\Controllers\FreeAgencyController::class, 'index'] )->middleware( 'auth' );
Router::post( '/free-agency/sign', [\App\Controllers\FreeAgencyController::class, 'signPlayer'] )->middleware( 'auth' );

// --- The routes below can be viewed by visitors and logged in users
// --- DOCUMENTATION ROUTES ---
Router::get( '/docs', [DocsController::class, 'index'] );
Router::get( '/docs/caching', [DocsController::class, 'performance'] );
Router::get( '/docs/cli', [DocsController::class, 'cli'] );
Router::get( '/docs/controllers', [DocsController::class, 'controllers'] );
Router::get( '/docs/doctrine', [DocsController::class, 'doctrine'] );
Router::get( '/docs/events', [DocsController::class, 'events'] );
Router::get( '/docs/file-uploader', [DocsController::class, 'fileUploader'] );
Router::get( '/docs/image-processing', [DocsController::class, 'imageProcessing'] );
Router::get( '/docs/installation', [DocsController::class, 'installation'] );
Router::get( '/docs/logging', [DocsController::class, 'logging'] );
Router::get( '/docs/mailer', [DocsController::class, 'mailer'] );
Router::get( '/docs/middleware', [DocsController::class, 'middleware'] );
Router::get( '/docs/models', [DocsController::class, 'models'] );
Router::get( '/docs/pagination', [DocsController::class, 'pagination'] );
Router::get( '/docs/request', [DocsController::class, 'request'] );
Router::get( '/docs/response', [DocsController::class, 'response'] );
Router::get( '/docs/routing', [DocsController::class, 'routing'] );
Router::get( '/docs/security', [DocsController::class, 'security'] );
Router::get( '/docs/seo', [DocsController::class, 'seo'] );
Router::get( '/docs/updating', [DocsController::class, 'updating'] );
Router::get( '/docs/validation', [DocsController::class, 'validation'] );
Router::get( '/docs/views', [DocsController::class, 'views'] );

Router::get( '/', [PageController::class, 'index'] );
Router::get( '/about', [PageController::class, 'about'] );
Router::get( '/contact', [App\Controllers\PageController::class, 'contact'] );
Router::post( '/contact', [App\Controllers\PageController::class, 'handleContact'] );

Router::get( '/sitemap.xml', [SitemapController::class, 'generate'] );

Router::get( '/logout', [AuthController::class, 'logout'] );

// This will match URLs like /posts/hello-world or /posts/123
Router::get( '/posts/{slug}', [PageController::class, 'showPost'] );

// --- PROTECTED ROUTES ---
// This route should only be accessible to authenticated users.
Router::get( '/dashboard', [PageController::class, 'dashboard'] )->middleware( 'auth' );
Router::get( '/upload', [App\Controllers\PageController::class, 'showUploadForm'] )->middleware( 'auth' );
Router::post( '/upload', [App\Controllers\PageController::class, 'handleUpload'] )->middleware( 'auth' );
Router::get( '/users', [PageController::class, 'showUsers'] );
Router::get( '/users/{user_id}', [PageController::class, 'viewUser'] );

// These routes should only be accessible to guests.
Router::get( '/login', [AuthController::class, 'showLoginForm'] )->middleware( 'guest' );
Router::post( '/login', [AuthController::class, 'login'] )->middleware( 'guest' );
Router::get( '/register', [AuthController::class, 'showRegisterForm'] )->middleware( 'guest' );
Router::post( '/register', [AuthController::class, 'register'] )->middleware( 'guest' );
