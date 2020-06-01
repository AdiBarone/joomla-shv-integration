<?php 
// No direct access
defined('_JEXEC') or die; ?>
<?php

if( $params->get('type') == "ranking" ) {

  echo '<table class="mod_shv_games_rankings" cellspacing="0" cellpadding="0">'.
       '<tbody>';
  $state = array(
     2 => "rank-dp ", # direct promotion
     1 => "rank-pq ", # promotion qualification
     0 => "",         # neutral ranking
    -1 => "rank-dq ", # demotion qualification
    -2 => "rank-dd ", # direct demotion
  );
  foreach( $shv['rankings'] as $team ) {
    echo '<tr class="'. $state[ $team['rank_status'] ].''.( $team['home_team'] ? 'hometeam':'' ).'">'.
           '<td class="ranking">'.$team['ranking'].'</td>'.
           '<td>'.$team['team_name'].'</td>'.
           '<td class="games_played">'.$team['games_played'].'</td>'.
           '<td class="points">'.$team['points'].'</td>'.
         '</tr>';
  }
  echo '</tbody></table>';

  if ( $params->get('teams-uri') ) {
    echo '<p class="mod_shv_games_rankings_team_link"><a href="'.$params->get('teams-uri').'/'.strtolower($shv['team']['common_name']).'">zur Teamseite</a></p>';
  }

} elseif( $params->get('type') == "results" ) {

  if( ! count($shv) > 0 ) {

    echo '<center>Keine aktuellen Resultate vorhanden</center>';

  } else {

    echo '<script type="text/javascript">
		function updateTicker() {
			jQuery.ajax("/ticker-update.php").done(function(json){
				data = jQuery.parseJSON(json)
				if ( data.length > 0 ) {
					data.forEach(function(e) {
						jQuery("#ticker_"+e.gamenr+"_result").html(e.result)
						jQuery("#ticker_"+e.gamenr+"_time").html(e.time)
					})
					setTimeout("updateTicker()", 10000)
				}
			});
		}
		updateTicker()
</script>';
    echo '<table class="mod_shv_games_games" cellspacing="0" cellpadding="0">'.
         '<tbody>';
    foreach( $shv as $i => $game ) {
			# when result is not yet defined
			if( $game['result'] ) {
      	$result = explode(":",$game['result']);
      	$home_win  = $result[0] > $result[1];
      	$guest_win = $result[0] < $result[1];
			} else {
      	$game['result'] = '<span class="hasTooltip tip" title="" data-original-title="Dieses Resultat wurde noch nicht gemeldet!">?</span>';
			}
			$hcm_win = false;
			$hcm_loss = false;
      foreach( array("home","guest") as $team ) {
        if ( preg_match("/.*Malters.*/", $game[$team] ) ) {
          $game[$team] = "HCM";
					if ( $team == "home"  && $home_win   ) $hcm_win = true;
					if ( $team == "home"  && $guest_win  ) $hcm_loss = true;
					if ( $team == "guest" && $guest_win  ) $hcm_win = true;
					if ( $team == "guest" && $home_win   ) $hcm_loss = true;
				}
      }
  
			$date = ModSHVGamesHelper::getDate($game['date'],0);
			if ( $date != @$last_date ) {
      	echo '<tr class="title">'.
             	'<th colspan="6" class="date">'.ModSHVGamesHelper::getDate($game['date'],0).'</th>'.
           	'</tr>';
				$j=0;
			}
			$last_date = $date;

      echo '<tr class="'.( $j % 2 == 0 ? ' odd':'' ).( @$game['leader'] ? ' leader':'' ).'">'.
             '<td width="10%" nowrap class="league"><a href="/index.php/teams/'.$game['common_name'].'">'.
						 		$game['common_name'].
								( $game['type'] != "MS" ? ' ('.$game['league'].')' : '' ).
							'</a></th>'.
             '<td width="38%" class="home'.($home_win?' winner':'').'">'.$game['home'].'</td>'.
             '<td width="9%" nowrap class="result">'.
						 	 ( @$game['live-ticker'] ? 
								 '<span class="live"><a href="https://www.handball.ch/de/matchcenter/spiele/'.$game['gamenr'].'" title="Matchcenter öffnen" target="_blank">LIVE</a></span>'.
								 '<span class="ticker_result" id="ticker_'.$game['gamenr'].'_result">'.$game['ticker_result'] .'</span>'.
								 '<span class="ticker_time" id="ticker_'.$game['gamenr'].'_time"><span class="icon-clock"></span>'.$game['ticker_time'] .'</span>'
						 	 : '<span nowrap class="indicator'.($hcm_loss?' loss':'').($hcm_win?' won':'').'">&#9475;</span>'.
						   		( $game['ticker_result'] ? '<a href="https://www.handball.ch/de/matchcenter/spiele/'.$game['gamenr'].'" title="Matchcenter öffnen" target="_blank">' : '' ) .
						   			$game['result'].
						   		( $game['ticker_result'] ? '</a>' : '' ) .
               		'<span nowrap class="indicator'.($hcm_loss?' loss':'').($hcm_win?' won':'').'">&#9475;</span>'
						   ) .
					 	 '</td>'.
             '<td width="38%" class="guest'.($guest_win?' winner':'').'">'.$game['guest'].'</td>'.
           '</tr>';
			$j++;
    }
    echo '</tbody></table>';

  }

} elseif( $params->get('type') == "games" ) {

  if( count($shv) == 0 ) {

    echo '<center>Keine Spiele geplant.</center>';

  } else {

    echo '<table class="mod_shv_games_games" cellspacing="0" cellpadding="0">'.
         '<tbody>';
    foreach( $shv as $i => $game ) {

      foreach( array("home","guest") as $team ) {
        
        if ( preg_match("/Malters/", $game[$team] ) ) {

          if( preg_match('/^MU/',$game['common_name'] ) ) {
            $common_name = "Junioren ".preg_replace('/^M(U[0-9]+).*$/','$1',$game['common_name']);
          } elseif( preg_match('/^FU[0-9]/',$game['common_name'] ) ) {
            $common_name = "Juniorinnen ".preg_replace('/^F(U[0-9]+).*$/','$1',$game['common_name']);
          } elseif( preg_match('/^D[0-9]/',$game['common_name'] ) ) {
            $common_name = "Damen ".preg_replace('/^D([0-9]+).*$/','$1',$game['common_name']);
          } elseif( preg_match('/^H[0-9]/',$game['common_name'] ) ) {
            $common_name = "Herren ".preg_replace('/^H([0-9]+).*$/','$1',$game['common_name']);
          } else {
            $common_name = $game['common_name'];
          }
          
          $game[$team] = '<span class="league">'.
					    '<a href="/index.php/teams/'.$game['common_name'].'">'.
						 		$common_name . 
								( $game['type'] != "MS" ? ' ('.$game['league'].')' : '' ).
							'</a>'.
						'</span>';
      	}
      }

			$date = ModSHVGamesHelper::getDate($game['date'],0);
			if ( $date != @$last_date ) {
      	echo '<tr class="title">'.
             	'<th colspan="6" class="date">'.ModSHVGamesHelper::getDate($game['date'],0).'</th>'.
           	'</tr>';
				$j=0;
			}
			$last_date = $date;

      echo '<tr class="'.( $j % 2 == 0 ? ' odd':'' ).( @$game['leader'] ? ' leader':'' ).'" title="'.$game['hall'].'">'.
             '<td width="5%" class="date">'.
						   '<span class="hasTooltip tip" title="" data-original-title="<strong>Halle:</strong><br />'.$game['hall'].'">'.
						   ModSHVGamesHelper::getDate($game['date'],-1).
							 '</span>'.
						 '</td>'.
             '<td width="45%" class="home">'.$game['home'].'</td>'.
             '<td width="6%" nowrap class="vs">vs.</td>'.
             '<td width="39%" class="guest">'.$game['guest'].'</td>'.
             '<td width="5%" nowrap class="league">'.( $game['type'] != "MS" ? ' ('.$game['league'].')' : '' ).'</td>'.
           '</tr>';
			$j++;
    }
    echo '</tbody></table>';

  }

}

?>
