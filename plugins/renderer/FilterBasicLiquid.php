<?php
/**
 * Provides basic content filters, mainly for strings, inspired by filters
 * known from the Liquid markup language (Ruby).
 *
 * Filters:
 * capitalize, downcase, upcase, sort, size, escape, escape_once, strip_html,
 * strip_newlines, newline_to_br, date_to_xmlschema, date_to_string,
 * date_to_long_string, xml_escape, cgi_escape, number_of_words,
 * array_to_sentence_string, trim
 *
 * @see https://github.com/shopify/liquid/wiki/liquid-for-designers
 * @see http://www.spiffystores.com/kb/Liquid_Filter_Reference
 * @see https://github.com/mojombo/jekyll/wiki/Liquid-Extensions
 *
 * @category  SPCMS
 * @package   Sandbox-Plugins
 * @author    Michael Haschke, http://eye48.com/
 * @since     0.2
 * @license   http://www.opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 */
class FilterBasicLiquid extends SandboxPlugin
{

    protected function init()
    {
        // from Liquid
        $this->pm->subscribe('sandbox_contentfilter_string_capitalize', $this, 'capitalize');
        $this->pm->subscribe('sandbox_contentfilter_string_downcase', $this, 'downcase');
        $this->pm->subscribe('sandbox_contentfilter_string_lowercase', $this, 'downcase');
        $this->pm->subscribe('sandbox_contentfilter_string_upcase', $this, 'upcase');
        $this->pm->subscribe('sandbox_contentfilter_string_uppercase', $this, 'upcase');
        $this->pm->subscribe('sandbox_contentfilter_array_sort', $this, 'sort');
        $this->pm->subscribe('sandbox_contentfilter_string_size', $this, 'size_of_string');
        $this->pm->subscribe('sandbox_contentfilter_array_size', $this, 'size_of_array');
        $this->pm->subscribe('sandbox_contentfilter_string_escape', $this, 'escape');
        $this->pm->subscribe('sandbox_contentfilter_string_escape_once', $this, 'escape_once');
        $this->pm->subscribe('sandbox_contentfilter_string_strip_html', $this, 'strip_html');
        $this->pm->subscribe('sandbox_contentfilter_string_strip_newlines', $this, 'strip_newlines');
        $this->pm->subscribe('sandbox_contentfilter_string_newline_to_br', $this, 'newline_to_br');
        $this->pm->subscribe('sandbox_contentfilter_string_date_to_atom', $this, 'date_to_atom');
        $this->pm->subscribe('sandbox_contentfilter_string_date_to_rss', $this, 'date_to_rss');
        $this->pm->subscribe('sandbox_contentfilter_string_date_to_xmlschema', $this, 'date_to_xmlschema');
        $this->pm->subscribe('sandbox_contentfilter_string_date_to_string', $this, 'date_to_string');
        $this->pm->subscribe('sandbox_contentfilter_string_date_to_long_string', $this, 'date_to_long_string');
        $this->pm->subscribe('sandbox_contentfilter_integer_date_to_atom', $this, 'timestamp_to_atom');
        $this->pm->subscribe('sandbox_contentfilter_integer_date_to_rss', $this, 'timestamp_to_rss');
        $this->pm->subscribe('sandbox_contentfilter_integer_date_to_xmlschema', $this, 'timestamp_to_xmlschema');
        $this->pm->subscribe('sandbox_contentfilter_integer_date_to_string', $this, 'timestamp_to_string');
        $this->pm->subscribe('sandbox_contentfilter_integer_date_to_long_string', $this, 'timestamp_to_long_string');
        $this->pm->subscribe('sandbox_contentfilter_string_xml_escape', $this, 'xml_escape');
        $this->pm->subscribe('sandbox_contentfilter_string_cgi_escape', $this, 'cgi_escape');
        $this->pm->subscribe('sandbox_contentfilter_string_number_of_words', $this, 'number_of_words');
        $this->pm->subscribe('sandbox_contentfilter_array_array_to_sentence_string', $this, 'array_to_sentence_string');

        // extra
        $this->pm->subscribe('sandbox_contentfilter_string_trim', $this, 'trim');
    }

    // capitalize words in the input sentence
    public function capitalize(&$string)
    {
        $string = ucwords($string);
        return $string;
    }

    // convert an input string to lowercase
    public function downcase(&$string)
    {
        $string = strtolower($string);
        return $string;
    }

    // convert an input string to uppercase
    public function upcase(&$string)
    {
        $string = strtoupper($string);
        return $string;
    }

    // sort elements of the array
    public function sort(&$array)
    {
        asort($array);
        return $array;
    }

    // return the size of string
    public function size_of_string(&$string)
    {
        $string = strlen($string);
        return $string;
    }

    // return the size of an array
    public function size_of_array(&$array)
    {
        $array = count($array);
        return $array;
    }

    // escape a string
    public function escape(&$string)
    {
        $string = htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
        return $string;
    }

    // returns an escaped version of html without affecting existing escaped entities
    public function escape_once(&$string)
    {
        if (version_compare(PHP_VERSION, '5.2.3', '>='))
        {
            $string = htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
        }
        else
        {
            $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
            $string = htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
        }
        return $string;
    }

    // strip html from string
    public function strip_html(&$string)
    {
        $string = strip_tags($string);
        return $string;
    }

    // strip all newlines (\n) from string
    public function strip_newlines(&$string)
    {
        $string = str_replace(array("\n", "\r"), '', $string);
        return $string;
    }

    // replace each newline (\n) with html break
    public function newline_to_br(&$string)
    {
        $string = str_replace(array("\r"), '', $string);
        $string = str_replace(array("\n"), '<br/>', $string);
        return $string;
    }

    // Convert a Time into Atom format, e.g. example: 2005-08-15T15:52:01+00:00
    public function date_to_atom(&$string)
    {
        $timestamp = $this->_create_timestamp_from_string($string);
        $string = date(DATE_ATOM, $timestamp);
        return $string;
    }

    public function timestamp_to_atom(&$timestamp)
    {
        $timestamp = date(DATE_ATOM, $timestamp);
        return $timestamp;
    }

    // Convert a Time into RSS format, e.g. Mon, 15 Aug 2005 15:52:01 +0000
    public function date_to_rss(&$string)
    {
        $timestamp = $this->_create_timestamp_from_string($string);
        $string = date(DATE_RSS, $timestamp);
        return $string;
    }

    public function timestamp_to_rss(&$timestamp)
    {
        $timestamp = date(DATE_RSS, $timestamp);
        return $timestamp;
    }

    // Convert a Time into W3CDTF format, e.g. 2002-10-02T10:00:00-05:00
    public function date_to_w3c(&$string)
    {
        $timestamp = $this->_create_timestamp_from_string($string);
        $string = date(DATE_W3C, $timestamp);
        return $string;
    }

    public function timestamp_to_w3c(&$timestamp)
    {
        $timestamp = date(DATE_W3C, $timestamp);
        return $timestamp;
    }

    // Convert a Time into XML Schema format, e.g. 2008-11-17T13:07:54-08:00
    public function date_to_xmlschema(&$string)
    {
        $timestamp = $this->_create_timestamp_from_string($string);
        $string = date('c', $timestamp);
        return $string;
    }

    public function timestamp_to_xmlschema(&$timestamp)
    {
        $timestamp = date('c', $timestamp);
        return $timestamp;
    }

    // Convert a date in short format, e.g. “27 Jan 2011”.
    public function date_to_string(&$string)
    {
        $timestamp = $this->_create_timestamp_from_string($string);
        $string = strftime('%e.&nbsp;%b %Y', $timestamp);
        return $string;
    }

    public function timestamp_to_string(&$timestamp)
    {
        $timestamp = strftime('%e.&nbsp;%b %Y', $timestamp);
        return $timestamp;
    }

    // Format a date in long format e.g. “27 January 2011”.
    public function date_to_long_string(&$string)
    {
        $timestamp = $this->_create_timestamp_from_string($string);
        $string = strftime('%e.&nbsp;%B %Y', $timestamp);
        return $string;
    }

    public function timestamp_to_long_string(&$timestamp)
    {
        $timestamp = strftime('%e.&nbsp;%B %Y', $timestamp);
        return $timestamp;
    }

    public function _create_timestamp_from_string($string)
    {
        if (is_numeric($string))
        {
            return intval($string);
        }

        // TODO: check if date_create is more powerful or if it is doing the same
        // @see http://de3.php.net/manual/en/datetime.construct.php

        return strtotime($string);
    }

    // Escape some text for use in XML.
    public function xml_escape(&$string)
    {
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $string = htmlspecialchars($string, ENT_NOQUOTES, 'UTF-8');
        return $string;
    }

    // CGI escape a string for use in a URL. Replaces any special characters with appropriate %XX replacements.
    public function cgi_escape(&$string)
    {
        $string = urlencode($string);
        return $string;
    }

    // Count the number of words in some text.
    public function number_of_words(&$string)
    {
        $temp = $this->strip_html($string);
        $temp = str_replace(array("\n", "\r", "\t"), ' ', $string);
        $temp = explode(' ', $temp);

        $sortout = create_function('$s', 'return (strlen($s) > 1);');
        $temp = array_filter($temp, $sortout);

        $string = count($temp);
        return $string;
    }

    // Convert an array into a sentence.
    public function array_to_sentence_string(&$array)
    {
        $array = implode(', ', $array);
        return $array;
    }

    // removes whitespace from content, e.g. "\n\r\t\ "
    public function trim(&$string)
    {
        $string = trim($string);
        return $string;
    }

}

