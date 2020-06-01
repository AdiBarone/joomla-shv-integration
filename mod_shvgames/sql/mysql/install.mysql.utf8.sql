CREATE TABLE IF NOT EXISTS `#__shv_teams` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `season` int(4) unsigned DEFAULT NULL,
  `common_name` varchar(4) NOT NULL,
  `league` varchar(16) NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `common_name_season` (`common_name`,`season`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__shv_rankings` (
  `group_id` int(5) unsigned NOT NULL,
  `team_id` int(11) NOT NULL DEFAULT '0',
  `ranking` int(2) NOT NULL,
  `home_team` int(1) unsigned NOT NULL,
  `team_name` varchar(256) NOT NULL,
  `games_played` int(3) NOT NULL,
  `games_won` int(3) NOT NULL,
  `games_draw` int(3) NOT NULL,
  `games_loss` int(3) NOT NULL,
  `goals_for` int(4) NOT NULL,
  `goals_against` int(4) NOT NULL,
  `goal_difference` int(4) NOT NULL,
  `points` int(3) NOT NULL,
  `rank_status` int(1) DEFAULT '0',
  PRIMARY KEY (`group_id`,`team_id`,`ranking`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__shv_games` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gamenr` bigint(15) unsigned DEFAULT NULL,
  `prov` int(1) DEFAULT '0',
  `date` int(11) unsigned NOT NULL,
  `season` int(11) unsigned NOT NULL,
  `league` varchar(16) NOT NULL,
  `team_id` int(11) unsigned DEFAULT NULL,
  `type` varchar(4) NOT NULL DEFAULT 'MS',
  `home` varchar(128) NOT NULL,
  `guest` varchar(128) NOT NULL,
  `hall` varchar(128) DEFAULT NULL,
  `result` varchar(6) DEFAULT NULL,
  `home_score` int(2) DEFAULT NULL,
  `guest_score` int(2) DEFAULT NULL,
  `ticker_time` varchar(5) DEFAULT NULL,
  `ticker_result` varchar(5) DEFAULT NULL,
  `active` int(1) unsigned DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gamenr` (`gamenr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

