<?php
/**
 * 
 * Applies inflections to words: singular, plural, camel, underscore, etc.
 * 
 * @category Solar
 * 
 * @package Solar_Inflect Word-inflection tools.
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Inflect.php 4380 2010-02-14 16:06:52Z pmjones $
 * 
 */
class SolarLite_Inflect
{
    /**
     * 
     * Returns any string, converted to using dashes with only lowercase 
     * alphanumerics.
     * 
     * @param string $str The string to convert.
     * 
     * @return string The converted string.
     * 
     */
    public function toDashes($str)
    {
        $str = preg_replace('/[^a-z0-9 _-]/i', '', $str);
        $str = $this->camelToDashes($str);
        $str = preg_replace('/[ _-]+/', '-', $str);
        return $str;
    }
    
    /**
     * 
     * Returns "foo_bar_baz" as "fooBarBaz".
     * 
     * @param string $str The underscore word.
     * 
     * @return string The word in camel-caps.
     * 
     */
    public function underToCamel($str)
    {
        $str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ', '', $str);
        $str[0] = strtolower($str[0]);
        return $str;
    }
    
    /**
     * 
     * Returns "foo-bar-baz" as "fooBarBaz".
     * 
     * @param string $str The dashed word.
     * 
     * @return string The word in camel-caps.
     * 
     */
    public function dashesToCamel($str)
    {
        $str = ucwords(str_replace('-', ' ', $str));
        $str = str_replace(' ', '', $str);
        $str[0] = strtolower($str[0]);
        return $str;
    }
    
    /**
     * 
     * Returns "foo_bar_baz" as "FooBarBaz".
     * 
     * @param string $str The underscore word.
     * 
     * @return string The word in studly-caps.
     * 
     */
    public function underToStudly($str)
    {
        $str = $this->underToCamel($str);
        $str[0] = strtoupper($str[0]);
        return $str;
    }
    
    /**
     * 
     * Returns "foo-bar-baz" as "FooBarBaz".
     * 
     * @param string $str The dashed word.
     * 
     * @return string The word in studly-caps.
     * 
     */
    public function dashesToStudly($str)
    {
        $str = $this->dashesToCamel($str);
        $str[0] = strtoupper($str[0]);
        return $str;
    }
    
    /**
     * 
     * Returns "camelCapsWord" and "CamelCapsWord" as "Camel_Caps_Word".
     * 
     * @param string $str The camel-caps word.
     * 
     * @return string The word with underscores in place of camel caps.
     * 
     */
    public function camelToUnder($str)
    {
        $str = preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
        $str = str_replace(' ', '_', ucwords($str));
        return $str;
    }
    
    /**
     * 
     * Returns "camelCapsWord" and "CamelCapsWord" as "camel-caps-word".
     * 
     * @param string $str The camel-caps word.
     * 
     * @return string The word with dashes in place of camel caps.
     * 
     */
    public function camelToDashes($str)
    {
        $str = preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
        $str = str_replace(' ', '-', ucwords($str));
        return strtolower($str);
    }
}