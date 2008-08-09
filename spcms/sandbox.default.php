<?php

/**
 * Sandbox Publisher - Default configuration
 *
 * @version $Id$
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

/* </SNAP> -- configuration ends here */

?>
