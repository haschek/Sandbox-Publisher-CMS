<?php

class CanonicalLink extends SandboxPlugin
{
    protected $url = null;
    protected $parameters = array();

    protected function init()
    {
       $this->pm->subscribe('sandbox_template_htmlhead_start', $this, 'insertCanonicalLinkMarkup');
    }

    public function setUrl($url)
    {
        $this->url = $url; // TODO: validate URI
    }

    public function addParameter($key, $value)
    {
        if ($key && $value)
        {
            // TODO: improve validation
            $this->parameters[$key] = $value;
        }
    }

    public function getCanonicalUri($argsep = '&')
    {
        ksort($this->parameters);
        return $this->url . '?' . http_build_query($this->parameters, '', $argsep);
    }

    public function insertCanonicalLinkMarkup()
    {
        if ($this->url !== null && is_array($this->parameters))
        {
            $canonicalUri = $this->getCanonicalUri('&amp;');
            echo '<link rel="canonical" href="'.$canonicalUri.'">'.PHP_EOL;
        }
        return;
    }
}