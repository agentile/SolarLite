<?php
/**
 * 
 * Static support methods for config information.
 * 
 * @category Solar
 * 
 * @package Solar
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Config.php 4498 2010-03-05 17:28:00Z pmjones $
 * 
 */
class SolarLite_Config
{
    /**
     * 
     * The loaded values from the config file.
     * 
     * @var array
     * 
     * @see load()
     * 
     */
    static protected $_store = array();
    
    
    /**
     * 
     * Safely gets a configuration key value.
     *
     * 
     */
    static public function get($key = null, $alt = null)
    {
        if (array_key_exists($key, self::$_store)) {
            return self::$_store[$key];
        }
        return $alt;
    }
    
    /**
     * 
     * Loads the config values from the specified location.
     * 
     * @param mixed $spec A config specification.
     * 
     * @see fetch()
     * 
     * @return void
     * 
     */
    static public function load($config)
    {
        $merge = (array) $config;
        self::$_store = array_merge(self::$_store, $merge);
    }
    
    /**
     * 
     * Sets the config values for a class and key.
     * 
     * @param string $class The name of the class.
     * 
     * @param string $key The name of the key for the class; if empty, will
     * apply the changes to the entire class array.
     * 
     * @param mixed $val The value to set for the class and key.
     * 
     * @return void
     * 
     */
    static public function set($key, $val)
    {
        self::$_store[$key] = $val;
    }
}