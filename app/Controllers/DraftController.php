<?php

namespace App\Controllers;

use App\Models\Hitter;
use App\Models\Pitcher;
use App\Models\Roster;
use App\Models\Team;
use App\Services\DraftStrategyService;
use App\Services\ScheduleService;
use Core\BaseController;
use Core\Request;
use Core\Response;
use Core\Session;
use Twig\Environment;

class DraftController extends BaseController
{
    protected Team $teamModel;
    protected Roster $rosterModel;
    protected Hitter $hitterModel;
    protected Pitcher $pitcherModel;
    protected DraftStrategyService $draftService;
    protected ScheduleService $scheduleService;

    /**
     * @param Environment $twig
     */
    public function __construct( Environment $twig )
    {
        parent::__construct( $twig );

        $this->teamModel       = new Team();
        $this->rosterModel     = new Roster();
        $this->hitterModel     = new Hitter();
        $this->pitcherModel    = new Pitcher();
        $this->draftService    = new DraftStrategyService();
        $this->scheduleService = new ScheduleService();
        $this->scheduleService->generateSchedule( 1 );
    }

    /**
     * Main Dashboard Entry Point
     */
    public function index(): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        if ( !$userTeamId ) {
            return $this->redirect( '/new-game' );
        }

        $teams       = $this->teamModel->getAllByDraftOrder();
        $userTeam    = $this->teamModel->findById( $userTeamId );
        $rosterCount = $this->rosterModel->getCountByTeam( $userTeamId );
        $status      = $this->draftService->advanceDraft( $userTeam['league_id'] ?? 1, count( $teams ) );

        // Get total teams for round calc
        $leagueState = $this->teamModel->getDb()->query( "SELECT total_teams FROM leagues WHERE league_id = 1" )->fetch();
        $totalTeams  = $leagueState['total_teams'] ?? 12;

        return $this->view( 'draft/dashboard.twig', [
            'teams'       => $teams,
            'userTeam'    => $userTeam,
            'rosterCount' => $rosterCount,
            'salaryCap'   => $userTeam['salary_cap'],
            'status'      => $status,
            'totalTeams'  => $totalTeams,
        ] );
    }

    /**
     * Show the New Game Setup Screen
     */
    public function setup(): Response
    {
        return $this->view( 'draft/setup.twig' );
    }

    // NEW: Fetch recent draft activity for ticker
    /**
     * @return mixed
     */
    public function getRecentPicks(): Response
    {
        $db = $this->teamModel->getDb();
        // Fetch last 30 picks with Team Names
        $sql = "SELECT r.player_name, r.position, r.salary, t.team_name, r.roster_id
                FROM rosters r
                JOIN teams t ON r.team_id = t.team_id
                ORDER BY r.roster_id DESC LIMIT 30";
        $picks = $db->query( $sql )->fetchAll( \PDO::FETCH_ASSOC );

        return $this->json( ['picks' => $picks] );
    }

    /**
     * Process the New Game Form
     */
    public function createGame( Request $request ): Response
    {
        // FAIL-SAFE: Use $_REQUEST directly
        $name       = $_REQUEST['team_name'] ?? '';
        $leagueSize = (int) ( $_REQUEST['league_size'] ?? 12 );
        $dhEnabled  = ( $_REQUEST['dh_rule'] ?? '' ) === 'on';

        if ( empty( $name ) ) {
            Session::setFlash( 'error', 'Team Name is required' );
            return $this->redirect( '/new-game' );
        }

        // 1. Initialize League (Clean slate for this MVP)
        $db = $this->teamModel->getDb();

        // FIX: Disable Foreign Key Checks temporarily so we can TRUNCATE
        $db->exec( "SET FOREIGN_KEY_CHECKS=0" );

        // Clear previous game data (Optional: strictly for single-save MVP)
        $db->exec( "TRUNCATE TABLE rosters" );
        $db->exec( "TRUNCATE TABLE teams" );
        $db->exec( "TRUNCATE TABLE leagues" );
        $db->exec( "TRUNCATE TABLE games" );

        // FIX: Re-enable Foreign Key Checks
        $db->exec( "SET FOREIGN_KEY_CHECKS=1" );

        // Create League
        $sql  = "INSERT INTO leagues (name, is_dh_enabled, total_teams, current_round, current_pick) VALUES (:name, :dh, :total, 1, 1)";
        $stmt = $db->prepare( $sql );
        $stmt->execute( [
            ':name'  => 'Dugout Dynasty League',
            ':dh'    => $dhEnabled ? 1 : 0,
            ':total' => $leagueSize,
        ] );
        $leagueId = $db->lastInsertId();

        // 2. Generate Draft Order (Random)
        $positions = range( 1, $leagueSize );
        shuffle( $positions );
        $userDraftPos = array_pop( $positions );

        // 3. Create User Team
        $userTeamData = [
            'name'               => $name,
            'is_user_controlled' => 1,
            'salary_cap'         => 80000000,
            'draft_order'        => $userDraftPos,
            'spending_archetype' => 'Balanced', // User decides their own fate
            'scouting_trait' => 'Analytics',
        ];
        $userTeamId = $this->teamModel->create( $userTeamData );
        Session::set( 'user_team_id', $userTeamId );

        // 4. Create CPU Teams
        // Classic Cities
        $cities = [
            'New York', 'Boston', 'Chicago', 'St. Louis', 'Los Angeles', 'San Francisco', 'Detroit',
            'Philadelphia', 'Pittsburgh', 'Cleveland', 'Cincinnati', 'Brooklyn', 'Baltimore', 'Washington',
            'Houston', 'Atlanta', 'Toronto', 'Seattle', 'Miami', 'Denver', 'Phoenix', 'San Diego',
            'Milwaukee', 'Minneapolis', 'Kansas City', 'Oakland', 'Tampa Bay', 'Dallas', 'Montreal',
            'Las Vegas', 'Portland', 'Nashville', 'Charlotte', 'Austin', 'Salt Lake City', 'Buffalo',
        ];

        // Classic & Modern Nicknames
        $nicknames = [
            'Pandas', 'Cardinals', 'Tigers', 'Lions', 'Barnstormers', 'Badgers', 'Isotopes',
            'Diamondbacks', 'Eagles', 'Stallions', 'Sharks', 'Blue Jays', 'Ravens', 'Titans',
            'Giants', 'Gators', 'Panthers', 'Raptors', 'Falcons', 'Bears', 'Hawks', 'Pirates',
            'Chiefs', 'Vikings', 'Rangers', 'Nationals', 'Cowboys', 'Colts', 'Indians',
        ];

        shuffle( $cities );
        shuffle( $nicknames );

        $archetypes = [Team::ARCHETYPE_STEINBRENNER, Team::ARCHETYPE_MONEYBALL, Team::ARCHETYPE_BALANCED];
        $traits     = [Team::TRAIT_SMALL_BALL, Team::TRAIT_POWER, Team::TRAIT_DEFENSE, Team::TRAIT_ANALYTICS];

        foreach ( $positions as $cpuDraftPos ) {
            $cityName = array_pop( $cities ) ?? 'Unknown City';
            $nickName = array_pop( $nicknames );

            $cpuName = "$cityName $nickName";

            $this->teamModel->create( [
                'name'               => $cpuName,
                'is_user_controlled' => 0,
                'salary_cap'         => 80000000,
                'draft_order'        => $cpuDraftPos,
                'spending_archetype' => $archetypes[array_rand( $archetypes )],
                'scouting_trait'     => $traits[array_rand( $traits )],
            ] );
        }

        Session::flash( 'success', "League initialized! You have the #{$userDraftPos} pick in the draft." );
        return $this->redirect( '/draft' );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPlayers( Request $request ): Response
    {
        $type   = $_REQUEST['type'] ?? 'batter';
        $pos    = $_REQUEST['pos'] ?? 'all';
        $sort   = $_REQUEST['sort'] ?? 'PRICE';
        $dir    = $_REQUEST['dir'] ?? 'DESC';
        $page   = (int) ( $_REQUEST['page'] ?? 1 );
        $limit  = 100;
        $offset = ( $page - 1 ) * $limit;

        // FIX: Capture search parameter
        $search = $_REQUEST['search'] ?? null;

        if ( $type === 'pitcher' ) {
            $filters = [];
            if ( $pos === 'SP' ) {
                $filters['role'] = 'SP';
            }

            if ( $pos === 'RP' ) {
                $filters['role'] = 'RP';
            }

            if ( $search ) {
                $filters['search'] = $search;
            }
            // Pass to Model

            $players = $this->pitcherModel->findAvailable( $limit, $offset, $sort, $dir, $filters );
        } else {
            $filters = [];
            if ( $pos !== 'all' ) {
                $filters['pos'] = $pos;
            }
            if ( $search ) {
                $filters['search'] = $search;
            }
            // Pass to Model

            $players = $this->hitterModel->findAvailable( $limit, $offset, $sort, $dir, $filters );

            foreach ( $players as &$p ) {
                if ( isset( $p['RUN'] ) ) {
                    $p['RUN'] = (int) $p['RUN'];
                }

                if ( isset( $p['row_id'] ) ) {
                    $p['id'] = $p['row_id'];
                }
            }
        }

        return $this->json( [
            'players' => $players,
            'page'    => $page,
            'count'   => count( $players ),
        ] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function makePick( Request $request ): Response
    {
        $input = json_decode( file_get_contents( 'php://input' ), true ) ?? $_REQUEST;

        $playerId   = $input['player_id'] ?? null;
        $playerType = $input['player_type'] ?? null;
        $teamId     = Session::get( 'user_team_id' );
        $leagueId   = 1;

        if ( !$playerId || !$teamId ) {
            return $this->json( ['error' => 'Invalid Request'], 400 );
        }

        // 1. Fetch the EXACT player using row_id (UNIQUE)
        // Since you added row_id to both tables, we use findByRowId for BOTH.
        if ( $playerType === 'pitcher' ) {
            $player = $this->pitcherModel->findByRowId( $playerId );
        } else {
            $player = $this->hitterModel->findByRowId( $playerId );
        }

        if ( !$player ) {
            // Log the ID that failed for debugging
            error_log( "DraftController: Player not found for row_id: $playerId" );
            return $this->json( ['error' => 'Player not found'], 404 );
        }

        // 2. Force the ID into the array to be safe for Roster::addPlayer
        // We store the UNIQUE row_id as the player identifier in the roster
        $player['ID'] = $playerId;
        $player['id'] = $playerId;
        // Explicitly set row_id in array too
        $player['row_id'] = $playerId;

        if ( $this->rosterModel->isNameDrafted( $player['NAME'] ) ) {
            return $this->json( ['error' => 'Player already drafted.'], 400 );
        }

        // Safe Salary parsing
        $price  = $player['PRICE'] ?? $player['Price'] ?? '0';
        $salary = (int) str_replace( [',', '$'], '', $price );

        $team = $this->teamModel->findById( $teamId );
        if ( $team['salary_cap'] < $salary ) {
            return $this->json( ['error' => 'Insufficient Funds.'], 400 );
        }

        try {
            $this->rosterModel->addPlayer( $teamId, $player, $playerType );
            $this->teamModel->deductCapSpace( $teamId, $salary );

            $db = $this->teamModel->getDb();
            $db->exec( "UPDATE leagues SET current_pick = current_pick + 1 WHERE league_id = $leagueId" );

            // 4. Run CPU Logic
            $totalTeams = count( $this->teamModel->getAllByDraftOrder() );
            $result     = $this->draftService->advanceDraft( $leagueId, $totalTeams );

            return $this->json( [
                'success'       => true,
                'remaining_cap' => $team['salary_cap'] - $salary,
                'draft_status'  => $result,
            ] );
        } catch ( \Exception $e ) {
            error_log( "DraftController::makePick Error: " . $e->getMessage() );
            return $this->json( ['error' => 'Draft failed: ' . $e->getMessage()], 500 );
        }
    }

    /**
     * API: Returns the user's full roster for the dashboard panel.
     */
    public function getMyRoster(): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        $roster     = $this->rosterModel->getPlayersByTeam( $userTeamId );
        return $this->json( ['roster' => $roster] );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function finishDraft( Request $request ): Response
    {
        $userTeamId = Session::get( 'user_team_id' );
        $leagueId   = 1; // TODO: Session

        // Validate user has a mostly full roster (e.g., 24+) before allowing skip
        // Or just allow it anytime if they're broke.
        $rosterCount = $this->rosterModel->getCountByTeam( $userTeamId );

        // Trigger massive simulation
        $totalTeams = count( $this->teamModel->getAllByDraftOrder() );

        // We tell the service to run until the END of the draft (pick 300+)
        // By default, advanceDraft stops when it hits a human turn.
        // We need a way to tell it "The human is done, skip me".
        // We can add a flag to the team 'draft_complete' or just handle it in the loop.

        // Better approach: Mark user team as "Auto-Draft" for the rest of the session?
        // Or just run a special loop here.

        $this->draftService->completeDraft( $leagueId, $totalTeams );

        // Generate Season Schedule
        $this->scheduleService->generateSchedule( $leagueId );

        return $this->json( ['success' => true, 'redirect' => '/pregame'] ); // Go to season start
    }
}
