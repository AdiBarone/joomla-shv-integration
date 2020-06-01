<?php

function debug($var) { echo "<pre>" . date("Y-m-d H:i:s") . " " . print_r( $var, 1 ) . "</pre>\n"; }

class PlgJobSHVGamesHelper
{

	private $SHV_API_KEY = null;
	private $SHV_CLUBS;
	private $SHV_CLUB_IDS = array();
	private $DB;
	private $DB_PREFIX;
	private $SHV_SEASON;
	private $SHV_SEASON_BEGIN;
	private $SHV_SEASON_END;
	private $FORCE = false;

	public function __construct($opts = array())
	{
		$this->SHV_API_KEY   = $opts['API_KEY'];
		$this->SHV_CLUBS     = ( is_array($opts['CLUBS']) ? $opts['CLUBS'] : array($opts['CLUBS']) );
		$this->CLUB_NAMES    = ( is_array($opts['NAMES']) ? $opts['NAMES'] : array($opts['NAMES']) );
		$this->connect();
	}

	public function setEnforce()
	{
		$this->FORCE = true;
	}

	private function connect()
	{
		include( __DIR__ . '/class.mysql.php' );
		$conf = JFactory::getConfig();
		$this->DB = new mysql($conf->get('host'), $conf->get('db'), $conf->get('user'), $conf->get('password'));

		$this->DB->query("set names utf8");
		$this->DB_PREFIX = $conf->get('dbprefix') . 'shv_';
		$this->DB_POSTFIX = '';

		$this->setSeasons();
	}

	private function setSeasons()
	{
		// set current season (if it's june, change to next season)
		if( date("n") >= 6 )
  			$this->SHV_SEASON = date("Y");
		else
  			$this->SHV_SEASON = date("Y") - 1;
	
		$this->SHV_SEASON_BEGIN = mktime(0,0,0,6,1, $this->SHV_SEASON);
		$this->SHV_SEASON_END   = mktime(0,0,0,6,1, $this->SHV_SEASON + 1);
	}

	private function getFromAPI($res,$data=array())
	{
  		$context = stream_context_create(
			array(
				'http' => array(
					'header'  => 'Authorization: Basic '. $this->SHV_API_KEY
				)
			)
		);

  		try
		{
			$json = file_get_contents('http://api.handball.ch/rest/v1'.$res, false, $context);
			return json_decode($json);
		}
		catch ( Exception $e )
		{
			debug("Error getting from API: ". $e->getMessage());
			return false;
		}
	}

	private function setClubIds()
	{
  		$all_clubs = $this->getFromAPI("/clubs");

		foreach ( $all_clubs as $club )
		{
			foreach ( $this->SHV_CLUBS as $name )
			{
				if( preg_match( "/$name/", $club->clubName ) )
				{
					$this->SHV_CLUB_IDS[] = $club->clubId;
				}
			}
		}
	}

	private function getTableName($tbl)
	{
		return $this->DB_PREFIX . $tbl . $this->DB_POSTFIX;
	}

	public function setTeams()
	{
		debug("updating teams");

		$this->setClubIds();

		foreach( $this->SHV_CLUB_IDS as $clubId )
		{
			$teams = $this->getFromAPI("/clubs/$clubId/teams");

			foreach( $teams as $team )
			{
				$update = $this->DB->getResult('
					select count(*)
					from '. $this->getTableName('teams') .'
					where id        = '.$this->DB->quote($team->teamId).'
				');

				$this->DB->query('
					'. ( $update ? 'update':'insert into') .' '. $this->getTableName('teams') .'
					set id        = '.$this->DB->quote($team->teamId).'
					  , season    = '.$this->SHV_SEASON.'
					  , league    = '.$this->DB->quote($team->groupText).'
					  , group_id  = '.$this->DB->quote($team->leagueId).'
					  , name      = '.$this->DB->quote($team->teamName).'
					  , timestamp = now()
					'. ( $update ? 'where id = '.$this->DB->quote($team->teamId) :'') .'
				');
			}
		}
	}

	private function getTeams()
	{
		return $this->DB->getRows('
			select *
			from '. $this->getTableName('teams') .'
			where season = '.$this->SHV_SEASON.'
		');
	}

	public function setGames()
	{
		debug("updating" . ( $this->FORCE ? " all" : "" ) . " games");

		foreach( $this->getTeams() as $team )
		{
			// getting future games
			$fields = 'gameId,gameNr,gameDateTime,gameTypeShort,leagueShort,teamAName,teamBName,teamAScoreHT,teamBScoreHT,teamAScoreFT,teamBScoreFT,venue';
			$games = $this->getFromAPI('/teams/'.$team['id'].'/games?order=desc&status=planned&fields='.$fields);

			// if we need all games, get played too
			if ( $this->FORCE )
			{
				$games = array_merge($games,
					$this->getFromAPI('/teams/'.$team['id'].'/games?order=desc&status=played&fields='.$fields)
				);
			}

			foreach( $games as $game )
			{
				$update = $this->DB->getResults('
					select id
					from '. $this->getTableName('games') .'
					where season  = '.$this->SHV_SEASON.'
					  and gamenr  = '.$this->DB->quote($game->gameId).'
				');

				$this->DB->query('
					'. ( $update ? 'update':'insert into') .' '. $this->getTableName('games') .'
					set season    = '.$this->SHV_SEASON.'
					  , gamenr    = '.$this->DB->quote($game->gameId).'
					  , prov      = '.$this->DB->quote( $game->gameNr == 0 ).'
					  , date      = '.$this->DB->quote(strtotime($game->gameDateTime)).'
					  , league    = '.$this->DB->quote($game->leagueShort).'
					  , team_id   = '.$this->DB->quote($team['id']).'
					  , type      = '.$this->DB->quote($game->gameTypeShort).'
					  , home      = '.$this->DB->quote(trim($game->teamAName)).'
					  , guest     = '.$this->DB->quote(trim($game->teamBName)).'
					  , hall      = '.$this->DB->quote(trim($game->venue)).'
					  , timestamp = now()
					'. ( $update ? 'where id = '.$this->DB->quote($update[0]) :'') .'
				');

			}
			
		}
	}

	public function setResults()
	{

		$open_results = $this->DB->getResult('
			select count(*)
			from '. $this->getTableName('games') .'
			where date < unix_timestamp(date_add( now(), interval 1 hour))
			  and date > unix_timestamp(date_sub( now(), interval 3 hour))
			  and result is null
		');

		if ( $open_results == 0 && !$this->FORCE )
		{
			// debug("no pending results to sync, skipping");
			return;
		}

		debug("updating results");

		foreach( $this->getTeams() as $team )
		{

			$games = $this->getFromAPI('/teams/'.$team['id'].'/games?order=desc&status=played&fields=gameId,gameDateTime,gameTypeShort,leagueShort,teamAName,teamBName,teamAScoreHT,teamBScoreHT,teamAScoreFT,teamBScoreFT,venue');

			foreach( $games as $game )
			{

				$update = $this->DB->getResults('
					select id
					from '. $this->getTableName('games') .'
					where season = '.$this->SHV_SEASON.'
					  and gamenr  = '.$this->DB->quote($game->gameId).'
				');

				$this->DB->query('
					'. ( $update ? 'update':'insert into') .' '. $this->getTableName('games') .'
					set season      = '.$this->SHV_SEASON.'
					  , date        = '.$this->DB->quote(strtotime($game->gameDateTime)).'
					  , league      = '.$this->DB->quote($game->leagueShort).'
					  , type        = '.$this->DB->quote($game->gameTypeShort).'
					  , home        = '.$this->DB->quote(trim($game->teamAName)).'
					  , guest       = '.$this->DB->quote(trim($game->teamBName)).'
					  , hall        = '.$this->DB->quote(trim($game->venue)).'
					  , result      = '.$this->DB->quote(trim($game->teamAScoreFT.':'.$game->teamBScoreFT)).'
					  , home_score  = '.$this->DB->quote(trim($game->teamAScoreFT)).'
					  , guest_score = '.$this->DB->quote(trim($game->teamBScoreFT)).'
					  , timestamp = now()
					'. ( $update ? 'where id = '.$this->DB->quote($update[0]) :'') .'
				');

			}
			
		}

		// update rankings automatically
		$this->setRankings();
	}

	private function isHomeTeam($team)
	{
		foreach( $this->CLUB_NAMES as $club )
		{
			if ( preg_match('@'.preg_quote($club).'@', $team ) )
				return true;
		}
		return false;
	}

	public function setRankings()
	{
		$this->DB->query("set autocommit = 0");
		$this->DB->query("start transaction");

		foreach( $this->getTeams() as $team )
		{
			$group = $this->getFromAPI('/teams/'.$team['id'].'/group');
debug($group);

			if( $group ) {

				// remove existing rankings of that group
				if ( @$team['team_id'] ) {
					$this->DB->query(
						'delete from ' . $this->getTableName('rankings') . ' ' . 
						'where team_id = ' . $this->DB->quote($team['team_id'])
					);
				}

				if ( ! is_array($group->ranking) ) continue;

				$this->DB->query('
					delete from '.$this->getTableName('rankings').'
					where team_id         = '.$this->DB->quote($team['id']).'
				');

				// loop each team
				foreach( $group->ranking as $ranking ) {

					if	( $ranking->rank <= $group->directPromotion )
						$rank_status =  2;
					elseif	( $ranking->rank <= $group->directPromotion + $group->promotionCandidate )
						$rank_status =  1;
					elseif	( $ranking->rank >  $group->totalTeams - $group->directRelegation )
						$rank_status = -2;
					elseif	( $ranking->rank >  $group->totalTeams - $group->directRelegation - $group->relegationCandidate )
						$rank_status = -1;
					else	$rank_status = 0;

					$home_team = ( $team['name'] == trim($ranking->teamName) );

					$this->DB->query('
						insert into '.$this->getTableName('rankings').'
						set team_id         = '.$this->DB->quote($team['id']).'
						  , ranking         = '.$this->DB->quote($ranking->rank).'
						  , home_team       = '.$this->DB->quote($home_team?1:0).'
						  , team_name       = '.$this->DB->quote($ranking->teamName).'
						  , games_played    = '.$this->DB->quote($ranking->totalGames).'
						  , games_won       = '.$this->DB->quote($ranking->totalWins).'
						  , games_draw      = '.$this->DB->quote($ranking->totalDraws).'
						  , games_loss      = '.$this->DB->quote($ranking->totalLoss).'
						  , goals_for       = '.$this->DB->quote($ranking->totalScoresPlus).'
						  , goals_against   = '.$this->DB->quote($ranking->totalScoresMinus).'
						  , goal_difference = '.$this->DB->quote($ranking->totalScoresDiff).'
						  , points          = '.$this->DB->quote($ranking->totalPoints).'
						  , rank_status     = '.$this->DB->quote($rank_status).'
					');
				}

			}
		}

		$this->DB->query("commit");
		$this->DB->query("set autocommit = 1");
	}

	public function updateTicker()
	{

		$games = $this->DB->getResults('
			select gamenr
			from '. $this->getTableName('games') .'
			where date < unix_timestamp(now())
			  and (
			    date > unix_timestamp(date_sub( now(), interval 15 minute))
			    or (
			      date > unix_timestamp(date_sub( now(), interval 2 hour))
			      and ticker_result is not null
			    )
			  )
			  and result is null
		');

		foreach ( $games as $gamenr )
		{
			debug("getting possible ticker for $gamenr");
			$this->getTickerFromGame($gamenr);
		}

	}

	private function getTickerFromGame($gamenr)
	{

		$options = array(
			'http' => array(
				'header'  => "content-type: application/json\r\n",
				'method'  => 'POST',
				'content' => '{"operationName":"getGame","variables":{"gameId":"'.$gamenr.'","isLive":false},"query":"query getGame($gameId: Int, $isLive: Boolean) {game(gameId: $gameId, isLive: $isLive) {homeTeamScore, awayTeamScore, ltGameTime, gameStatus}}"}'
				)
		);
		$context  = stream_context_create($options);
		$ticker = json_decode(file_get_contents("https://www.handball.ch/Umbraco/Api/MatchCenter/Query", false, $context));

		$gametime = $ticker->data->game[0]->ltGameTime;
		$result = $ticker->data->game[0]->homeTeamScore . ":" . $ticker->data->game[0]->awayTeamScore;

		if ( $gametime == "00:00" ) return;

		$this->DB->query('
			update '.$this->getTableName('games').'
			set ticker_time   = '.$this->DB->quote($gametime).'
			  , ticker_result = '.$this->DB->quote($result).'
			where gamenr      = '.$this->DB->quote($gamenr).'
		');

	}

}

