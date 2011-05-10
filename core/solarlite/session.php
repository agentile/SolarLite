<?php
/**
 * 
 * Class for working with the $_SESSION array, including read-once
 * flashes.
 * 
 * On instantiation, lazy-starts the session.  That is, if a session cookie
 * already exists, it starts the session; otherwise, it waits until the 
 * first attempt to write to the session before starting it.
 * 
 * Instantiate this once for each class that wants access to $_SESSION
 * values.  It automatically segments $_SESSION by class name, so be 
 * sure to use setClass() (or the 'class' config key) to identify the
 * segment properly.
 * 
 * A "flash" is a session value that propagates only until it is read,
 * at which time it is removed from the session.  Taken from ideas 
 * popularized by Ruby on Rails, this is useful for forwarding
 * information and messages between page loads without using GET vars
 * or cookies.
 * 
 * @category Solar
 * 
 * @package Solar_Session Session containers with support for named segments,
 * read-once flash values, and lazy-started sessions.
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @author Antti Holvikari <anttih@gmail.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Session.php 4495 2010-03-04 18:44:30Z pmjones $
 * 
 */
class SolarLite_Session
{
    /**
     * 
     * The session save handler object.
     * 
     * @var Solar_Session_Handler_Adapter
     * 
     */
    static protected $_handler;
    
    /**
     * 
     * The current request object.
     * 
     * @var Solar_Request
     * 
     */
    static protected $_request;
    
    /**
     * 
     * Array of read-once "flash" keys and values.
     * 
     * Convenience reference to $_SESSION['Solar_Session']['flash'][$this->_class].
     * 
     * @var array
     * 
     */
    protected $_flash = array();
    
    /**
     * 
     * Array of "normal" session keys and values.
     * 
     * Convenience reference to $_SESSION[$this->_class].
     * 
     * @var array
     * 
     */
    protected $_store = array();
    
    /**
     * 
     * The top-level $_SESSION class key for segmenting values.
     * 
     * @var array
     * 
     */
    protected $_class = 'SolarLite';
    
    /**
     * 
     * Is the $_SESSION loaded for the current class?
     * 
     * @var bool
     * 
     */
    protected $_is_loaded = false;
    
    
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
    public function __construct()
    {
        // only set up the handler if it doesn't exist yet.
        if (! self::$_handler) {
            $handler = 'SolarLite_Session_Handler_' . SolarLite_Config::get('session_handler', 'Native');
            self::$_handler = new $handler();
        }
        
        // only set up the request if it doesn't exist yet.
        if (! self::$_request) {
            self::$_request = new SolarLite_Request();
        }
        
        // lazy-start any existing session
        $this->lazyStart();
    }
    
    /**
     * 
     * Magic get for store and flash as a temporary measure.
     * 
     * @param string $key The session key to retrieve.
     * 
     * @return mixed The value of the key.
     * 
     * @deprecated
     * 
     */
    public function &__get($key)
    {
        if ($key == 'store') {
            $this->load();
            return $this->_store;
        }
        
        if ($key == 'flash') {
            $this->load();
            return $this->_flash;
        }
        
        throw $this->_exception('ERR_NO_SUCH_PROPERTY', array(
            'key' => $key,
        ));
    }
    
    /**
     * 
     * Starts the session; automatically sends a P3P header if one is defined
     * (and it is, by default).
     * 
     * @return void
     * 
     */
    public function start()
    {
        // don't start more than once.
        if ($this->isStarted()) {
            // be sure the segment is loaded, though
            $this->load();
            return;
        }
        
        // set the privacy headers
        $response = new SolarLite_Response();
        $response->setHeader('P3P', 'CP="CAO COR CURa ADMa DEVa TAIa OUR BUS IND UNI COM NAV INT STA"');
        
        // start the session
        session_start();
        
        // load the session segment
        $this->load();
    }
    
    /**
     * 
     * Lazy-start the session (i.e., only if a session cookie from the client
     * already exists).
     * 
     * @return void
     * 
     */
    public function lazyStart()
    {
        // don't start more than once.
        if ($this->isStarted()) {
            // be sure the segment is loaded, though
            $this->load();
            return;
        }
        
        $name = session_name();
        if (self::$_request->cookie($name)) {
            // a previous session exists, start it
            $this->start();
        }
    }
    
    /**
     * 
     * Has a session been started yet?
     * 
     * @return bool
     * 
     */
    public function isStarted()
    {
        return session_id() !== '';
    }
    
    /**
     * 
     * Loads the session segment with store and flash values for the current
     * class.
     * 
     * @return void
     * 
     */
    public function load()
    {
        if ($this->isLoaded()) {
            return;
        }
        
        // can't be loaded if the session has started
        if (! $this->isStarted()) {
            // not possible for anything to be loaded, then
            $this->_is_loaded = false;
            $this->_store = array();
            $this->_flash = array();
            return;
        }
        
        // set up the value store.
        if (empty($_SESSION[$this->_class])) {
            $_SESSION[$this->_class] = array();
        }
        $this->_store =& $_SESSION[$this->_class];
        
        // set up the flash store
        if (empty($_SESSION['SolarLite_Session']['flash'][$this->_class])) {
            $_SESSION['SolarLite_Session']['flash'][$this->_class] = array();
        }
        $this->_flash =& $_SESSION['SolarLite_Session']['flash'][$this->_class];
        
        // done!
        $this->_is_loaded = true;
    }
    
    /**
     * 
     * Tells if the session segment is loaded or not.
     * 
     * @return bool
     * 
     */
    public function isLoaded()
    {
        return $this->_is_loaded;
    }
    
    /**
     * 
     * Sets the class segment for $_SESSION; unloads existing store and flash
     * values.
     * 
     * @param string $class The class name to segment by.
     * 
     * @return void
     * 
     */
    public function setClass($class)
    {
        $this->_is_loaded = false;
        $this->_class = $class;
        $this->load();
    }
    
    /**
     * 
     * Gets the current class segment for $_SESSION.
     * 
     * @return string
     * 
     */
    public function getClass()
    {
        return $this->_class;
    }
    
    /**
     * 
     * Whether or not the session currently has a particular data key stored.
     * Does not return or remove the value of the key.
     * 
     * @param string $key The data key.
     * 
     * @return bool True if the session has this data key in it, false if
     * not.
     * 
     */
    public function has($key)
    {
        $this->load();
        return array_key_exists($key, $this->_store);
    }
    
    /**
     * 
     * Sets a normal value by key; this will start the session if needed.
     * 
     * @param string $key The data key.
     * 
     * @param mixed $val The value for the key; previous values will
     * be overwritten.
     * 
     * @return void
     * 
     */
    public function set($key, $val)
    {
        $this->start();
        $this->_store[$key] = $val;
    }
    
    /**
     * 
     * Appends a normal value to a key; this will start the session if needed.
     * 
     * @param string $key The data key.
     * 
     * @param mixed $val The value to add to the key; this will
     * result in the value becoming an array.
     * 
     * @return void
     * 
     */
    public function add($key, $val)
    {
        $this->start();
        
        if (! isset($this->_store[$key])) {
            $this->_store[$key] = array();
        }
        
        if (! is_array($this->_store[$key])) {
            settype($this->_store[$key], 'array');
        }
        
        $this->_store[$key][] = $val;
    }
    
    /**
     * 
     * Gets a normal value by key, or an alternative default value if
     * the key does not exist.
     * 
     * @param string $key The data key.
     * 
     * @param mixed $val If key does not exist, returns this value
     * instead.  Default null.
     * 
     * @return mixed The value.
     * 
     */
    public function get($key, $val = null)
    {
        $this->load();
        
        if (array_key_exists($key, $this->_store)) {
            $val = $this->_store[$key];
        }
        
        return $val;
    }
    
    /**
     * 
     * Deletes a key from the store, removing it entirely.
     * 
     * @param string $key The data key.
     * 
     * @return void
     * 
     */
    public function delete($key)
    {
        // don't start a new session to remove something that isn't there
        $this->lazyStart();
        unset($this->_store[$key]);
    }
    
    /**
     * 
     * Resets (clears) all normal keys and values.
     * 
     * @return void
     * 
     */
    public function reset()
    {
        // don't start a new session to remove something that isn't there
        $this->lazyStart();
        $this->_store = array();
    }
    
    /**
     * 
     * Whether or not the session currently has a particular flash key stored.
     * Does not return or remove the value of the key.
     * 
     * @param string $key The flash key.
     * 
     * @return bool True if the session has this flash key in it, false if
     * not.
     * 
     */
    public function hasFlash($key)
    {
        $this->load();
        return array_key_exists($key, $this->_flash);
    }
    
    /**
     * 
     * Sets a flash value by key; this will start the session if needed.
     * 
     * @param string $key The flash key.
     * 
     * @param mixed $val The value for the key; previous values will
     * be overwritten.
     * 
     * @return void
     * 
     */
    public function setFlash($key, $val)
    {
        $this->start();
        $this->_flash[$key] = $val;
    }
    
    /**
     * 
     * Appends a flash value to a key; this will start the session if needed.
     * 
     * @param string $key The flash key.
     * 
     * @param mixed $val The flash value to add to the key; this will
     * result in the flash becoming an array.
     * 
     * @return void
     * 
     */
    public function addFlash($key, $val)
    {
        $this->start();
        
        if (! isset($this->_flash[$key])) {
            $this->_flash[$key] = array();
        }
        
        if (! is_array($this->_flash[$key])) {
            settype($this->_flash[$key], 'array');
        }
        
        $this->_flash[$key][] = $val;
    }
    
    /**
     * 
     * Gets a flash value by key, thereby removing the value.
     * 
     * @param string $key The flash key.
     * 
     * @param mixed $val If key does not exist, returns this value
     * instead.  Default null.
     * 
     * @return mixed The flash value.
     * 
     * @todo Mike Naberezny notes a possible issue with AJAX requests:
     * 
     *     // If this is an AJAX request, don't clear the flash.
     *     $headers = getallheaders();
     *     if (isset($headers['X-Requested-With']) &&
     *         stripos($headers['X-Requested-With'], 'xmlhttprequest') !== false) {
     *         // leave alone
     *         return;
     *     }
     * 
     * Would need to have Solar_Request access for this to work like the rest
     * of Solar does.
     * 
     */
    public function getFlash($key, $val = null)
    {
        $this->load();
        
        if (array_key_exists($key, $this->_flash)) {
            $val = $this->_flash[$key];
            unset($this->_flash[$key]);
        }
        
        return $val;
    }
    
    /**
     * 
     * Deletes a flash key, removing it entirely.
     * 
     * @param string $key The flash key.
     * 
     * @return void
     * 
     */
    public function deleteFlash($key)
    {
        // don't start a new session to remove something that isn't there
        $this->lazyStart();
        unset($this->_flash[$key]);
    }
    
    /**
     * 
     * Resets (clears) all flash keys and values.
     * 
     * @return void
     * 
     */
    public function resetFlash()
    {
        // don't start a new session to remove something that isn't there
        $this->lazyStart();
        $this->_flash = array();
    }
    
    /**
     * 
     * Resets both "normal" and "flash" values.
     * 
     * @return void
     * 
     */
    public function resetAll()
    {
        $this->reset();
        $this->resetFlash();
    }
    
    /**
     * 
     * Regenerates the session ID.
     * 
     * Use this every time there is a privilege change.
     * 
     * @return void
     * 
     * @see [[php::session_regenerate_id()]]
     * 
     */
    public function regenerateId()
    {
        $this->start();
        if (! headers_sent()) {
            session_regenerate_id(true);
        }
    }
}