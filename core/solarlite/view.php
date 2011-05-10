<?php
/**
 * 
 * Provides a Template View pattern implementation for Solar.
 * 
 * This implementation is good for all (X)HTML and XML template
 * formats, and provides a built-in escaping mechanism for values,
 * along with lazy-loading and persistence of helper objects.
 * 
 * Also supports "partial" templates with variables extracted within
 * the partial-template scope.
 * 
 * @category Solar
 * 
 * @package Solar_View PHP-based TemplateView system.
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: View.php 4405 2010-02-18 04:27:25Z pmjones $
 * 
 */
class SolarLite_View
{
    /**
     * 
     * Parameters for escaping.
     * 
     * @var array
     * 
     */
    protected $_escape = array(
        'quotes'  => ENT_COMPAT,
        'charset' => 'UTF-8',
    );
    
    /**
     * 
     * The name of the current partial file.
     * 
     * @var string
     * 
     */
    protected $_partial_file;
    
    /**
     * 
     * Variables to be extracted within a partial.
     * 
     * @var array
     * 
     */
    protected $_partial_vars;
    
    /**
     * 
     * The name of the current template file.
     * 
     * @var string
     * 
     */
    protected $_template_file;
    
    /**
     * 
     * Path stack for templates.
     * 
     */
    protected $_template_path = array();
    
    /**
     * 
     * Disallows setting of underscore-prefixed variables.
     * 
     * @param string $key The variable name.
     * 
     * @param string $val The variable value.
     * 
     * @return void
     * 
     */
    public function __set($key, $val)
    {
        if ($key[0] != '_') {
            $this->$key = $val;
        }
    }
    
    /**
     * 
     * Sets variables for the view.
     * 
     * This method is overloaded; you can assign all the properties of
     * an object, an associative array, or a single value by name.
     * 
     * You are not allowed to assign any variable with an underscore
     * prefix.
     * 
     * In the following examples, the template will have two variables
     * assigned to it; the variables will be known inside the template as
     * "$this->var1" and "$this->var2".
     * 
     * {{code: php
     *     $view = Solar::factory('Solar_View_Template');
     *     
     *     // assign directly
     *     $view->var1 = 'something';
     *     $view->var2 = 'else';
     *     
     *     // assign by associative array
     *     $ary = array('var1' => 'something', 'var2' => 'else');
     *     $view->assign($ary);
     *     
     *     // assign by object
     *     $obj = new stdClass;
     *     $obj->var1 = 'something';
     *     $obj->var2 = 'else';
     *     $view->assign($obj);
     *     
     *     // assign by name and value
     *     $view->assign('var1', 'something');
     *     $view->assign('var2', 'else');
     * }}
     * 
     * @param mixed $spec The assignment specification.
     * 
     * @param mixed $var (Optional) If $spec is a string, assign
     * this variable to the $spec name.
     * 
     * @return bool True on success, false on failure.
     * 
     */
    public function assign($spec, $var = null)
    {
        // assign from associative array
        if (is_array($spec)) {
            foreach ($spec as $key => $val) {
                $this->$key = $val;
            }
            return true;
        }
        
        // assign from Solar_View object properties.
        // 
        // objects of a class have access to the protected and
        // private properties of other objects of the same class.
        // this means get_object_vars() will get all the internals 
        // of the assigned Solar_View object, overwriting the 
        // internals of this object.  check for underscores to make 
        // sure we don't do this.  yes, this means we check both
        // here and at __set(), which sucks.
        if (is_object($spec) && $spec instanceof SolarLite_View) {
            foreach (get_object_vars($spec) as $key => $val) {
                if ($key[0] != "_") {
                    $this->$key = $val;
                }
            }
            return true;
        }
        
        // assign from object properties (not Solar_View)
        if (is_object($spec)) {
            foreach (get_object_vars($spec) as $key => $val) {
                $this->$key = $val;
            }
            return true;
        }
        
        // assign by name and value
        if (is_string($spec)) {
            $this->$spec = $var;
            return true;
        }
        
        // $spec was not array, object, or string.
        return false;
    }
    
    /**
     * 
     * Built-in helper for escaping output.
     * 
     * @param scalar $value The value to escape.
     * 
     * @return string The escaped value.
     * 
     */
    public function escape($value)
    {
        return htmlspecialchars(
            $value,
            $this->_escape['quotes'],
            $this->_escape['charset']
        );
    }
    
    // -----------------------------------------------------------------
    //
    // Templates and partials
    //
    // -----------------------------------------------------------------
    
    /**
     * 
     * Reset the template directory path stack.
     * 
     * @param string|array $path The directories to set for the stack.
     * 
     * @return void
     * 
     * @see Solar_Path_Stack::set()
     * 
     */
    public function setTemplatePath($path = null)
    {
        $this->_template_path = (array) $path;
    }
    
    /**
     * 
     * Add to the template directory path stack.
     * 
     * @param string|array $path The directories to add to the stack.
     * 
     * @return void
     * 
     * @see Solar_Path_Stack::add()
     * 
     */
    public function addTemplatePath($paths)
    {
        $paths = (array) $paths;
        
        foreach ($paths as $path) {
            $this->_template_path[] = $path;
        }
    }
    
    /**
     * 
     * Returns the template directory path stack.
     * 
     * @return array The path stack of template directories.
     * 
     * @see Solar_Path_Stack::get()
     * 
     */
    public function getTemplatePath()
    {
        return $this->_template_path->get();
    }
    
    /**
     * 
     * Displays a template directly.
     * 
     * @param string $name The template to display.
     * 
     * @return void
     * 
     */
    public function display($name)
    {
        echo $this->fetch($name);
    }
    
    /**
     * 
     * Fetches template output.
     * 
     * @param string $name The template to process.
     * 
     * @return string The template output.
     * 
     */
    public function fetch($name)
    {
        // save externally and unset from local scope
        $this->_template_file = $this->template($name);
        unset($name);
        
        // run the template
        ob_start();
        require $this->_template_file;
        return ob_get_clean();
    }
    
    /**
     * 
     * Returns the path to the requested template script.
     * 
     * @param string $name The template name to look for in the template path.
     * 
     * @return string The full path to the template script.
     * 
     */
    public function template($name)
    {
        // append ".php" if needed
        if (substr($name, -4) != '.php') {
            $name .= '.php';
        }

        // get a path to the template
        foreach ($this->_template_path as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $file = $path . $name;
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new Exception("Could not find view: $file in template path stack");
    }
    
    /**
     * 
     * Executes a partial template in its own scope, optionally with 
     * variables into its within its scope.
     * 
     * Note that when you don't need scope separation, using a call to
     * "include $this->template($name)" is faster.
     * 
     * @param string $name The partial template to process.
     * 
     * @param array|object $spec Additional variables to use within the
     * partial template scope. If an array, we use extract() on it.
     * If an object, we create a new variable named after the partial template
     * file and set that new variable to be the object.  E.g., passing an
     * object to a partial template named `_foo-bar.php` will use that object
     * as `$foo_bar` in the partial.
     * 
     * @return string The results of the partial template script.
     * 
     */
    public function partial($name, $spec = null)
    {
        // use a try/catch block so that if a partial is not found, the
        // exception does not break the parent template.
        try {
            // save the partial name externally
            $this->_partial_file = $this->template($name);
        } catch (Solar_View_Exception_TemplateNotFound $e) {
            throw new Exception("Could not find partial: $file in template path stack");
        }
        
        // save partial vars externally. special cases for different types.
        if (is_object($spec)) {
            
            // the object var name is based on the partial's template name.
            // e.g., `foo/_bar-baz.php` becomes `$bar_baz`.
            $key = basename($this->_partial_file); // file name
            $key = substr($key, 1); // drop leading underscore
            if (substr($key, -4) == '.php') {
                $key = substr($key, 0, -4); // drop trailing .php
            }
            $key = str_replace('-', '_', $key); // convert dashes to underscores
            
            // keep the object under the key name
            $this->_partial_vars[$key] = $spec;
            
            // remove the key name from the local scope
            unset($key);
            
        } else {
            // keep vars as an array to be extracted
            $this->_partial_vars = (array) $spec;
        }
        
        // remove the partial name and spec from local scope
        unset($name);
        unset($spec);
        
        // disallow resetting of $this
        unset($this->_partial_vars['this']);
        
        // inject vars into local scope
        extract($this->_partial_vars);
        
        // run the partial template
        ob_start();
        require $this->_partial_file;
        return ob_get_clean();
    }
    
    /**
     * locale
     * Insert description here
     *
     * @param $key
     * @param $replace
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function locale($key, $replace = null)
    {
        static $class;
        if (! $class) {
            $class = $this->controller_class;
        }

        static $locale;
        if (! $locale) {
            $locale = new SolarLite_Locale();
        }
        
        return $locale->fetch($class, $key, $replace);
    }
}
