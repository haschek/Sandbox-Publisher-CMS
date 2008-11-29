<?php
/**
 * Sandbox Publisher - Default configuration
 *
 * @category SPCMS
 * @package  Sandbox-Core
 *
 * @author   Michael Haschke, eye48.com
 * @license  http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 *
 * @version  SVN: $Id$
 *
 * @link     http://code.google.com/p/sandbox-publisher-cms Dev Website and Issue tracker
 *
 * You may configure everything here, but if you want to update your Sandbox
 * based App/Website easy, it is recommended to set up your own configuration
 * file 'sandbox.user.php' and store it to the same folder like this configuration
 * is located.
 *
 * In 'sandbox.user.php' you can overwrite all stuff what is here specified, you
 * also can unset the vars there (e.g. unset($c['plugin'])). To have an start just
 * copy & paste everything between '<SNAP>'.
 **/

/* <SNAP> -- configuration starts here */

// Is your Sandbox app/website in production use?
$production = false;

// template folders
$c['template']['folder'][] = './templates/';

// templates
$c['template']['name'] = 'default.tpl';

// plugin folders
$c['plugin']['folder'][] = './plugins/';

// load plugins at start
// $c['plugin']['load'][] = 'PluginName';

// caching
// $c['cache']['age'] = 2 * 60 * 60; // cache stuff for 2 hours
// $c['cache']['folder'] = './../cache/';

/* </SNAP> -- configuration ends here */

?>
