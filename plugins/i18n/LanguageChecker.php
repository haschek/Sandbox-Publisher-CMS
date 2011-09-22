<?php

// TODO: Description, class + methods
class LanguageChecker extends SandboxPlugin
{

    protected $languagesUser;
    protected $languagesUserExtented;
    protected $languagesApplication;
    protected $languagesApplicationOrderedByUserPreferences;
    
    protected function init()
    {
        $this->languagesUser = $this->createUserLanguageStackByBrowserRequests();
        $this->languagesUserExtented = $this->createUserLanguageStackExtended();
        $this->languagesApplication = $this->listApplicationLanguagesEnabled();
        $this->languagesApplicationOrderedByUserPreferences = $this->createApplicationLanguageStackByUserPreferences();

        $this->addLogMessage('Merged language stack: '. implode(',', $this->getLanguageStackMergedFromUserAndApplication()));
        $this->addLogMessage('Merged language stack (simplified): '. $this->getLanguageStackSimplified(true));

        return;
    }
    
    private function createUserLanguageStackByBrowserRequests()
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

        $this->addLogMessage('Read requested user language stack: '. implode(',', $languages));
        return $languages;

    }

    private function createUserLanguageStackExtended()
    {
        if (!$this->languagesUser)
        {
            $this->languagesUser = $this->createUserLanguageStackByBrowserRequests();
        }

        $languagesRequested = $this->languagesUser;

        // check for manually choosen language by get paramter
        $applanguages = $this->listApplicationLanguagesEnabled();

        if (isset($_GET['lang']))
        {
            $langparameter = $_GET['lang'];
            if (in_array($langparameter, $applanguages))
            {
                $languagesRequested = array_merge(array($langparameter), $languagesRequested);
            }
        }

        $requExtended = array(); // Language array including additional language code (e.g. for en-us it will be add en_US)
        
        foreach($languagesRequested as $code)
        {
            // add code, in lower and uppercase
            $requExtended[] = strtolower($code);
            $requExtended[] = strtoupper($code);

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
            else
            {
                // add doubled code, like xx -> xx-xx, xx_XX
                $requExtended[] = strtolower($code.'-'.$code);
                $requExtended[] = strtolower($code).'_'.strtoupper($code);
            }
        }

        $requExtended = array_unique($requExtended);

        $this->addLogMessage('Created extended user language stack: '. implode(',', $requExtended));
        return $requExtended;
    }
    
    private function listApplicationLanguagesEnabled()
    {
        return (isset($this->config['enabled']) && is_array($this->config['enabled']))?$this->config['enabled']:array();
    }

    // returns enabled languages prioritized by user requested languages
    private function createApplicationLanguageStackByUserPreferences()
    {
        $requested = $this->getLanguageStackUserPreferences();
        $enabled = $this->listApplicationLanguagesEnabled();

        // only keep the enabled languages from the requested languages
        $languageStack = array_intersect($requested, $enabled);

        // merge that with other enabled languages
        $languageStack = array_unique(array_merge($languageStack, $enabled));

        $this->addLogMessage('Created app language stack by user preferences: '. implode(',', $languageStack));
        return $languageStack;
    }

    public function getLanguageStackUserPreferences($string = false)
    {
        if ($string) return implode(',', $this->languagesUserExtented);
        return $this->languagesUserExtented;
    }
    
    // returns enabled languages prioritized by user requested languages
    public function getApplicationLanguageStackByUserPreferences($string = false)
    {
        if (!$this->languagesApplicationOrderedByUserPreferences)
        {
            $this->languagesApplicationOrderedByUserPreferences = $this->createApplicationLanguageStackByUserPreferences();
        }
        
        if ($string) return implode(',', $this->languagesApplicationOrderedByUserPreferences);
        return $this->languagesApplicationOrderedByUserPreferences;
    }

    public function getLanguageStackMergedFromUserAndApplication()
    {
        return array_unique(
                    array_merge(
                        $this->getLanguageStackUserPreferences(),
                        $this->getApplicationLanguageStackByUserPreferences()
                    )
                 );
    }

    public function getLanguageStackSimplified($string = false)
    {
        $languagestack = $this->getLanguageStackMergedFromUserAndApplication();
        foreach ($languagestack as $i => $langcode)
        {
            $languagestack[$i] = strtolower(substr($langcode, 0, 2));
        }

        if ($string) return implode(',', array_unique($languagestack));
        return array_unique($languagestack);
    }

    public function setlocale($category, $suffixes)
    {
        if (!is_array($suffixes))
        {
            return false;
        }

        $languagestack = $this->getLanguageStackMergedFromUserAndApplication();
        $localestack = array();
        foreach ($languagestack as $langcode)
        {
            foreach ($suffixes as $suffix)
            {
                $localestack = $langcode.$suffix;
            }
        }
        //print_r($languagestack);
        return setlocale($category, $localestack);
    }
    
}
