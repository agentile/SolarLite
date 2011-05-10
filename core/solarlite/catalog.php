<?php
/**
 * 
 * Acts as a central catalog for model instances; reduces the number of times
 * you instantiate model classes.
 * 
 * @category Solar
 * 
 * @package Solar_Sql_Model
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @author Jeff Moore <jeff@procata.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Catalog.php 4416 2010-02-23 19:52:43Z pmjones $
 * 
 */
class SolarLite_Catalog
{
    /**
     * 
     * An array of instantiated model objects keyed by class name.
     * 
     * @var array
     * 
     */
    protected $_store = array();
    
    /**
     * 
     * Inflection dependency.
     * 
     * @var Solar_Inflect
     * 
     */
    protected $_inflect;
    
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
        $this->_inflect = new SolarLite_Inflect();
    }
    
    /**
     * 
     * Magic get to make it look like model names are object properties.
     * 
     * @param string $key The model name to retrieve.
     * 
     * @return Solar_Sql_Model The model object.
     * 
     */
    public function __get($key)
    {
        return $this->getModel($key);
    }
    
    /**
     * 
     * Returns a stored model instance by name, creating it if needed.
     * 
     * @param string $name The model name.
     * 
     * @return Solar_Sql_Model A model instance.
     * 
     */
    public function getModel($name)
    {
        $class = 'App_Model_' . $this->_inflect->underToStudly($name);
        return $this->getModelByClass($class);
    }
    
    /**
     * 
     * Returns a stored model instance by class, creating it if needed.
     * 
     * @param string $class The model class.
     * 
     * @return Solar_Sql_Model A model instance.
     * 
     */
    public function getModelByClass($class)
    {
        if (empty($this->_store[$class])) {
            $this->_store[$class] = $this->_newModel($class);
        }
        
        return $this->_store[$class];
    }
    
    /**
     * 
     * Sets a model name to be a specific instance or class.
     * 
     * Generally, you only need this when you want to bring in a single model
     * from outside the expected stack.
     * 
     * @param string $name The model name to use.
     * 
     * @param string|Solar_Sql_Model $spec If a model object, use directly;
     * otherwise, assume it's a string class name and create a new model using
     * that.
     * 
     * @return void
     * 
     */
    public function setModel($name, $spec)
    {        
        // instance, or new model?
        if ($spec instanceof SolarLite_Model) {
            $model = $spec;
            $class = get_class($model);
        } else {
            $class = $spec;
            $model = $this->_newModel($class);
        }
        
        $this->_store[$class] = $model;
    }
    
    /**
     * 
     * Loads a model from the stack into the catalog by name, returning a 
     * true/false success indicator (instead of throwing an exception when
     * the class cannot be found).
     * 
     * @param string $name The model name to load from the stack into the
     * catalog.
     * 
     * @return bool True on success, false on failure.
     * 
     */
    public function loadModel($name)
    {
        try {
            $class = $this->getClass($name);
        } catch (Exception $e) {
            return false;
        }
        
        // retain the model internally
        $this->getModelByClass($class);
        
        // success
        return true;
    }
    
    /**
     * 
     * Returns a new model instance (not stored).
     * 
     * @param string $name The model name.
     * 
     * @return Solar_Sql_Model A model instance.
     * 
     */
    public function newModel($name)
    {
        $class = $this->getClass($name);
        return $this->_newModel($class);
    }
    
    /**
     * 
     * Returns information about the catalog as an array with keys for 'names'
     * (the model name-to-class mappings), 'store' (the classes actually
     * loaded up and retained), and 'stack' (the search stack for models).
     * 
     * @return array
     * 
     */
    public function getInfo()
    {
        return array(
            'store' => array_keys($this->_store)
        );
    }
    
    /**
     * 
     * Returns a new model instance (not stored).
     * 
     * @param string $class The model class.
     * 
     * @return Solar_Sql_Model A model instance.
     * 
     */
    protected function _newModel($class)
    {
        // instantiate
        $model = new $class($this);
        
        // done!
        return $model;
    }
}