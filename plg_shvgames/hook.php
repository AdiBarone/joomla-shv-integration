<?php

// Initialize Joomla framework
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
    require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
    define('JPATH_BASE', dirname(__DIR__) . '/../../');
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

require_once JPATH_BASE . '/includes/framework.php';

/**
 * Cron job 
 *
 */
class myCron extends JApplicationCli
{
     public function doExecute()
     {
         // require_once "shv_games.php";
         // PlgSystemSHVGames::jobTask(); // <- wont work
     }
}

JApplicationCli::getInstance('myCron')->execute();
