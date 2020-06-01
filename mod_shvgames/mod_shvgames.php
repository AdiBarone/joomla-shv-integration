<?php
/**
 * SHV Games Module Entry Point
 * 
 * @package    site.shvgames
 * @subpackage Modules
 * @license    GNU/GPL, see LICENSE.php
 * @link       http://docs.joomla.org/J3.x:Creating_a_simple_module/Developing_a_Basic_Module
 * mod_shvgames is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// No direct access
defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

// Trigger update if activated
if ( $params->get('scheduleUpdate') == 1 ) {
  JDispatcher::getInstance()->trigger('executeScheduledTask', array());
}

// Initialize
ModSHVGamesHelper::initialize();

// Route to correct method
switch ( $params->get('type') ) {
  case "ranking": $shv = ModSHVGamesHelper::getRankings($params); break;
  case "games"  : $shv = ModSHVGamesHelper::getGames($params); break;
  case "results": $shv = ModSHVGamesHelper::getResults($params); break;
} 

require JModuleHelper::getLayoutPath('mod_shvgames');
