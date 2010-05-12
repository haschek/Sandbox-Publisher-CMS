<?php

// TODO: Description, class + methods
class LanguageChecker extends SandboxPlugin
{

    protected $languagesUser = array();    
    
    protected function init()
    {
        
        $this->languagesUser = $this->createUserPreferences();
        
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
        if ($string) return implode(',', $this->languagesUser);
        return $this->languagesUser;
    }
    
    // returns enabled languages prioritized by user requested languages
    public function getLanguageStack($string = false)
    {
        if (!isset($this->languageStack))
        {
            $requested = $this->getUserPreferences();
            $enabled = $this->listLanguagesEnabled();
            
            // only keep the enabled languages from the requested languages
            $languageStack = array_intersect($requested, $enabled);
            
            // merge that with other enabled languages
            $languageStack = array_unique(array_merge($languageStack, $enabled));
            
            $this->languageStack = $languageStack;
        }
        
        if ($string) return implode(',', $this->languageStack);
        return $this->languageStack;
    }
    
    private function getLanguageRequests()
    {
        // save accepted languages to array
        $accepted = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?explode(',', trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])):array();
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
    
    private function listLanguagesEnabled()
    {
        return (isset($this->config['enabled']) && is_array($this->config['enabled']))?$this->config['enabled']:array();
    }
    
}
