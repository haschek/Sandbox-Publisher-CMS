<?php

class LanguageChecker extends SandboxPlugin
{

    private $config = array();
    private $languages = array();    
    
    protected function init()
    {
        // load user configuration
        $config = $this->sandbox->getConfig();
        if (isset($config['LanguageChecker']))
        {
            // user configuration
            $this->config = $config['LanguageChecker'];
        }

        // Foafpress event handlers for SPCMS
        //$this->pm->subscribe('sandbox_parse_failed', $this, 'LoadResource'); // parameters: event name, class name or instance, event handler method
        
        $this->languages = $this->createUserPreferences();
        
        return;
        
    }
    
    private function createUserPreferences()
    {
        $requested = $this->getLanguageRequests();
        
        $requExtended = array(); // Language array including additional language code (e.g. for en-us it will be add en_US)
        
        foreach($requested as $code)
        {
            // add code
            $requExtended[] = $code;

            if (strlen($code) > 2)
            {
                if (substr($code, 2, 1) == '-')
                {
                    // got xx-xx, add xx_XX
                    $requExtended[] = substr($code, 0, 2).'_'.strtoupper(substr($code, 3));
                }
                elseif (substr($code, 2, 1) == '_')
                {
                    // got xx_XX, add xx-xx
                    $requExtended[] = substr($code, 0, 2).'-'.strtolower(substr($code, 3));
                }

                // add 2char code
                $requExtended[] = substr($code,0, 2);
            }
        }
        
        return array_unique($requExtended);
    }
    
    public function getUserPreferences($string = false)
    {
        if ($string) return implode(',', $this->languages);
        return $this->languages;
    }
    
    private function getLanguageRequests()
    {
        // save accepted languages to array
        $accepted = explode(',', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        $languages = array();
        
        if (count($accepted)>0)
        {
            // extract accepting ratio
            $test_accept = array();
            foreach($accepted as $format)
            {
                $formatspec = explode(';',$format);
                $k = trim($formatspec[0]);
                if (count($formatspec)==2)
                {
                    $test_accept[$k] = trim($formatspec[1]);
                }
                else
                {
                    $test_accept[$k] = 'q=1.0';
                }
            }
            
            // sort by ratio
            arsort($test_accept);
            
            // get the language keys
            $languages = array_keys($test_accept);
        }

        return $languages;
    
    }
    
}

?>
