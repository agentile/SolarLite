<?php
/**
 * SolarLite Arch Class
 */
class SolarLite 
{
    public static $system = null;
    
    protected $_config = array();
    
    /**
     * __construct
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function __construct() {}
    
    /**
     * load config, clean superglobals
     * 
     */
    public function start($config = null)
    {
        // clear out registered globals
        if (ini_get('register_globals')) {
            self::cleanGlobals();
        }
        
        // set the system directory
        self::$system = dirname(dirname(__FILE__));
        
        // register autoloader
        spl_autoload_register(array($this, 'autoload'));
        
        // load config values
        SolarLite_Config::load($config);
        
        if (isset($config['ini_set']) && is_array($config['ini_set'])) {
            foreach ($config['ini_set'] as $key => $val) {
                ini_set($key, $val);
            }
        }
        
        // dispatch
        $this->display();
        
        // unregister autoloader
        spl_autoload_unregister(array($this, 'autoload'));
    }
    
    /**
     * 
     * Cleans the global scope of all variables that are found in other
     * super-globals.
     * 
     * This code originally from Richard Heyes and Stefan Esser.
     * 
     * @return void
     * 
     */
    public static function cleanGlobals()
    {
        $list = array(
            'GLOBALS',
            '_POST',
            '_GET',
            '_COOKIE',
            '_REQUEST',
            '_SERVER',
            '_ENV',
            '_FILES',
        );
        
        // Create a list of all of the keys from the super-global values.
        // Use array_keys() here to preserve key integrity.
        $keys = array_merge(
            array_keys($_ENV),
            array_keys($_GET),
            array_keys($_POST),
            array_keys($_COOKIE),
            array_keys($_SERVER),
            array_keys($_FILES),
            // $_SESSION is null if you have not started the session yet.
            // This insures that a check is performed regardless.
            isset($_SESSION) && is_array($_SESSION) ? array_keys($_SESSION) : array()
        );
        
        // Unset the globals.
        foreach ($keys as $key) {
            if (isset($GLOBALS[$key]) && ! in_array($key, $list)) {
                unset($GLOBALS[$key]);
            }
        }
    }
    
    /**
     * autoload
     * Insert description here
     *
     * @param $name
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public static function autoload($name)
    {
        // did we ask for a non-blank name?
        if (trim($name) == '') {
            throw new Exception('No class name specified');
        }
        
        // pre-empt further searching for the named class or interface.
        // do not use autoload, because this method is registered with
        // spl_autoload already.
        $exists = class_exists($name, false) || interface_exists($name, false);
        
        if ($exists) {
            return;
        }
        
        $file = self::classToPath($name, '.php');
        
        if (file_exists($file)) {
            return include $file;
        } else {
            throw new Exception("$file does not exist.");
        }
        
        // if the class or interface was not in the file, we have a problem.
        // do not use autoload, because this method is registered with
        // spl_autoload already.
        $exists = class_exists($name, false) || interface_exists($name, false);
        
        if (!$exists) {
            throw new Exception("Class: $name not found in $file");
        }
    }
    
    /**
     * display
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function display()
    {
        $uri = new SolarLite_Uri();
        $uri->set();
        
        if (!isset($uri->path[0])) {
            $controller = SolarLite_Config::get('default_controller', false);
            if ($controller) {
                $uri->path[0] = $controller;
            } else {
                $uri->path[0] = '';
            }
        }
        
        // pass through router
        $uri->path = $this->route($uri->path);
        
        $class = 'App_Controller_' . ucfirst($uri->path[0]);
        $file = self::classToPath($class, '.php');
    
        if (!file_exists($file) && SolarLite_Config::get('default_controller', false) !== false) {
            $controller = SolarLite_Config::get('default_controller');
            array_unshift($uri->path, $controller);
            $class = 'App_Controller_' . ucfirst($controller);
            $file = self::classToPath($class, '.php');
            if (!file_exists($file)) {
                $class = 'SolarLite_Controller';
            }
        } elseif (!file_exists($file)) {
            $class = 'SolarLite_Controller';
        }
        $class = new $class();
        $class->display($uri);
    }
    
    /**
     * classToPath
     * Insert description here
     *
     * @param $name
     * @param $ext
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public static function classToPath($name, $ext = '')
    {
        $path = str_replace('_', DIRECTORY_SEPARATOR, strtolower($name));
        
        if (strpos($path, 'solarlite') !== FALSE) {
            $path = 'core' . DIRECTORY_SEPARATOR . $path; 
        }
        
        return self::$system . DIRECTORY_SEPARATOR . $path . $ext;
    }
    
    /**
     * route
     * Insert description here
     *
     * @param $uri
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function route($uri)
    {
        $routing = SolarLite_Config::get('routing', array());
        $replace = (isset($routing['replace'])) ? $routing['replace'] : array();
        $rewrite = (isset($routing['rewrite'])) ? $routing['rewrite'] : array();
        
        // check for rewrites
        $static = trim(implode('/', $uri), '/');
        
        foreach ($rewrite as $start => $end) {
            $pattern = str_replace(
                array_keys($replace),
                array_values($replace),
                trim($start, '/')
            );
            $pattern = '#^' . trim($pattern, '/') . '$#';
            if (preg_match($pattern, $static)) {
                $rw = trim($end, '/');
                $newpath = preg_replace($pattern, $rw, $static);
                return explode('/', trim($newpath, '/'));
            }
        }
        
        return $uri;
    }
}
