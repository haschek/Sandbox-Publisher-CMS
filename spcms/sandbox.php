<?php
/**
 * Sandbox Publisher 0.1 RC1
 *
 * Sandbox Publisher is a Mini-CMS (Content Management System). It simply can
 * parse files and it routes their content to PHP template files. Sandbox
 * Publisher comes with a plugin manager and an event dispatcher built in, so
 * it is easy to integrate plugins (e.g. to connect a database). It works with
 * virtual files (non existing ones), too.
 *
 * METADATA
 *
 * @category  SPCMS
 * @package   Sandbox-Core
 * @author    Michael Haschke @ eye48.com
 * @copyright 2008 Michael Haschke
 * @license   http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @version   SVN: $Id$
 *
 * WEBSITES
 *
 * @link      http://sandbox.eye48.com/sandbox-publisher-cms Project Website and Overview
 * @link      http://code.google.com/p/sandbox-publisher-cms Dev Website and Issue tracker
 *
 * LICENCE
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @link      http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 *
 **/


/**
 * Sandbox Loader
 *
 * This PHP script keeps all together:
 * - loading configuration
 * - instanciate the Sandbox class
 * - init the Sandbox environment inclusive plugin manager and event dispatcher
 * - providing important constants like Sandbox path and base url
 * - get requested file name
 * - flushing the output
 *
 * @package    Sandbox-Core
 * @subpackage Sandbox-Loader
 * @access     private
 * @since      0.1
 **/

// include default configuration
require_once 'sandbox.default.php';

// include user configuration if there is one
if (is_readable('sandbox.user.php')) {
    include_once 'sandbox.user.php';
}

// include Sandbox class and tools
require_once 'sandbox.inc.php';

// define sandbox path
define('SANDBOX_PATH', rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);

// define document's root path'
define('DOCUMENT_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);

// define sandbox base dir (URL)
if (!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS'] || $_SERVER['HTTPS'] == 'off') {
    $prot = 'http://';
} else {
    $prot = 'https://';
}
define('SANDBOX_BASE', $prot.rtrim($_SERVER['SERVER_NAME'], DIRECTORY_SEPARATOR).str_replace(DOCUMENT_ROOT, '/', SANDBOX_PATH));

// get requested filename
if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    $file = rtrim(DOCUMENT_ROOT, DIRECTORY_SEPARATOR).substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
} else {
    $file = rtrim(DOCUMENT_ROOT, DIRECTORY_SEPARATOR).$_SERVER['REQUEST_URI'];
}

// set error level
if (isset($production) && $production === true) {
    // Sandbox Application runs in production use
    // don't show any errors
    error_reporting(0);
} else {
    // debug mode
    // show all tiny errors and warnings
    error_reporting(E_ALL | E_STRICT);
}

// run Sandbox
try {
    // create Sandbox
    $parsing = new Sandbox($c);
    // parse file
    $parsing->parse($file);
    // output
    $parsing->flush();
} catch (Exception $e) { // something went wrong

    // check current error level
    if (!isset($production) || $production !== true) {
        // print out debug message
        echo '<h1>'.$e->getMessage().'</h1>';
        echo '<p>in <strong>'.$e->getFile().'</strong> at line <strong>'.$e->getLine().'</strong>, code <strong>'.$e->getCode().'</strong>.</p>';
        echo '<pre>'.$e->getTraceAsString().'</pre>';
    }
    
    // TODO: else: send server error (what number? must check.)
    
    die();
}

?>
