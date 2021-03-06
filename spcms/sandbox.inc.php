<?php
/**
 * Sandbox Publisher 0.1
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
 *
 * WEBSITES
 *
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
 * @link       https://github.com/haschek/Sandbox-Publisher-CMS
 * @since      0.1
 * 
 **/
class Sandbox
{
    /**
     * @var $config Sandbox configuration array from config file
     * @access protected
     * @since 0.1
     **/
    private $config = array();
    
    /**
     * @var $file last file which was parsed successfully
     * @access public
     * @since 0.1
     **/
    public $file = null;
    
     /**
     * @var $parsed signals that a file was already parsed, intented for plugins
     * to prevent parsing 
     * @access public
     * @since 0.1
     **/
    public $parsed = false;
    
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
     * @var SandboxCache $cache Sandbox Cache object class
     * @access public
     * @since 0.1
     **/
    public $cache = null;

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
    
    /**
     * @var string $layoutname layout file name, absolute path
     * @access public
     * @since 0.1
     **/
    public $layoutname = null;
    
    // maybe we need this later
    // config
    // private var $_config = array();
    
    /**
     * Sandbox costructor
     *
     * The constructor creates the objects for Sandbox content store and the
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
        // configuration must be stored in a array
        if (!is_array($config)) $config = array();

        // save configuration
        $this->config = $config;

        // create content object
        $this->content = new SandboxContent($this);
        
        // create plugin environment
        $this->pm = new SandboxPluginmanager($this);
        
        // create cache manager
        if (isset($config['cache']) && is_array($config['cache']))
        {
            $cachemaxage = (isset($config['cache']['age'])) ? $config['cache']['age'] : 0;
            $cachefolder = (isset($config['cache']['folder'])) ? $config['cache']['folder'] : null;
            $cachetshift = (isset($config['cache']['displacement'])) ? $config['cache']['displacement'] : 0;
            $this->cache = new SandboxCache($cachemaxage, $cachefolder, $cachetshift);
        }
        else
        {
            $this->cache = new SandboxCache();
        }
        
        // add pluginfolders
        if (isset($config['plugin']['folder']) && is_array($config['plugin']['folder'])
            && count($config['plugin']['folder']) > 0) {
            
            $pluginfolders = implode(PATH_SEPARATOR, $config['plugin']['folder']);
            $pluginfolders = explode(PATH_SEPARATOR, $pluginfolders);
            foreach ($pluginfolders as $pluginfolder) {
                $this->pm->addFolder($pluginfolder);
            }
            
        }
        
        // add template folders
        if (isset($config['template']['folder']) && is_array($config['template']['folder'])
            && count($config['template']['folder']) > 0) {
            
            foreach ($config['template']['folder'] as $templatefolder) {
                $this->templateAddFolder($templatefolder);
            }
        }
        
        // set layout
        if (isset($config['template']['layout']) && $config['template']['layout']) {
            $this->templateSetLayout($config['template']['layout']);
        }
            
        // set template
        if (isset($config['template']['name']) && $config['template']['name']) {
            $this->templateSetName($config['template']['name']);
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
     * @param boolean $eval evaluate PHP code
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
    public function parse($file, $eval = true, $expand = true)
    {
        $php = false;
        $phpcode = null;
        $varmatch = array();
        $varKey = 'SANDBOX';
        $varValue = null;
        $this->parsed = false;
    
        if (is_file($file) && !is_dir($file) && is_readable($file)) {
        
            /* EVENT sandbox_parse_start
             * @param String    $file   name of file (server path)
             */
            $this->pm->publish('sandbox_parse_start', $file);
            
            // testing $parsed, plugin may set it to prevent parsing
            if ($this->parsed === false) {
            
                // file_get_contents
                $content = file($file);
                foreach ($content as $line) {
                    if (rtrim($line) == '<?php') {
                        $php = true;
                        $phpcode = null;
                    } elseif (rtrim($line) == '?>') {
                        $php = false;
                        // execute php code
                        if (trim($phpcode) && $eval) eval(trim($phpcode));
                    } elseif (preg_match('/^\{([a-z|A-Z|0-9]+[a-z|A-Z|0-9|_]*)\}$/', trim($line), $varmatch)) { // regex for {Varname111}
                        $varKey = $varmatch[1];
                        if ((isset($this->content->$varKey) && $expand === false))
                        {
                            $varKey = null;
                        }
                    } else {
                        if ($php === true) {
                            $phpcode .= $line;
                        } elseif ($varKey) {
                            $this->content->$varKey .= $line;
                        }
                    }
                }

                $this->file = $file;
                $this->parsed = true;

                /* EVENT sandbox_parse_end
                 * @param String    $file   name of file (server path)
                 */
                $this->pm->publish('sandbox_parse_end', $file);
            }
        
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

            /* EVENT sandbox_flush_start
             */
            $this->pm->publish('sandbox_flush_start');

            // check for layout
            if ($this->layoutname)
            {
                $this->runninglayout = true;
                include_once $this->layoutname;
            } else {
                // no layout defined
                include_once $this->templatename;
            }

            /* EVENT sandbox_flush_end
             */
            $this->pm->publish('sandbox_flush_end');

            return true;
        } else {
            throw new Exception("No template assigned!");
            return false;
        }
    }
    
    /**
     * Template output
     *
     * Runs the PHP template file to print out the content from inside a layout
     * template.
     *
     * @return boolean true for template has been included
     *
     * @since 0.1
     * @access public
     *
     * @throws Exception 'Using output() method is only allowed in a layout template!'
     **/
    private function output()
    {
        // test for calling this from a layout template
        if (isset($this->runninglayout) && $this->runninglayout === true)
        {
            unset($this->runninglayout);
            include_once $this->templatename;
            return true;
        } else {
            throw new Exception("Using output() method is only allowed in a layout template!");
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
     * template folder stack (@see Sandbox::templateAddFolder) and save it. A
     * template defines the output for the content.
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
        $this->templatename = $this->templateSearch($name);
    
        if (!$this->templatename) {
            throw new Exception("Template '".$name."' was not found or is not readable.");
            return false;
        }
        
        return $this->templatename;
    }
    
    /**
     * Set layout
     *
     * Search the name of the layout template in the folders which are stored in
     * the template folder stack (@see Sandbox::templateAddFolder) and save it.
     * A layout defines the outer sleeve output beyond the specific template.
     *
     * @param string $name name of th layout template without extension (.php)
     *
     * @return string absolute file name of layout inclusive extension
     *
     * @since 0.1
     * @access public
     *
     * @throws Exception 'Layout %name% was not found or is not readable.'
     **/
    public function templateSetLayout($name = null)
    {
        $this->layoutname = $this->templateSearch($name);
    
        if (!$this->layoutname) {
            throw new Exception("Layout '".$name."' was not found or is not readable.");
            return false;
        }
        
        return $this->layoutname;
    }
    
    /**
     * Include partial template
     *
     * A partial template can be used several times, best used for same outputs
     * with different data.
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
    public function templatePartial($name = null, $vars = array())
    {
        extract($vars, EXTR_PREFIX_INVALID, 'var');
    
        if ($tpl = $this->templateSearch($name)) {
            include $tpl;
        } else {
            throw new Exception("Template '".$name."' was not found or is not readable.");
            return false;
        }
        
        return $tpl;
    }

    /**
     * Search a template
     *
     * It searchs the template in folders from template folder stack.
     *
     * @param string $name name of template file without extension (.php)
     *
     * @return string absolute file name of template inclusive extension
     *
     * @since 0.1
     * @access public
     **/
    public function templateSearch($name = null)
    {
        $tpl = null;
        
        if (substr($name, 0, 1) == DIRECTORY_SEPARATOR) {
            // check absolute template name
            if (is_readable($name.'.php')) {
                $tpl = $name.'.php';
            }
            
        } else {
            // search relative template name in temp dirs from folder stack
            foreach ($this->templatefolders as $folder) {
                if (is_readable($folder.$name.'.php')) {
                    $tpl = $folder.$name.'.php';
                    break;
                }
            }
        }
    
        
        return $tpl;
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
        echo trim($this->content->$var);
    }
    
    /**
     * Get configuration
     *
     * @return array configuration
     *
     * @since 0.1
     * @access public
     **/
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Magic method to call a non existing method
     *
     * Calls of non existing methods will lead to triggering an event, the
     * event name is dynamically created with 'sandbox_'+methodname+'_call'
     *
     * @param string $name name of method
     * @param array  $args arguments
     *
     * @return void
     *
     * @since 0.1
     * @access private
     *
     * @publish event sandbox_'methodname'_call
     **/
    public function __call($name, $args)
    {
        /* EVENT sandbox_'methodname'_call
        * @param array $args arguments for called method
        */
        return $this->pm->publish('sandbox_'.$name.'_call', $args);
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
 * @link       https://github.com/haschek/Sandbox-Publisher-CMS
 * @since      0.1
 * 
 **/
class SandboxContent
{
    /**
     * @var Sandbox $sandbox Sandbox object instance
     * @access private
     * @since 0.2
     **/
    private $_sandbox = null;

    /**
     * @var array $_c array stack where all content vars are stored in
     * @access protected
     * @since 0.1
     **/
    protected $_c = array();

    /**
     * @var boolean $_useFilters Only use activated filter on content items if
     *      this switch is set to true (default).
     * @access protected
     * @since 0.2
     **/
    protected $_processFilters = true;

    /**
     * @var array $_activeFilters array stack containing names of active content
     *      filters
     * @access protected
     * @since 0.2
     **/
    protected $_activeFilters = array();

    /**
     * @var array $_disposableFilters array stack containing names of filters
     *      only used for the next requested content item
     * @access protected
     * @since 0.2
     **/
    protected $_disposableFilters = array();

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
    public function __construct(Sandbox $sandbox)
    {
        $this->_sandbox = $sandbox;
    }

    /**
     * Get content of variable, processed by referenced filters, or without
     * filter processing.
     *
     * @param string $name name of content item
     * @param mixed $useFilters - array with filter names, or true|false to
     *        enable/disable previously configured filters
     *
     * @return mixed
     *
     * @since 0.2
     * @access public
     **/
    public function getItem($name, $useFilters = false)
    {
        if (isset($this->_c[$name]))
        {
            return $this->filterVar($this->_c[$name], $useFilters);
        }
        else
        {
            return null; // return empty string
        }

    }

    /**
     * Get content of variable, processed by referenced filters, or without
     * filter processing.
     *
     * @param mixed $contentvar content to be filtered
     * @param mixed $useFilters - array with filter names, or true|false to
     *        enable/disable previously configured filters
     *
     * @return mixed
     *
     * @since 0.2
     * @access public
     **/
    public function filterVar($contentvar, $useFilters = false)
    {
        $content_of_item =  $contentvar;

        if (is_array($useFilters))
        {
            // add filters to process them only once
            // and save return state (true or false)
            $useFilters = $this->setDisposableFilters($useFilters);
        }

        if ($useFilters === false)
        {
            return $content_of_item;
        }

        // get filters

        $filters_to_process = array();

        if (count($this->_disposableFilters) > 0)
        {
            // use disposable filters only once
            $filters_to_process = $this->_disposableFilters;
            $this->clearDisposableFilters();
        }
        elseif (count($this->_activeFilters) > 0)
        {
            $filters_to_process = $this->_activeFilters;
        }

        if (count($filters_to_process) === 0)
        {
            // no filters to process
            return $content_of_item;
        }

        // process filters
        // using events for this, so listeners can alter the content

        foreach ($filters_to_process as $filtername)
        {
            // get type of content item (in loop b/c type can change)
            $type_of_item = gettype($content_of_item);
            // create event name
            $filter_event_name = 'sandbox_contentfilter_'.$type_of_item.'_'.$filtername;
            // publish event
            $this->_sandbox->pm->publish($filter_event_name, $content_of_item);
        }

        return $content_of_item;

    }

    /**
     * Magic method to get variable, shortcut for getItem(varname, true).
     *
     * Do not use this directly, just use $SandboxContentObject->varname;
     *
     * @param string $var variable name
     *
     * @return mixed
     *
     * @since 0.1
     * @access public
     **/
    public function __get($var)
    {
        if (is_string($var)) {
            return $this->getItem($var, true);
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
     * @access public
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
     * @access public
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
     * @access public
     **/
    public function __unset($var)
    {
        unset($this->_c[$var]);
        return;
    }

    /**
     * Magic method to get a filtered content item
     *
     * Do not use this directly, just use unset($SandboxContentObject->varname);
     *
     * @param string $varname name of content item
     * @param array $filterstack containing filter names as strings
     *
     * @return mixed
     *
     * @since 0.2
     * @access public
     **/
    public function __call($varname, $filterstack)
    {
        if (count($filterstack) == 0)
        {
            return $this->getItem($varname, false);
        }

        if ($this->setDisposableFilters($filterstack))
        {
            return $this->getItem($varname, true);
        }

        return null;
    }

    /**
     * Method to add one filter to the end of the filter stack, processed on
     * requested content items.
     *
     * @param string $name name of filter
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function addFilter($name)
    {
        if (is_string($name))
        {
            $this->_activeFilters[] = $name;
            return true;
        }

        return false;
    }

    /**
     * Method to remove one filter from the filter stack.
     *
     * @param string $name name of filter
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function removeFilter($name)
    {
        if (!is_string($name))
        {
            return false;
        }
        
        $this->_activeFilters = array_diff($this->_activeFilters, array($name));
        
        return true;
    }

    /**
     * Method to add various filters to the end of the filter stack, processed
     * on requested content items.
     *
     * @param array $names containing filter names as strings
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function addFilters($names)
    {
        if (!is_array($names))
        {
            return false;
        }
        elseif (count($names) == 0)
        {
            return true;
        }

        $list_of_returns = array();

        foreach ($names as $filtername)
        {
            $list_of_returns[] = $this->addFilter($filtername);
        }

        $list_of_different_returns = array_unique($list_of_returns);

        if (count($list_of_different_returns) == 1)
        {
            // all filters were added successfully (all true)
            // or all addings failed (all false)
            return $list_of_different_returns[0];
        }
        else
        {
            // mix of successfull and failed addings
            return false;
        }

    }

    /**
     * Method to remove various filters from the filter stack.
     *
     * @param array $names containing filter names as strings
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function removeFilters($names)
    {
        if (!is_array($names))
        {
            return false;
        }
        elseif (count($names) == 0)
        {
            return true;
        }

        $list_of_returns = array();

        foreach ($names as $filtername)
        {
            $list_of_returns[] = $this->removeFilter($filtername);
        }

        $list_of_different_returns = array_unique($list_of_returns);

        if (count($list_of_different_returns) == 1)
        {
            // all filters were removed successfully (all true)
            // or all removings failed (all false)
            return $list_of_different_returns[0];
        }
        else
        {
            // mix of successfull and failed removings
            return false;
        }

    }

    /**
     * Method to add various filters to the filter stack, after the filter stack
     * was cleared first.
     *
     * @param array $names containing filter names as strings
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function setFilters($names)
    {
        if (!is_array($names))
        {
            return false;
        }

        $this->_activeFilters = array(); // clear stack

        return $this->addFilters($names);
    }

    /**
     * Remove all filters from all filter stacks.
     *
     * @return void
     *
     * @since 0.2
     * @access public
     **/
    public function clearFilters()
    {
        $this->_activeFilters = array();
        $this->clearDisposableFilters();
        return;
    }

    /**
     * Method to set a filter stack what will be used only on the next requested
     * content item, this stack will be cleared straight after it.
     *
     * @param array $names containing filter names as strings
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function setDisposableFilters($names)
    {
        if (!is_array($names))
        {
            return false;
        }

        foreach ($names as $filtername)
        {
            if (is_string($filtername))
            {
                $this->_disposableFilters[] = $filtername;
            }
        }

        return true;
    }
    
    public function clearDisposableFilters()
    {
        $this->_disposableFilters = array();
        return;
    }

    /**
     * Use filter stack to process
     *
     * @param boolean true to process filters on requested content items, false
     *        to ignore filters
     *
     * @return boolean
     *
     * @since 0.2
     * @access public
     **/
    public function setFilterProcessing($bool)
    {
        if (!is_bool($bool))
        {
            return false;
        }

        $this->_processFilters = $bool;

        return true;
    }

    // TODO: comment this
    public function getKeys()
    {
        return array_keys($this->_c);
    }
    
    // TODO: comment this
    public function getArray()
    {
        return $this->_c;
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
 * @link       https://github.com/haschek/Sandbox-Publisher-CMS
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
        
        // TODO: better work with foldername_pluginname to get really distinct names for plugin classes
        // get class name (because pluginname could be 'foldername/pluginname')
        $elements = explode(DIRECTORY_SEPARATOR, $pluginname);
        $lastElementIndex = count($elements) - 1;
        $classname = $elements[$lastElementIndex];
    
        if (!isset($this->_plugins[$classname])) {
            // plugin is not active, load it
            if ($pluginpath = $this->need($pluginname)) {
            
                $this->_plugins[$classname] = new $classname($this->_sandbox, $pluginpath);
            }
        }
        
        // plugin should be active now
        
        if (isset($this->_plugins[$classname])) {
            return $this->_plugins[$classname];
        } else {
            throw new Exception('Plugin \''.$classname.'\' is not available!');
            return false;
        }

    }
    
    /**
     * Plugin Includer
     *
     * Extract absolute file name of plugin by search in assigned plugin folders
     * (@see SandboxPluginmanager::_search()) and includes plugin code.
     *
     * @param string $pluginname Plugin name relative to one of the stored plugin folders (without .php)
     *
     * @return mixed absolute path to directory where plugin is located, or false when file not found
     *
     * @access public
     * @since 0.1
     *
     * @throws Exception 'Plugin %filename% is not found!'
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
            throw new Exception('Plugin \''.$pluginname.'.php'.'\' is not found!');
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
    
        if (!$folder || !is_string($folder)) {
            throw new Exception('Cannot add plugin folder: name is empty or not a string!');
            return false;
        }
        
        if (substr($folder, 0, 1) != DIRECTORY_SEPARATOR) {
            // assume local path, prefix sandbox path
            $folder = SANDBOX_PATH . $folder;
        }
            
        if (is_dir($folder)) {

            // beautify path
            $folder = realpath($folder).DIRECTORY_SEPARATOR;

            // folder is already part of the environment
            if (in_array($folder, $this->_environment)) return true;

            if (is_readable($folder)) {

                // add folder to environment
                $this->_environment[] = $folder;

                // add also sub folders?
                if ($sub === true) $this->_scan($folder, true);

                return true;
            } else {
                throw new Exception('Cannot add plugin folder: '.$folder.' is not readable!');
                return false;
            }

        } else {
            throw new Exception('Cannot add plugin folder: '.$folder.' does not exists!');
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
        
        // if event handler is on event stack then delete old position first
        $this->unsubscribe($eventname, $classname, $method);
        
        // add event handler to event stack
        $this->_subscriptions[$eventname][] = array('class'=>$classname,'method'=>$method);
        
        return true;
    
    }
    
    /**
     * Event unsubscriber
     *
     * Use this method to unsubscribe event handlers from events.
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
    public function unsubscribe($eventname = null, $pluginclass = null, $method = null)
    {
        if (!$eventname || !is_string($eventname)) return false;
        if (!$pluginclass) return false;
        if (!$method || !is_string($method)) return false;
        
        if (is_string($pluginclass)) {
            $classname = $pluginclass;
        } elseif (is_object($pluginclass)) {
            $classname = get_class($pluginclass);
        } else {
            return false;
        }

        if (isset($this->_subscriptions[$eventname]) &&
            (false !== $stackIndex = array_search(array('class'=>$classname,'method'=>$method), $this->_subscriptions[$eventname]))) {
        
            // event handler is on stack for event, delete it
            unset($this->_subscriptions[$eventname][$stackIndex]);
        }
        
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
                    $r = $pluginclass->$eventhandler['method']($arg);
                    $response[$i] = $r;
                } catch (Exception $e) {
                    if (!defined('IS_PRODUCTION_INSTANCE') || IS_PRODUCTION_INSTANCE !== true) {
                        throw $e;
                    }
                    $response[$i] = $e;
                }
            }
            
        }
        
        return $response;
    }
    
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
    
    // TODO: comment this
    public function countActivePlugins()
    {
        return count($this->_plugins);
    }
    
    // TODO: comment this
    public function isActive($plugin)
    {
        return isset($this->_plugins[$plugin]);
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
 * @link       https://github.com/haschek/Sandbox-Publisher-CMS
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
     * @var SandboxCache $cache Sandbox Cache object class
     * @access public
     * @since 0.1
     **/
    public $cache = null;

    /**
     * @var string $path absolute name of path where file of plugin class is located
     * @access protected
     * @since 0.1
     **/
    protected $path = null;
    
    /**
     * @var Array $config Plugin configuration as key-value pairs
     * @access protected
     * @since 0.1
     **/
    protected $config = array();
    
    /**
     * Sandbox Plugin constructor
     *
     * Construstor method, please do not overwrite this method in your plugin class!
     * To run your stuff at instantiation please use the init method.
     * {@see SandboxPlugin::init()}
     *
     * @param Sandbox $sandbox    Sandbox object instance
     * @param string  $pluginpath Absolute path where plugin class is located
     *
     * @access public
     * @since 0.1
     **/
    final public function __construct(Sandbox $sandbox, $pluginpath)
    {
        // interfaces to API
        
        $this->path = rtrim($pluginpath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $this->sandbox = $sandbox;
        $this->content = $sandbox->content;
        $this->pm = $sandbox->pm;
        $this->cache = $sandbox->cache;
        
        // plugin config from user configuration (done in bootstrap)
        
        $config = $this->sandbox->getConfig();
        $classname = get_class($this);
        if (isset($config[$classname]))
        {
            $this->config = $config[$classname];
        }
        unset($config);
        unset($classname);
        
        $this->init();
    }
    
    /**
     * Plugin Init-Process
     *
     * Overwrite this method to run your stuff when the plugin is initiated. It
     * is called from the constructor method. Please call parent method at
     * first: parent::init();
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
    
    /**
     * Return plugin path
     *
     * @return string  $pluginpath Absolute path where plugin class is located
     *
     * @access public
     * @since 0.1
     **/
    final public function getPath()
    {
        return $this->path;
    }

    /**
     * Add log message via event handler, adding memory usage at this point
     *
     * @return string $msg Log message
     *
     * @access public
     * @since 0.2
     **/
    final public function addLogMessage($msg)
    {
        $msg = $msg.' -- memory '.intval(memory_get_usage(true)/1024).'kb';
        $this->sandbox->pm->publish('sandbox_add_log_message', $msg);
        return;
    }

}

/**
 * Sandbox Cache
 *
 * This class handles caching for variables and outputs, also content objects
 * can be cached.
 *
 * @category   SPCMS
 * @package    Sandbox-Core
 * @subpackage Sandbox-Includes
 * @author     Michael Haschke, eye48.com
 * @license    http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @link       https://github.com/haschek/Sandbox-Publisher-CMS
 * @since      0.1
 * 
 **/
class SandboxCache
{

    /**
     * @var int $age the maximum age of a valid cache in seconds
     * @access protected
     * @since 0.1
     **/
    protected $age = 0; // default: no caching (valid age of 0 seconds)
    
    /**
     * @var float $displacement to randomize the maximum age for cache files (pos./neg. time displacement),
     *          this is intended to use with lot of cached parts of data (to prevent invalidation on one point of time),
     *          e.g. maximum age of 60 min and displacement of 0.5 means a random max age of 30 to 90 min
     * @access protected
     * @since 0.1
     **/
    protected $displacement = 0; // default: exact max age, no time displacement
    
    /**
     * @var string $folder absolute server path to cache folder
     * @access protected
     * @since 0.1
     **/
    protected $folder = null; // default: no caching in files (no folder)
    
    /**
     * @var array $_memcache caches as array in memory (to speed up double cache operations)
     * @access private
     * @since 0.1
     **/
    private $_memcache = array();
    
    /**
     * Sandbox Cache costructor
     *
     * The constructor method creates the Sandbox Cache object and it sets
     * configuration for cache folder and maximum age of valid cache files.
     * 
     * @param int $maxage maximum age of valid cache files in seconds
     * @param string $folder path name, absolute or relative to Sandbox Publisher folder
     *
     * @return void
     *
     * @since 0.1
     * @access public
     *
     **/
    public function __construct($maxage = 0, $folder = null, $displacement = 0)
    {
        // set valid age for cache files
        if ($maxage !== 0) $this->setAge($maxage);
        
        // set coefficient for time displacement
        if ($displacement !== 0) $this->setDisplacement($displacement);
        
        // set cache folder
        if ($folder !== null) $this->setFolder($folder);
    }

    /**
     * Set maximum age
     *
     * Must be used to set the maximum age (in seconds) a cache can have to be valid.
     *
     * @param int $age maximum age in seconds
     *
     * @return void
     *
     * @access public
     * @since 0.1
     **/
    public function setAge($age)
    {
        if (is_numeric($age) && intval($age) >= 0) $this->age = intval($age);
        // TODO: throw exception
        
        return;
    }

    /**
     * Set time displacement
     *
     * Used to set a random time shift for the maximum age of valid cache files.
     * Values between 0 and 0.5 are recommended.
     *
     * @param float $shift relative coefficient between 0 and 1
     *
     * @return void
     *
     * @access public
     * @since 0.1
     **/
    public function setDisplacement($shift)
    {
        if (is_float($shift) && $shift >= 0 && $shift < 1) $this->displacement = $shift;
        // TODO: throw exception
        
        return;
    }
    
    /**
     * Set cache folder
     *
     * Must be used to set the folder where caches are saved.
     *
     * @param string $folder path name, absolute or relative to Sandbox Publisher folder
     *
     * @return boolean
     *
     * @access public
     * @since 0.1
     * 
     * @throws Exception 'Cannot use folder for cache files!'
     **/
    public function setFolder($folder)
    {
        if ($folder === null || $folder === '')
        {
            // do not cache in files
            $this->folder = null;
        }
        elseif (!$folder || !is_string($folder))
        {
            throw new Exception("Cannot use folder for cache files: wrong input!");
            return false;
        }
        else
        {
            // relative or absolute path
            if (substr($folder, 0, 1) != DIRECTORY_SEPARATOR) {
                // relative path
                $folder = SANDBOX_PATH.$folder;
            }

            if (is_dir($folder) && is_writable($folder)) {
                $this->folder = rtrim(realpath($folder), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                return true;
            } else {
                throw new Exception("Cannot use folder for cache files: Template folder '".$folder."' not found or not writeable!");
                return false;
            }
        }
    }
    
    /**
     * Write variable to cache
     *
     * This method is used to save a variable and its type to a cache file.
     *
     * @param mixed $var variable to cache, arrays and objects will be serialized
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return boolean
     *
     * @access public
     * @since 0.1
     * 
     * @throws Exception 'Cannot write cache!'
     **/
    public function saveVar($var, $name, $namespace = null, $maxage = null)
    {
        if ($this->age > 0 || $maxage !== null)
        {
            // 1. write cache to memory
            if ($this->_setMemcache($var, $name, $namespace))
            {
                if ($this->folder != null)
                {
                    // 2. write cache to file
                    
                    // serialize (saving var type)
                    $cachedata = serialize($var);
                    
                    // open cache file
                    $cachename = $this->_createCachename($name, $namespace);
                    if ($cachename && $cachefile = @fopen($this->folder.$cachename, 'wb'))
                    {
                        // success
                        // write data to file (without magic_quotes)
                        
                        // magic quotes off
                        $q = ini_get('magic_quotes_runtime');
                        ini_set('magic_quotes_runtime', 0);
                        
                        // write to file
                        if (@fwrite($cachefile, $cachedata) === false)
                        {
                            // cannot write to file
                            throw new Exception('Cannot write cache: cannot write into file '.$this->folder.$this->_createCachename($name, $namespace).' !');
                            return false;
                        }
                        
                        // restore magic quotes configuration 
                        ini_set('magic_quotes_runtime', $q);
                        
                        // close cache file
                        @fclose($cachfile);
                        
                        /* EVENT sandbox_write_cache_to_file
                         * @param Array    
                         */
                        //TODO
                        //$this->pm->publish('sandbox_write_cache_to_file',
                        //                   array('name'=>$name, 'namespace'=>$namespace, 'file'=>$cachefile, 'data'=>$cachedata));

                        return true;
                        
                        /* TODO: locking operations? does make it sense?
                           It seems to provoke a lot of errors/problems?
                           see http://www.php.net/manual/de/function.flock.php
                        */
                    }
                    else
                    {
                        // cannot open cache file
                        throw new Exception('Cannot write cache: cannot open file '.$this->folder.$this->_createCachename($name, $namespace).' to write cache!');
                        return false;
                    }
                }
                else
                {
                    // folder is null, so only write to memcache
                    return true;
                }
            }
            else
            {
                // mem cache must work, so end here
                return false;
            }
        }
        else
        {
            // do not cache anything
            return false;
        }
    }
    
    /**
     * Read variable from cache
     *
     * This method is used to read a variable from a cache file. The type will be recovered.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     * @param int $maxage maximal age of cache in seconds, or -1 for no cache invalidation by time
     * @param int $displacement displacement factor for timeshift of cache invalidation time
     *
     * @return mixed variable as saved type or null (if not cached)
     *
     * @access public
     * @since 0.1
     * 
     * @throws Exception 'Cannot read cache!'
     **/
    public function getVar($name, $namespace = null, $maxage = null, $displacement = null)
    {

        $_displacedMaxage = $this->_displacedAge($maxage, $displacement);

        if ($_displacedMaxage > 0 || $maxage === -1)
        {
            // 1. read cache from memory
            if ($var = $this->_getMemcache($name, $namespace, $maxage))
            {
                return $var;
            }
            // 2. read cache from file
            elseif ($this->folder != null)
            {
                // open cache file if age is valid
                $cachename = $this->_createCachename($name, $namespace);
                if ($cachename)
                {
                    // clear file stats from php cache
                    clearstatcache();
                    
                    // test for valid file
                    if (!is_readable($this->folder.$cachename))
                    {
                        // file doesn't exist or is not readable
                        return false;
                    }
                
                    // test for valid age of cache, or no cach invalidation by time
                    if ($_displacedMaxage < (time() - filemtime($this->folder.$cachename)) && $maxage !== -1)
                    {
                        // cache too old
                        return false;
                    }
                    
                    // open cache file
                    if ($cachefile = @fopen($this->folder.$cachename, 'rb'))
                    {
                        // success
                        // read var from file (without magic_quotes)
                        
                        // magic quotes off, backup configuration
                        $q = ini_get('magic_quotes_runtime');
                        ini_set('magic_quotes_runtime', 0);
                        
                        // get file size
                        $cachesize = filesize($this->folder.$cachename);
                        
                        // read cache data
                        if ($cachesize)
                        {
                            $cachedata = @fread($cachefile, $cachesize);
                            
                            // unserialize (restore var type)
                            $var = unserialize($cachedata);
                        }
                        else
                        {
                            $var = null;
                        }
                        
                        // restore magic quotes configuration 
                        ini_set('magic_quotes_runtime', $q);
                        
                        // close cache file
                        @fclose($cachefile);
                        
                        /* EVENT sandbox_read_cache_from_file
                         * @param Array    
                         */
                        //TODO
                        //$this->pm->publish('sandbox_read_cache_from_file',
                        //                   array('name'=>$name, 'namespace'=>$namespace, 'file'=>$cachefile, 'data'=>$cachedata));

                        return $var;
                        
                        /* TODO: locking operations? does make it sense?
                           It seems to provoke a lot of errors/problems?
                           see http://www.php.net/manual/de/function.flock.php
                        */
                    }
                    else
                    {
                        // file name corrupt or file not readable
                        throw new Exception('Cannot read cache: cannot open '.$this->folder.$cachename.' !');
                        return false;
                    }
                }
                else
                {
                    // cannot create name for cache file
                    throw new Exception('Cannot read cache: cannot create valid file name from "'.$name.'" with namespace "'.$namespace.'"!');
                    return false;
                }

            }
        }
        
        return false;
    }
    
    /**
     * Write output to cache
     *
     * This method is used to save an output buffer to a cache file.
     * All outputs are saved as string variable.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return boolean
     *
     * @access public
     * @since 0.1
     **/
    public function saveOutput($name, $namespace = null)
    {
        // get output string from saven buffer
        $output = ob_get_contents();
        // send output buffer and turn it off
        ob_end_flush();
        
        // save output string to cache
        return $this->saveVar($output, $name, $namespace);
    }
    
    /**
     * Get output from cache
     *
     * This method is used to read an output buffer from a cache file and print it
     * out immediately.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return boolean true for successfull output, false if cache not available
     *
     * @access public
     * @since 0.1
     * @deprecated 0.1
     **/
    public function getOutput($name, $namespace = null, $maxage = null)
    {
        // get cached string and print it out
        $output = $this->getVar($name, $namespace, $maxage);
        if ($output !== false)
        {
            echo $output;
            return true;
        }
        
        // or start to save output to buffer
        ob_start();
        ob_implicit_flush(false);
        return false;
    }
    
    /**
     * Echo output from cache, or record output buffer
     *
     * This method is used to read an output buffer from a cache file and print it
     * out immediately, only if the cached output is still valid. If cache is
     * outdated the method starts to record the following output buffer.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return boolean true for successfull output, false if cache not available
     *
     * @access public
     * @since 0.2
     **/
    public function echoOutputOrRecordIt($name, $namespace = null, $maxage = null)
    {
        // get cached string and print it out
        if ($this->echoOutput($name, $namespace, $maxage))
        {
            return true;
        }
        
        // or start to save output to buffer
        $this->recordOutput();
        return false;
    }
    
    /**
     * Echo output from cache
     *
     * This method is used to read an output buffer from a cache file and print it
     * out immediately, or return false if cached output is outdated.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return boolean true for successfull output, false if cache not available
     *
     * @access public
     * @since 0.2
     **/
    public function echoOutput($name, $namespace = null, $maxage = null)
    {
        // get cached string and print it out
        $output = $this->getVar($name, $namespace, $maxage);
        if ($output !== false)
        {
            echo $output;
            return true;
        }
        
        return false;
    }
    
    /**
     * Start to record output buffer
     *
     * This method is used to start the recording of the following output buffer.
     * That enables safing it later to a cache file.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return boolean true for successfull output, false if cache not available
     *
     * @access public
     * @since 0.2
     **/
    public function recordOutput()
    {
        // or start to save output to buffer
        ob_start();
        ob_implicit_flush(false);
        return;
    }
    
    /**
     * Get cache from memory
     *
     * This method tries to fetch a cache variable from the intern array saved
     * in memory.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     * @param int $maxage maximum age in seconds for valid cache
     *
     * @return mixed variable (as its type) or false (no valid cache in memory)
     *
     * @access private
     * @since 0.1
     **/
    private function _getMemcache($name, $namespace, $maxage)
    {
        $validtime = time() - intval($maxage);
        
        $cachename = $this->_createCachename($name, $namespace);
        if ($cachename && array_key_exists($cachename, $this->_memcache))
        {
            // cache exists in memory
            // validate cache against time
            if ($validtime <= $this->_memcache[$cachename]['time'])
            {
                // cache is valid
                return $this->_memcache[$cachename]['content'];
            }
            else
            {
                // cache is too old
                return false;
            }
        }
        else
        {
            // name for cache cannot be created
            return false;
        }
        
    }
    
    /**
     * Write cache into memory
     *
     * This method will write a cache variable to the intern array saved in memory.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     * @param int $maxage maximum age in seconds for valid cache
     *
     * @return boolean true for success, false if it is not successful
     *
     * @access private
     * @since 0.1
     **/
    private function _setMemcache($var, $name, $namespace)
    {
        if ($cachename = $this->_createCachename($name, $namespace))
        {
            // save var
            $this->_memcache[$cachename]['content'] = $var;
            // save time of cache
            $this->_memcache[$cachename]['time'] = time();
            
            return true;
        }
        else
        {
            // name for cache cannot be created
            return false;
        }
    }
    
    /**
     * Randomize maximum age by configured time displacement coefficient
     *
     * @param int $maxage maximum age for valid cache files (in seconds)
     * @param int $displacement relative displacement factor for timeshift
     *
     * @return int randomized maximum for valid cache time in seconds
     *
     * @access private
     * @since 0.1
     **/
    private function _displacedAge($maxage, $displacement)
    {
        if ($maxage === null) $maxage = $this->age;
        if ($displacement === null) $displacement = $this->displacement;
        $maxage = intval($maxage);
        if (!(is_float($displacement) && $displacement >= 0 && $displacement < 1)) $displacement = 0;

        $age = rand(intval($maxage*(1-$displacement)), intval($maxage*(1+$displacement)));
        
        //die(strval($age));
        return ($age);
    }
    
    /**
     * Create name for cache file
     *
     * This method will create a name for the cache file out of the cache name,
     * its namespace and a spcms-cache extension.
     *
     * @param string $name name for cache (it will be hashed by md5)
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return mixed name as string (success) or false (no success)
     *
     * @access private
     * @since 0.1
     **/
    private function _createCachename($name, $namespace)
    {
        if (false !== $space = $this->_checkNamespace($namespace))
        {
            // namespace is valid
            if ($space !== null) $space .= '_-_';
            return $space.md5($name).'.spcms-cache'; // Better to use extension, maybe cache folder is used by other apps, too.
        }
        else
        {
            // valid namespace cannot be created
            return false;
        }
    }
    
    /**
     * Validate namespace string
     *
     * A namespace must be a alpha-numeric string, so only word- and digit-,
     * underscore- and minus characters, are allowed. It must start with a word-
     * or digit character. This method checks if a namespace is valid.
     *
     * @param string $namespace namespace, only alpha-numeric chars (plus underscored '_' and minus '-') are allowed
     *
     * @return mixed namespace as string or null (success), false (no success)
     *
     * @access private
     * @since 0.1
     **/
    private function _checkNamespace($namespace)
    {
        if ($namespace === null || $namespace === '')
        {
            // no namespace
            return null;
        }
        elseif (is_string($namespace))
        {
            // check namespace
            if (preg_match('/^[\w|\d]+[\w|\d|-|_]*[\w|\d]*$/', $namespace))
            {
                // valid namespace, only word- and digit charcters
                return $namespace;
            }
            else
            {
                // invalid namespace
                return false;
            }
        }
        else
        {
            // wrong input
            return false;
        }
    }

}

?>
