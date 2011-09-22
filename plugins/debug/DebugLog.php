<?php
/**
 * DebugLog Plugin
 *
 * Listens to (log message) events, to save and echo log messages
 *
 * @category  SPCMS
 * @package   Sandbox-Plugins
 * @author    Michael Haschke, http://eye48.com
 * @license   http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 */
class DebugLog extends SandboxPlugin
{
    protected $log_messages = array();

    protected function init()
    {
        $this->pm->subscribe('sandbox_add_log_message', $this, 'addMessage');

        if (!defined('IS_PRODUCTION_INSTANCE'))
        {
            $this->pm->subscribe('sandbox_end_of_template_header', $this, 'printCssStyles');
            $this->pm->subscribe('sandbox_end_of_template_body', $this, 'output');
        }
    }

    public function addMessage($msg)
    {
        $this->log_messages[] = $msg;
    }

    public function printCssStyles()
    {
        echo '
            <style type="text/css">
                #SPCSM_DebugLog
                {
                    position:fixed;
                    left:1em;
                    top:0;
                    background:#000; color:#fff;
                    padding:0.5em;
                    font-family:monospace;
                    border-width:3px;
                    border-color:#fff;
                    border-style:none double double double;
                }
                #SPCSM_DebugLog:hover
                {
                    overflow:auto;
                    right:1em;
                    bottom:1em;
                }

                #SPCSM_DebugLog pre
                {
                    display:none;
                }
                #SPCSM_DebugLog:hover pre
                {
                    display:block;
                }
            </style>'.PHP_EOL;
    }

    public function output()
    {
        if (count($this->log_messages)>0)
        {
            echo '<div id="SPCSM_DebugLog"><strong>DEBUGINFO</strong>'.PHP_EOL;
            echo('<pre>'.print_r($this->log_messages, true).'</pre>').PHP_EOL;
            echo '</div>'.PHP_EOL;
        }
    }
}
?>
