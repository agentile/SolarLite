<?php
/**
 * 
 * Class for connecting to PostgreSQL databases.
 * 
 * @category Solar
 * 
 * @package Solar_Sql
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Pgsql.php 4416 2010-02-23 19:52:43Z pmjones $
 * 
 */
class SolarLite_DB_Pgsql extends SolarLite_DB
{
    /**
     * 
     * The PDO adapter type.
     * 
     * @var string
     * 
     */
    protected $_pdo_type = 'pgsql';
    
    /**
     * 
     * The quote character before an entity name (table, index, etc).
     * 
     * @var string
     * 
     */
    protected $_ident_quote_prefix = '"';
    
    /**
     * 
     * The quote character after an entity name (table, index, etc).
     * 
     * @var string
     * 
     */
    protected $_ident_quote_suffix = '"';
    
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
     * Get the last auto-incremented insert ID from the database.
     * 
     * Postgres SERIAL and BIGSERIAL types create sequences named in this
     * fashion:  `{$table}_{$col}_seq`.
     * 
     * <http://www.postgresql.org/docs/7.4/interactive/datatype.html#DATATYPE-SERIAL>
     * 
     * @param string $table The table name on which the auto-increment occurred.
     * 
     * @param string $col The name of the auto-increment column.
     * 
     * @return int The last auto-increment ID value inserted to the database.
     * 
     */
    public function lastInsertId($table = null, $col = null)
    {
        $this->connect();
        $name = "{$table}_{$col}_seq";
        $name = $this->quoteName($name);
        return $this->_pdo->lastInsertId($name);
    }
}
