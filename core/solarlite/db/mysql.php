<?php
/**
 * 
 * Class for MySQL behaviors.
 * 
 * @category Solar
 * 
 * @package Solar_Sql
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Mysql.php 4416 2010-02-23 19:52:43Z pmjones $
 * 
 */
class SolarLite_DB_Mysql extends SolarLite_DB
{
    /**
     * 
     * The PDO adapter type.
     * 
     * @var string
     * 
     */
    protected $_pdo_type = 'mysql';
    
    /**
     * 
     * The quote character before an entity name (table, index, etc).
     * 
     * @var string
     * 
     */
    protected $_ident_quote_prefix = '`';
    
    /**
     * 
     * The quote character after an entity name (table, index, etc).
     * 
     * @var string
     * 
     */
    protected $_ident_quote_suffix = '`';
    
    /**
     * __construct
     * Insert description here
     *
     * @param $config
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }
    
    /**
     * 
     * Creates a PDO-style DSN.
     * 
     * For example, "mysql:host=127.0.0.1;dbname=test"
     * 
     * @param array $info An array with host, post, name, etc. keys.
     * 
     * @return string A PDO-style DSN.
     * 
     */
    protected function _buildDsn($info)
    {
        // the dsn info
        $dsn = array();
        
        // socket, or host-and-port? (can't use both.)
        if (! empty($info['sock'])) {
            
            // use a socket
            $dsn[] = 'unix_socket=' . $info['sock'];
            
        } else {
            
            // use host and port
            if (! empty($info['host'])) {
                $dsn[] = 'host=' . $info['host'];
            }
        
            if (! empty($info['port'])) {
                $dsn[] = 'port=' . $info['port'];
            }
            
        }
        
        // database name
        if (! empty($info['name'])) {
            $dsn[] = 'dbname=' . $info['name'];
        }
        
        // done
        return $this->_pdo_type . ':' . implode(';', $dsn);
    }
}
