<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  system.shvgames
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class PlgSystemSHVGames extends JPlugin
{
	/**
	* Load the language file on instantiation.
	*
	* @var    boolean
	* @since  __DEPLOY_VERSION__
	*/
	protected $autoloadLanguage = true;
	
	/**
	* Application object.
	*
	* @var    JApplicationCms
	* @since  __DEPLOY_VERSION__
	*/
	protected $app;
	
	/**
	* Database object.
	*
	* @var    JDatabaseDriver
	* @since  __DEPLOY_VERSION__
	*/
	protected $db;
	
	/**
	* The log check and rotation code event.
	*
	* @return  void
	*
	* @since   __DEPLOY_VERSION__
	*/
	public function executeScheduledTask($task = array())
	{
		$startTime = microtime(true);
	
		// Get the timeout for job task
		$now           = time();
	
		$last          = $this->params->get('lastrun', 0);
		$cache_timeout = (int) $this->params->get('cachetimeout', 1);
		$unit          = (int) $this->params->get('unit', 86400);
		$cache_timeout = $unit * $cache_timeout;
	
		if ((abs($now - $last) < $cache_timeout)) {
			return;
		}
	
		// set last execution timestamp
		$table = new JTableExtension(JFactory::getDbo());
		$table->load(array('element' => 'shvgames'));
		$this->params->set('lastrun', time());
		$table->set('params', $this->params->toString());
		$table->store();
	
		// Execute the job
		$this->jobTask();

		$endTime    = microtime(true);
		$timeToLoad = sprintf('%0.2f', $endTime - $startTime);

		try
			{
			JLog::add(
				JText::sprintf('Executed Plugin: %s', $this->_name)  . 
				JText::sprintf('Processing Time: %s seconds.', $timeToLoad),
				JLog::INFO,
				'scheduler'
			);
		} catch (RuntimeException $exception) {
			// Informational log only
		}
	}

	/**
	* The log check and rotation code event.
	*
	* @return  void
	*
	* @since   __DEPLOY_VERSION__
	*/
	public function jobTask()
	{
		require_once dirname(__FILE__) . '/helper.php';

		$API = new PlgJobSHVGamesHelper(array(
			"API_KEY" => $this->params->get('api-key'),
			"CLUBS"   => explode(",", $this->params->get('clubs')),
			"NAMES"   => explode(",", $this->params->get('names')),
		));

		// $API->setEnforce();
    
		$API->setTeams();
		$API->setGames();
		$API->setResults();
		$API->setRankings();
		$API->updateTicker();

	}

}
