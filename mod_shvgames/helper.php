<?php
/**
 * Helper class for SHV Games module
 *
 * @package    site.shvgames
 * @subpackage Modules
 * @link http://docs.joomla.org/J3.x:Creating_a_simple_module/Developing_a_Basic_Module
 * @license        GNU/GPL, see LICENSE.php
 * mod_shvgames is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
class ModSHVGamesHelper
{

    /**
     * Initialize SHV Games
     *
     * @access public
     */
    public static function initialize() {

      $document = JFactory::getDocument();

      // is this still needed?
      // date_default_timezone_set("Europe/Zurich");

      # css includes
      $head[] = '<link rel="stylesheet" href="/modules/mod_shvgames/css/style.css" type="text/css" media="screen" />';

      $document->addCustomTag(implode("\n",$head));

    }

    /**
     * Retrieves the current season (if it's june, change to next season)
     *
     * @access public
     */
    public static function getSeason() {

      if( date("n") >= 6 ) {
        return date("Y");
      } else {
        return date("Y")-1;
      }
    }

    /**
     * Shortens the team name, if needed
     *
     * @teams   array  $teams An array containing several teams
     * @html    bool   $html A boolean trigger to set span-title html tag when shortening
     *
     * @access public
     */
    public static function shortenTeamNames($attributes, $teams, $html = true) {
      foreach( $attributes as $attribute ) {
        // $length     = $params->get('team-length', 23);    # string length
        $length     = 23;
        $shortener  = "..."; # shorten by this string
        foreach( $teams as $i => $team ) {
          $spec_chars = strrev(preg_replace('/^([^A-Za-z0-9_ ()]*).*$/', '$1', strrev(trim($team[$attribute]))));
          $shorten_by = $length - strlen($shortener.$spec_chars);
          if ( strlen($team[$attribute]) > $length ) {
            $shortened = mb_substr($team[$attribute], 0, $shorten_by) . "... " . $spec_chars;
            if ( $html )  $teams[$i][$attribute] = '<span class="hasTooltip tip" title="" data-original-title="'.$team[$attribute].'">' . $shortened . '</span>';
            else          $teams[$i][$attribute] = $shortened;
          }
        }
      }

      return $teams;
    }

    /**
     * Retrieves the team data
     *
     * @name   string  $name A string containing the team name
     *
     * @access private
     */
    private static function getTeam($name) {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
          ->select(array('id','league','common_name'))
          ->from('#__shv_teams')
          ->where(array(
            'common_name = '.$db->quote($name)
          , 'season = '.$db->quote(ModSHVGamesHelper::getSeason())
          ));
        $db->setQuery($query);
        $team = $db->loadAssoc();
        return $team;
    }

    /**
     * Returns a human readable date string
     *
     * @ts    int  $ts A unix timestamp
     * @show_time  bool $show_time a switch to show time or not
     *
     * @access public
     */
    public static function getDate( $ts, $show_time=1 ) {

      $day_begins = mktime(0,0,0,date("m"),date("d"),date("Y"));
      $str = "";

      if ( $show_time != -1 ) {
        if( $ts > $day_begins && $ts < $day_begins + 86400 ) {
          $str = "heute ";
        } elseif( $ts > $day_begins + 86400 && $ts < $day_begins + 2*86400 ) {
          $str = "morgen ";
        } else {
          $days = array('So','Mo','Di','Mi','Do','Fr','Sa');
          $months = array('Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember');
          $str .= $days[ date("w",$ts) ].".";
          $str .= date(" j. ",$ts);
          $str .= $months[ date("n",$ts) - 1 ];
          $str .= date(" Y",$ts);
        }
      }

      if ( date("H:i",$ts) != "00:00" ) {
        if ( $show_time == 1 ) $str .= ', ';
        if ( $show_time != 0 ) $str .= date("H:i",$ts);
        if ( $show_time == 1 ) $str .= ' Uhr';
      } else {
        if ( $show_time != 0 ) $str .= " (prov)";
      }

      return $str;

    }


    /**
     * Retrieves the team rankings
     *
     * @param   array  $params An object containing the module parameters
     *
     * @access public
     */
    public static function getRankings($params) {

        $team = ModSHVGamesHelper::getTeam($params->get('team_name'));

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
          ->select(array('ranking','team_name','points','home_team','rank_status','games_played'))
          ->from('#__shv_rankings')
          ->where('team_id = ' .  $db->quote($team['id']) )
          ->order('ranking asc');
        $db->setQuery($query);
        $teams = $db->loadAssocList();

        $teams = ModSHVGamesHelper::shortenTeamNames(array("team_name"), $teams);

        return array(
          'team' => $team,
          'rankings' => $teams
        );
    }

    /**
     * Retrieves the team games
     *
     * @param   array  $params An object containing the module parameters
     *
     * @access public
     */
    public static function getGames($params) {

      $teams = explode(",",$params->get("team_name"));
      $hall = $params->get("hall");
      $bool = $params->get("hall_only");
      $min_games = ( $params->get("min_games") ? $params->get("min_games") : 5 );

      $db = JFactory::getDbo();

      for ( $forecast=2; $forecast<= 150; $forecast++ ) {
        $where = array(
            'g.season = ' . $db->quote(ModSHVGamesHelper::getSeason()),
            't.common_name in ("' . implode('","', $teams ) . '")',
            'g.result is null',
            'g.date < ' . (time()+$forecast*86400),
            'g.date > ' . (time()-3600),
            'active = 1',
        );
        if ( $hall )
          $where[] = 'g.hall '.( $bool ? '' : 'not' ).' like '.$db->quote("%".$hall."%");

        $query = $db->getQuery(true)
          ->select(array('t.common_name','g.date','g.home','g.guest','g.hall','g.type','g.league'))
          ->from('#__shv_games g')
          ->join('INNER','#__shv_teams t ON ( ' . $db->quoteName('g.team_id') . ' = ' . $db->quoteName('t.id') . ' )' )
          ->where($where)
          ->order('date asc');

        $db->setQuery($query);
        $games = $db->loadAssocList();

        if ( count($games) >= $min_games ) break;

      }

      # set leader teams
      foreach ( $games as $i => $game ) {
        if (  $game['common_name'] == "H1" ||
              $game['common_name'] == "D1" )
          $games[$i]['leader'] = true;
      }

      $games = ModSHVGamesHelper::shortenTeamNames(array("guest","home"),$games);

      return $games;

    }

    /**
     * Retrieves the latest team results
     *
     * @param   array  $params An object containing the module parameters
     *
     * @access public
     */
    public static function getResults($params) {

      $teams = explode(",",$params->get("team_name"));

      $db = JFactory::getDbo();

      for ( $recall=2; $recall<= 150; $recall++ ) {

        $query = $db->getQuery(true)
          ->select(array('t.common_name','g.date','g.gamenr','g.home','g.guest','g.hall','g.result','g.type','g.league','g.ticker_result','g.ticker_time'))
          ->from('#__shv_games g')
          ->join('INNER','#__shv_teams t ON ( ' . $db->quoteName('g.team_id') . ' = ' . $db->quoteName('t.id') . ' )' )
          ->where(array(
            'g.season = ' . $db->quote(ModSHVGamesHelper::getSeason())
           ,'t.common_name in ("' . implode('","', $teams ) . '")'
           ,'g.date > ' . (time()-$recall*86400)
           ,'( result is not null or ticker_result is not null )'
           ,'active = 1'
          ))
          ->order('date desc');
        $db->setQuery($query);
        $games = $db->loadAssocList();

        foreach($games as $i => $game ) {
          if ( ! $game['result'] && $game['ticker_result'] && $game['ticker_result'] != '0:0' ) {
            $games[$i]['live-ticker'] = true;
          }
        }

        if ( count($games) > 5 ) break;

      }

      # set leader teams
      foreach ( $games as $i => $game ) {
        if (  $game['common_name'] == "H1" ||
              $game['common_name'] == "D1" )
          $games[$i]['leader'] = true;
      }

      $games = ModSHVGamesHelper::shortenTeamNames(array("guest","home"),$games);

      return $games;

    }

}
