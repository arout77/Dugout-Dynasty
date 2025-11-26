<?php

use App\Controllers\DraftController;
use App\Controllers\GameController;
use Core\Router;

// --- DRAFT API ---
Router::get( '/api/draft/players', [DraftController::class, 'getPlayers'] )->middleware( 'auth' );
Router::post( '/api/draft/pick', [DraftController::class, 'makePick'] )->middleware( 'auth' );
Router::get( '/api/draft/my-roster', [DraftController::class, 'getMyRoster'] )->middleware( 'auth' );
Router::post( '/api/draft/finish', [DraftController::class, 'finishDraft'] )->middleware( 'auth' );

// NEW: Ticker Route
Router::get( '/api/draft/recent-picks', [DraftController::class, 'getRecentPicks'] )->middleware( 'auth' );

Router::get( '/api/draft/roster-count', function () {
    $roster = new \App\Models\Roster();
    $count  = $roster->getCountByTeam( \Core\Session::get( 'user_team_id' ) );
    return ( new \Core\Response() )->json( ['count' => $count] );
} )->middleware( 'auth' );

// --- GAMEPLAY API ---
Router::post( '/api/game/sim-at-bat', [GameController::class, 'simAtBat'] )->middleware( 'auth' );
Router::post( '/api/game/substitute', [GameController::class, 'substitute'] );
