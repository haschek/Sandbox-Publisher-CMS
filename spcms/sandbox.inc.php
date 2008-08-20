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
 * PHP version 5
 *
 * METADATA
 *
 * @category  SPCMS
 * @package   Sandbox-Core
 * @author    Michael Haschke, eye48.com
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
 * Sandbox Publisher
 * 
 * This is the central Sandbox class, providing methods to init the environment
 * (loading stndard plugins and assign default template), parse local files for
 * content, providing interfaces to plugin manager/event dispatcher and the
 * content store, can assign template folders, selecting the template file.
 *
 * @category   SPCMS
 * @package    Sandbox-Core
 * @subpackage Sandbox-Includes
 * @author     Michael Haschke, eye48.com
 * @license    http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @link       http://http://code.google.com/p/sandbox-publisher-cms
 * @since      0.1
 * 
 **/
class Sandbox
{
    /**
     * @var SandboxContent $content Sandbox content object which stores all assigned content variables
     * @access public
     * @since 0.1
     **/
    public $content = null;
    
    /**
     * @var SandboxPluginmanager $pm Sandbox Plugin manager and Event dispatcher
     * @access public
     * @since 0.1
     **/
    public $pm = null;
    
    /**
     * @var array $templatefolders stack of activated folders containing template files
     * @access public
     * @since 0.1
     *
     * It can contain absolute (/path/to/) and relative (./path/to/) paths to
     * template folders. Relative paths must be relative to Sandbox root folder.
     **/
    public $templatefolders = array();
    
    /**
     * @var string $templatename template file name, absolute path
     * @access public
     * @since 0.1
     **/
    public $templatename = null;
    
    // maybe we need this later
    // config
    // private var $_config = array();
    
    /**
     * Sandbox costructor
     *
     * The constructor ceates the objects for Sandbox content store and the
     * plugin manager/event dispatcher, load plugins and templates.
     * 
     * @param array $config Configuration from sandbox.default|user.php ($c)
     *
     * @return void
     *
     * @since 0.1
     * @access public
     *
     * @publish event sandbox_construct_complete
     **/
    public function __construct($config)
    {
        // create content object
        $this->content = new SandboxContent();
        
        // create plugin environment
        $this->pm = new SandboxPluginmanager($this);
        
        // add pluginfolders
        if (isset($config['plugin']['folder']) && is_array($config['plugin']['folder'])
            && count($config['plugin']['folder']) > 0) {
            
            $pluginfolders = implode(PATH_SEPARATOR, $config['plugin']['folder']);
            $pluginfolders = explode(PATH_SEPARATOR, $pluginfolders);
            foreach ($pluginfolders as $pluginfolder) {
                $this->pm->addFolder(realpath(SANDBOX_PATH.$pluginfolder));
            }
            
        }
        
        // load plugins
        if (isset($config['plugin']['load']) && is_array($config['plugin']['load'])
            && count($config['plugin']['load']) > 0) {
            
            $plugins = implode(PATH_SEPARATOR, $config['plugin']['load']);
            $plugins = explode(PATH_SEPARATOR, $plugins);
            foreach ($plugins as $plugin) {
                $this->pm->load($plugin);
            }
            
        }
        
        // add template folders
        if (isset($config['template']['folder']) && is_array($config['template']['folder'])
            && count($config['template']['folder']) > 0) {
            
            foreach ($config['template']['folder'] as $templatefolder) {
                $this->templateAddFolder($templatefolder);
            }
        }
        
        // set template
        if (isset($config['template']['name']) && $config['template']['name']) {
            $this->templateSetName($config['template']['name']);
        }
            
        /* EVENT sandbox_construct_complete
         * published at the end of Sandbox::__construct,
         * - SandboxContent and SandboxPluginmanager has been created
         * - folders of plugins and templates (taken from configuration) are stored
         * - standard template file is assigned
         * - standard plugin classes (from configuration) had been instanciated
         */
        $this->pm->publish('sandbox_construct_complete');
    }
    
    /**
     * Sandbox parser
     *
     * It parses the local files for content tags ({Content123}), stores the
     * content in the Sandbox content object. PHP code (in php tags) is executed
     * on the fly.
     *
     * @param string $file absolute file name
     *
     * @return boolean true for file has been parsed, falso for file not found
     *
     * @since 0.1
     * @access public
     *
     * @publish event sandbox_parse_start
     * @publish event sandbox_parse_end
     * @publish event sandbox_parse_failed
     **/
    public function parse($file)
    {
        $php = false;
        $phpcode = null;
        $varmatch = array();
        $varKey = null;
        $varValue = null;
    
        if (is_file($file) && !is_dir($file) && is_readable($file)) {
        
            /* EVENT sandbox_parse_start
             * @param String    $file   name of file (server path)
             */
            $this->pm->publish('sandbox_parse_start', $file);
        
            // file_get_contents
            $content = file($file);
            foreach ($content as $line) {
                if (rtrim($line) == '<?php') {
                    $php = true;
                    $phpcode = null;
                } elseif (rtrim($line) == '?>') {
                    $php = false;
                    // execute php code
                    if (trim($phpcode)) eval(trim($phpcode));
                } elseif (preg_match('/^\{([a-z|A-Z]+[0-9]*)\}$/', $line, $varmatch)) { // regex for {Varname111}
                    $varKey = $varmatch[1];
                    //$varValue = null; // set empty var
                } else {
                    if ($php === true) {
                        $phpcode .= $line;
                    } elseif ($varKey) {
                        //$varValue .= $line;
                        //$this->content->$varKey = trim($varValue);
                        $this->content->$varKey .= $line;
                    }
                }
            }

            /* EVENT sandbox_parse_end
             * @param String    $file   name of file (server path)
             */
            $this->pm->publish('sandbox_parse_end', $file);
            
            return true;

        } else {

            /* EVENT sandbox_parse_failed
             * @param String    $file   name of file (server path)
             */
            $this->pm->publish('sandbox_parse_failed', $file);

            return false;
        }
    }
    
    /**
     * Sandbox output
     *
     * Runs the PHP template file to print out the content.
     *
     * @return boolean true for template has been included, false for template was not found
     *
     * @since 0.1
     * @access public
     *
     * @throws Exception 'No template assigned!'
     **/
    public function flush()
    {
        if ($this->templatename) {
            include_once $this->templatename;
            return true;
        } else {
            throw new Exception("No template assigned!");
            return false;
        }
    }
    
    /**
     * Add template folders
     *
     * If path exists the folder will be added to $templatefolders stack. The path
     * must be absolute, or relative to the Sandbox root path.
     *
     * @param string $folder realative or absolute path to template folder.
     *
     * @return boolean true for success, false for wrong folder (not existing)
     *
     * @since 0.1
     * @access public
     *
     * @throws Exception 'Template folder not found!'
     **/
    public function templateAddFolder($folder = null)
    {
        if (!$folder || !is_string($folder)) return false;
        
        $templatefolder = null;
    
        // relative or absolute path
        if (substr($folder, 0, 1) == DIRECTORY_SEPARATOR) {
            // absolute path
            $templatefolder = $folder;
        } else {
            // relative path
            $templatefolder = SANDBOX_PATH.$folder;
        }
        
        if (is_readable($templatefolder)) {
            $this->templatefolders[$folder] = rtrim(realpath($templatefolder), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            return true;
        } else {
            throw new Exception("Template folder '".$templatefolder."' not found!");
            return false;
        }
    }
    
    /**
     * Set template name
     *
     * Search the name of the template in the folders which are stored in the
     * template folder stack (@see Sandbox::templateAddFolder).
     *
     * @param string $name name of template file without extension (.php)
     *
     * @return string absolute file name of template inclusive extension
     *
     * @since 0.1
     * @access public
     *
     * @throws Exception 'Template %name% was not found or is not readable.'
     **/
    public function templateSetName($name = null)
    {
        $this->templatename = null;
    
        foreach ($this->templatefolders as $folder) {
            if (is_readable($folder.$name.'.php') && !$this->templatename)
                $this->templatename = $folder.$name.'.php';
        }
        
        if (!$this->templatename) {
            throw new Exception("Template '".$name."' was not found or is not readable.");
            return false;
        }
        
        return $this->templatename;
    }
    
    /**
     * Output content variable
     *
     * Use this method in the templates to print out variables stored in the
     * Sandbox content object (@see SandboxContent). This is like an short key
     * command for 'echo $this->content->varname;'.
     *
     * @param string $var name of variable
     *
     * @return mixed content of variable
     *
     * @since 0.1
     * @access public
     *
     * @todo option to check var content for trimming, stripping tags, controlling charset, ...
     **/
    public function show($var)
    {
        echo $this->content->$var;
    }
    
}

/**
 * Sandbox Content
 * 
 * The content object where the content variables are stored in. Currently only
 * some magic methods and an array stack.
 *
 * @category   SPCMS
 * @package    Sandbox-Core
 * @subpackage Sandbox-Includes
 * @author     Michael Haschke, eye48.com
 * @license    http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @link       http://http://code.google.com/p/sandbox-publisher-cms
 * @since      0.1
 * 
 **/
class SandboxContent
{
    /**
     * @var array $_c array stack where all content vars are stored in
     * @access private
     * @since 0.1
     **/
    private $_c = array();
    
    /**
     * Sandbox Content contructor
     *
     * The constructor is only a stub right now, may be needed later for more stuff.
     *
     * @return boolean true
     *
     * @since 0.1
     * @access public
     **/
    public function __construct()
    {
        // only a stub
    }
        
    /**
     * Magic method to get variable
     *
     * Do not use this directly, just use $SandboxContentObject->varname;
     *
     * @param string $var variable name
     *
     * @return mixed
     *
     * @since 0.1
     * @access private
     **/
    public function __get($var)
    {
        if (isset($this->_c[$var])) {
            return $this->_c[$var];
        } else {
            return null; // return empty string
        }
    }

    /**
     * Magic method to set variable
     *
     * Do not use this directly, just use $SandboxContentObject->varname = $varcontent;
     *
     * @param string $var   variable name
     * @param mixed  $value variable value
     *
     * @return void
     *
     * @since 0.1
     * @access private
     **/
    public function __set($var, $value)
    {
        $this->_c[$var] = $value;
    }

    /**
     * Magic method to check variable if it is set
     *
     * Do not use this directly, just use isset($SandboxContentObject->varname);
     *
     * @param string $var variable name
     *
     * @return boolean
     *
     * @since 0.1
     * @access private
     **/
    public function __isset($var)
    {
        return isset($this->_c[$var]);
    }

    /**
     * Magic method to unset a variable (delete from memory)
     *
     * Do not use this directly, just use unset($SandboxContentObject->varname);
     *
     * @param string $var variable name
     *
     * @return boolean
     *
     * @since 0.1
     * @access private
     **/
    public function __unset($var)
    {
        unset($this->_c[$var]);
        return;
    }
}

/**
 * Sandbox Plugin Manager & Event Dispatcher
 * 
 * The plugin manager (PM) can include and load (include + instanciate) plugins,
 * it has methods to search for plugin classes. The PM is also used to add more
 * directories where plugins may be located. The event dispatcher is included in
 * the PM, used to integrate plugin by subscribing their methods to events as
 * their event handlers. Events are published (propagated) through the event
 * dispatcher, too.
 *
 * @category   SPCMS
 * @package    Sandbox-Core
 * @subpackage Sandbox-Includes
 * @author     Michael Haschke, eye48.com
 * @license    http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @link       http://http://code.google.com/p/sandbox-publisher-cms
 * @since      0.1
 * 
 **/
class SandboxPluginmanager
{
    
    /**
     * @var array $_environment stack to store folders and sub folders of directories where plugin classes are located
     * @access private
     * @since 0.1
     **/
    private $_environment = array();
    
    /**
     * @var array $_plugins stack to store objects of instanciated plugin classes
     * @access private
     * @since 0.1
     * @see SandboxPluginmanager::load()
     **/
    private $_plugins = array();
    
    /**
     * @var array $_includes stack to store absolute file names of plugin classes which have been included (PHP include_once)
     * @access private
     * @since 0.1
     * @see SandboxPluginmanager::need()
     **/
    private $_includes = array();
    
    /**
     * @var array $_subscriptions stack to store all event handler subscriptions to Sandbox events
     * @access private
     * @since 0.1
     * @see SandboxPluginmanager::subscribe()
     **/
    private $_subscriptions = array();
    
    /**
     * @var Sandbox $_sandbox Sandbox object
     * @access private
     * @since 0.1
     **/
    private $_sandbox = null;
    
    /**
     * Sandbox Plugin Manager constructor
     *
     * @param Sandbox $sandbox Sandbox object
     *
     * @access private
     * @since 0.1
     **/
    public function __construct(Sandbox $sandbox)
    {
        $this->_sandbox = $sandbox;
    }
    
    
    /**
     * Plugin Loader
     *
     * Instanciates a plugin class and stores the returned object to the plugin stack
     * (@see SandboxPluginmanager:$_plugins). Uses the plugin includer
     * (@see SandboxPluginmanager:need()).
     *
     * @param string $pluginname Plugin name relative to one of the stored plugin folders (without .php)
     *
     * @return SandboxPlugin instance of plugin class
     *
     * @access public
     * @since 0.1
     *
     * @throws Exception 'Plugin %name% is not available!'
     **/
    public function load($pluginname = null)
    {
        if (!$pluginname || !is_string($pluginname)) return false;
        
        if (!isset($this->_plugins[$pluginname])) {
            // plugin is not active, load it
            if ($pluginpath = $this->need($pluginname)) {
            
                // get class name (because pluginname could be 'foldername/pluginname')
                $elements = explode(DIRECTORY_SEPARATOR, $pluginname);
                $lastElementIndex = count($elements) - 1;
                $classname = $elements[$lastElementIndex];
            
                eval('$this->_plugins["'.$pluginname.'"] = new '.$classname.'($this->_sandbox, $pluginpath);');
            }
        }
        
        // plugin should be active now
        
        if (isset($this->_plugins[$pluginname])) {
            return $this->_plugins[$pluginname];
        } else {
            throw new Exception('Plugin \''.$pluginname.'\' is not available!');
            return false;
        }

    }
    
    /* include a plugin:
       * include file (require) but do not instanciate class
       * return absolute path to plugin or false */
    /**
     * Plugin Includer
     *
     * Extract absolute file name of plugin by search in assigned plugin folders
     * (@see SandboxPluginmanager::_search()) and includes it.
     *
     * @param string $pluginname Plugin name relative to one of the stored plugin folders (without .php)
     *
     * @return mixed absolute path to directory where plugin is located, or false when file not found
     *
     * @access public
     * @since 0.1
     **/
    public function need($pluginname = null)
    {
        if (!$pluginname || !is_string($pluginname)) return false;
        
        // must be a file
        if (($pluginfile = $this->_search($pluginname.'.php')) && !is_dir($pluginfile)) {
            
            // already included?
            if (in_array($pluginfile, $this->_includes) === false) {
                // include plugin class
                include_once $pluginfile;
                // add to inludes
                $this->_includes[] = $pluginfile;
            }
            // return absolute directory of plugin class
            return dirname($pluginfile);
            
        } else {
            return false;
        }
    }
    
    /**
     * Add plugin folder
     *
     * Method to add new folders to the plugin environment where plugin classes
     * can be located for the intern search. You can add one specific folder
     * and also its sub folders.
     *
     * @param string  $folder folder, absolute path or relative to sandbox root
     * @param boolean $sub    set to true when sub folders should be inlcuded (false is default)
     *
     * @return boolean true (folder was successfully added) or false (folder was not added)
     *
     * @access public
     * @since 0.1
     **/
    public function addFolder($folder = null, $sub = false)
    {
    
        if (!$folder || !is_string($folder)) return false;
        
        if (substr($folder, 0, 1) != DIRECTORY_SEPARATOR) // local path, prefix sandbox path
            $folder = SANDBOX_PATH . $folder;
            
        // beautify path
        $folder = realpath($folder).DIRECTORY_SEPARATOR;

        // folder is already part of the environment
        if (in_array($folder, $this->_environment)) return true;
            
        if (is_dir($folder) && is_readable($folder)) {
            // add folder to environment
            $this->_environment[] = $folder;
            
            // add also sub folders?
            if ($sub === true) $this->_scan($folder, true);

            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Event subscriber
     *
     * Use this method to subscribe event handlers to events. All subscriptions
     * are stored in a stack for every event. Right now it is like first come
     * first serve (fifo). If an event handler is subscribed again, its place in
     * the stack is moved to the end.
     *
     * @param string $eventname   name of event
     * @param mixed  $pluginclass name of plugin class as string or object instance of plugin class
     * @param string $method      name of class method which is the event handler
     *
     * @return boolean true for success, or false
     *
     * @access public
     * @since 0.1
     **/
    public function subscribe($eventname = null, $pluginclass = null, $method = null)
    {
        if (!$eventname || !is_string($eventname)) return false;
        if (!$pluginclass) return false;
        if (!$method || !is_string($method)) return false;
        
        if (is_string($pluginclass)) {
            $classname = $pluginclass;
        } elseif (is_object($pluginclass)) {
            $classname = get_class($pluginclass);
            $this->_plugins[$classname] = $pluginclass;
        } else {
            return false;
        }
    
        if (isset($this->_subscriptions[$eventname]) &&
            (false !== $stackIndex = array_search(array('class'=>$classname,'method'=>$method), $this->_subscriptions[$eventname]))) {
        
            // event handler is on stack for event, delete it
            unset($this->_subscriptions[$eventname][$stackIndex]);
        }
        
        $this->_subscriptions[$eventname][] = array('class'=>$classname,'method'=>$method);
        //debug echo '<div><pre>';print_r($this->_subscriptions);echo '</pre></div>';
        
        return true;
    
    }
    
    /**
     * Event Publisher
     *
     * It publishes an event by calling all methods stored in the event stack
     * (@see SandboxPluginmanager::subscribe()). It can pass by one optional
     * argument to event handlers.
     *
     * @param string    $eventname name of event
     * @param reference &$arg      optional argument passed by to event handlers
     *
     * @return array $response stack for responses returned by event handlers
     *
     * @access public
     * @since 0.1
     *
     * @todo check for event cycles (and prevent them)
     **/
    public function publish($eventname, &$arg = false)
    {
        // TODO: check for event cycles (and prevent them)
    
        if (!$eventname || !is_string($eventname)) return false;

        $response = array(); // array to store returns

        if (!isset($this->_subscriptions[$eventname]) || count($this->_subscriptions[$eventname]) == 0)
            return $response; // return empty response array when no event handlers available for the event

        $handlerStack = $this->_subscriptions[$eventname];
        sort($handlerStack, SORT_NUMERIC);
        //debug echo '<div><pre>';print_r($this->_subscriptions);echo '</pre></div>';
        //debug print_r($handlerStack);
        
        foreach ($handlerStack as $eventhandler) {
            
            // create index from class::method for response array
            $i = implode('::', $eventhandler);

            // get instance of plugin class
            try {
            
                $pluginclass = $this->load($eventhandler['class']);
            
            } catch (Exception $e) {
                $pluginclass = false;
                $response[$i] = $e;
            }
            
            // call method and save response
            if ($pluginclass) {

                try {
                    $r = null;
                    //debug echo '<p>$r = '.get_class($pluginclass).'->'.$eventhandler['method'].'($arg);</p>';
                    eval('$r = $pluginclass->'.$eventhandler['method'].'($arg);');
                    $response[$i] = $r;
                } catch (Exception $e) {
                    $response[$i] = $e;
                }
            }
            
        }
        
        return $response;
    }
    
    /* init environment */
    /*
    private function _init()
    {
    
    }
    */
    
    /**
     * Folder scanner
     *
     * It opens directory and read all sub directories to add them as plugin
     * folders (@see SandboxPluginmanager::addFolder).
     *
     * @param string  $folder absolute path name of folder to scan
     * @param boolean $sub    scan also sub folders? true|false
     *
     * @return mixed void or false (directory cannot be opened)
     *
     * @access private
     * @since 0.1
     **/
    private function _scan($folder, $sub)
    {
        if ($dir = @opendir($folder)) {
            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..' && is_dir($folder.$file)) {
                    $this->addFolder($folder.$file, $sub);
                }
            }
            return;
        } else {
            return false;
        }
    }
    
    /**
     * Plugin searcher
     *
     * It searches for file or directory in environment and returns absolute
     * (file|path) name including path of the the first match or false, if it is
     * not found.
     *
     * @param string $file file ore path name which should be searched for in the plugin environment
     *
     * @return mixed absolue path as string or falso, if it is not found or not readable
     *
     * @access private
     * @since 0.1
     **/
    private function _search($file)
    {
        // test for an absolute file name
        if (is_readable($file)) return realpath($file);
        
        // test for a relative filename in the environment
        $env = $this->_environment;
        reset($env);
        
        foreach ($env as $dir) {
            if (is_readable($dir.$file)) return realpath($dir.$file);
        }
        
        // file not found
        return false;
    }
    
    /**
     * Magic method to have fast access to plugins
     *
     * It tries to load plugin and returns its response (@see SandboxPluginmanager::load()).
     * Do not use this method directly, please use instead
     * $SandboxPluginmanager->PluginClassName
     *
     * @param string $plugin plugin name
     *
     * @return mixed
     *
     * @access public
     * @since 0.1
     **/
    public function __get($plugin)
    {
        return $this->load($plugin);
    }
}

/**
 * Sandbox Plugin
 *
 * This is the Sandbox plugin class which may be extended by other Sandbox plugins.
 * It provides interfaces to the objects for the Sandbox, content store and the
 * Sandbox Plugin manager. It also saves the absolute path where the plugin is
 * located.
 *
 * Use this for your plugin:
 *    class YourPluginClass extends SandboxPlugin { // your stuff }
 *
 * @category   SPCMS
 * @package    Sandbox-Core
 * @subpackage Sandbox-Plugin
 * @author     Michael Haschke, eye48.com
 * @license    http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @link       http://http://code.google.com/p/sandbox-publisher-cms
 * @since      0.1
 * 
 **/
class SandboxPlugin
{

    /**
     * @var Sandbox $sandbox Sandbox object instance
     * @access public
     * @since 0.1
     **/
    public $sandbox = null;
    
    /**
     * @var SandboxContent $content Object instance of Sandbox content store
     * @see SandboxContent
     * @access public
     * @since 0.1
     **/
    public $content = null;
    
    /**
     * @var SandboxPluginmanager $pm Interface to Sandbox Plugin manager
     * @see SandboxPluginmanager
     * @access public
     * @since 0.1
     **/
    public $pm = null;
    
    /**
     * @var string $path absolute name of path where file of plugin class is located
     * @access protected
     * @since 0.1
     **/
    protected $path = null;
    
    /**
     * Sandbox Plugin constructor
     *
     * Construstor method, please do not overwrite this method in your plugin class!
     * To run your stuff at instantiation please use the _init method.
     * {@see SandboxPlugin::_init()}
     *
     * @param Sandbox $sandbox    Sandbox object instance
     * @param string  $pluginpath Absolute path where plugin class is located
     *
     * @access public
     * @since 0.1
     **/
    final public function __construct(Sandbox $sandbox, $pluginpath)
    {
        $this->path = rtrim($pluginpath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $this->sandbox = $sandbox;
        $this->content = $sandbox->content;
        $this->pm = $sandbox->pm;
        
        $this->init();
    }
    
    /**
     * Plugin Init-Process
     *
     * Overwrite this method to run your stuff when the plugin is initiated. It
     * is called from the constructor method. Please call parent method at
     * first: parent::_init();
     *
     * @return void
     *
     * @access protected
     * @since 0.1
     **/
    protected function init()
    {
        return;
    }

}

?>
