<?php
/**
 * @category Solar
 * 
 * @package Solar_Sql_Model An SQL-oriented ORM system using TableDataGateway 
 * and DataMapper patterns.
 * 
 * @author Paul M. Jones <pmjones@solarphp.com>
 * 
 * @author Jeff Moore <jeff@procata.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Model.php 4489 2010-03-02 15:34:14Z pmjones $
 * 
 */
abstract class SolarLite_Model
{
    /**
     * 
     * The number of rows affected by the last INSERT, UPDATE, or DELETE.
     * 
     * @var int
     * 
     * @see getAffectedRows()
     * 
     */
    protected $_affected_rows;
    
    
    /**
     * 
     * A Solar_Sql dependency object.
     * 
     * @var Solar_Sql_Adapter
     * 
     */
    protected $_sql = null;
    
    /**
     * 
     * A Solar_Sql_Model_Cache object.
     * 
     * @var Solar_Sql_Model_Cache
     * 
     */
    protected $_cache = null;
    
    /**
     * 
     * The model name is the short form of the class name; this is generally
     * a plural.
     * 
     * When inheritance is enabled, the default is the $_inherit_name value,
     * otherwise, the default is the $_table_name.
     * 
     * @var string
     * 
     */
    protected $_model_name;
    
    /**
     * 
     * The results of get_class($this) so we don't call get_class() all the 
     * time.
     * 
     * @var string
     * 
     */
    protected $_class;
    
    /**
     * 
     * SolarLite_Catalog object
     * 
     * @var object
     * 
     */
    protected $_catalog;
    
    /**
     * 
     * Should the fetch methods use cache?
     * 
     * @var string
     * 
     */
    protected $_use_cache = true;
    
    /**
     * 
     * The table name.
     * 
     * @var string
     * 
     */
    protected $_table_name = null;
    
    /**
     * 
     * The primary column.
     * 
     * @var string
     * 
     */
    protected $_primary_col = null;
    
    /**
     * 
     * __construct
     * 
     * @return void
     * 
     */
    public function __construct($catalog = null)
    {
        $this->_catalog = $catalog;
        $db_config = SolarLite_Config::get('database', array());
        if (isset($db_config['type'])) {
            $db_class = 'SolarLite_DB_' . ucfirst($db_config['type']);
        } else {
            $db_class = 'SolarLite_DB';
        }
        $this->_sql = new $db_class($db_config);
        
        if (isset($db_config['cache']) 
            && isset($db_config['cache']['type'])) {
            $cache_adapter = 'SolarLite_Cache_' 
                . ucfirst($db_config['cache']['type']);
            if (isset($db_config['cache']['life'])) {
                $life = (int) $db_config['cache']['life'];
            } else {
                $life = 3600; // 1 hour
            }
            
            if (isset($db_config['cache']['prefix'])) {
                $prefix = (string) $db_config['cache']['prefix'];
            } else {
                $prefix = '';
            }
            $this->_cache = new $cache_adapter($life, $prefix);
        }
    }
    
    /**
     * 
     * Returns the fully-qualified primary key name.
     * 
     * @return string
     * 
     */
    public function getPrimary()
    {
        return "{$this->_model_name}.{$this->_primary_col}";
    }
    
    /**
     * 
     * Returns the number of rows affected by the last INSERT, UPDATE, or
     * DELETE.
     * 
     * @return int
     * 
     */
    public function getAffectedRows()
    {
        return $this->_affected_rows;
    }
    
    /**
     * 
     * Updates rows in the model table and deletes cache entries.
     * 
     * @param array $data The row data to insert.
     * 
     * @param string|array $where The WHERE clause to identify which rows to 
     * update.
     * 
     * @return int The number of rows affected.
     * 
     * @throws Solar_Sql_Exception on failure of any sort.
     * 
     * @see Solar_Sql_Model_Cache::deleteAll()
     * 
     */
    public function update($data, $where)
    {
        if (! is_array($data)) {
            throw new Exception('No Data Given for update query');
        }
        
        // reset affected rows
        $this->_affected_rows = null;
        
        // don't update the primary key
        unset($data[$this->_primary_col]);
        
        // perform the update and track affected rows
        $this->_affected_rows = $this->_sql->update(
            $this->_table_name,
            $data,
            $where
        );
        
        // clear the cache for this model
        if ($this->_cache && $this->_cache->isActive()) {
            $this->_deleteCache();
        }
        
        // done!
        return $this->_affected_rows;
    }
    
    /**
     * 
     * Deletes rows from the model table and deletes cache entries.
     * 
     * @param string|array $where The WHERE clause to identify which rows to 
     * delete.
     * 
     * @return int The number of rows affected.
     * 
     * @see Solar_Sql_Model_Cache::deleteAll()
     * 
     */
    public function delete($where)
    {
        // perform the deletion and track affected rows
        $this->_affected_rows = $this->_sql->delete(
            $this->_table_name,
            $where
        );
        
        // clear the cache for this model
        if ($this->_cache && $this->_cache->isActive()) {
            $this->_deleteCache();
        }

        // done!
        return $this->_affected_rows;
    }
    
    /**
     * 
     * Inserts one row to the model table and deletes cache entries.
     * 
     * @param array $data The row data to insert.
     * 
     * @return int|bool On success, the last inserted ID if there is an
     * auto-increment column on the model (otherwise boolean true). On failure
     * an exception from PDO bubbles up.
     * 
     * @throws Solar_Sql_Exception on failure of any sort.
     * 
     * @see Solar_Sql_Model_Cache::deleteAll()
     * 
     */
    public function insert($data)
    {
        if (! is_array($data)) {
            throw new Exception('No Data Given for insert query');
        }
        
        // reset affected rows
        $this->_affected_rows;
        
        // perform the insert and track affected rows
        $this->_affected_rows = $this->_sql->insert(
            $this->_table_name,
            $data
        );
        
        // clear the cache for this model
        if ($this->_cache && $this->_cache->isActive()) {
            $this->_deleteCache();
        }
        
        // return the last insert id assuming the primary key 
        // is autoinc, or just "true" ?
        if ($this->_primary_col) {
            return $this->lastInsertId();
        } else {
            return true;
        }
    }
    
    /**
     * lastInsertId
     * Insert description here
     *
     * @param $col
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function lastInsertId($col = null)
    {
        if (!$col) {
            $col = $this->_primary_col;
        }
        return $this->_sql->lastInsertId($this->_table_name, $this->_primary_col);
    }
    
    /**
     * query
     * Insert description here
     *
     * @param $stmt
     * @param $data
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function query($stmt, $data = array())
    {
        return $this->_sql->query($stmt, $data);
    }
    
    /**
     * fetchAll
     * Insert description here
     *
     * @param $stmt
     * @param $data
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function fetchAll($stmt, $data = array(), $fetch_mode = PDO::FETCH_ASSOC)
    {
        // check cache
        if ($this->_cache && $this->_cache->isActive() && $this->_use_cache) {
            $cache_key = $this->_getCacheKey(array($stmt, $data));
            $cache_entry = $this->_getCacheEntry($cache_key);
            if ($cache_entry) {
                return $cache_entry;
            }
        }
        
        // perform query
        $sth = $this->_sql->query($stmt, $data);
        $result = $sth->fetchAll($fetch_mode);
        
        // set cache entry
        if ($this->_cache && $this->_cache->isActive()) {
            $cache_key = $this->_getCacheKey(array($stmt, $data));
            $this->_setCacheEntry($cache_key, $result);
        }
        
        return $result;
    }
    
    /**
     * 
     * Fetches one row from the database.
     * 
     * 
     * @return array
     * 
     */
    public function fetchOne($stmt, $data = array(), $fetch_mode = PDO::FETCH_ASSOC)
    {
        // check cache
        if ($this->_cache && $this->_cache->isActive() && $this->_use_cache) {
            $cache_key = $this->_getCacheKey(array($stmt, $data));
            $cache_entry = $this->_getCacheEntry($cache_key);
            if ($cache_entry) {
                return $cache_entry;
            }
        }
        
        // perform query
        $sth = $this->_sql->query($stmt, $data);
        $result = $sth->fetch($fetch_mode);
        
        // set cache entry
        if ($this->_cache && $this->_cache->isActive()) {
            $cache_key = $this->_getCacheKey(array($stmt, $data));
            $this->_setCacheEntry($cache_key, $result);
        }
        
        return $result;
    }
    
    /**
     * 
     * Deletes the cache for this model.
     * 
     * Technically, this just increases the data version number.  This means
     * that older versions will no longer be valid, causing a cache miss.
     * 
     * The version entry is keyed under /model/$table_name/data_version
     * 
     * @return void
     * 
     */
    protected function _deleteCache()
    {
        $key = $this->_cache->getPrefix()
             . "/model"
             . "/{$this->_table_name}"
             . "/data_version";
        
        $this->_cache->increment($key);
    }
    
    /**
     * 
     * Fetches the current model data version from the cache.
     * 
     * The entry is keyed under /model/$table_name/data_version
     * 
     * @return int The model data version.
     * 
     */
    protected function _fetchVersion()
    {
        $key = $this->_cache->getPrefix()
             . "/model"
             . "/{$this->_table_name}"
             . "/data_version";
        
        return $this->_cache->fetch($key);
    }
    
    /**
     * 
     * Fetch the cache key for a query
     * 
     * @param mixed $params
     * 
     */
     protected function _getCacheKey($params)
     {
        $key = hash('md5', serialize($params));  
        $key = $this->_cache->getPrefix()
             . "/model"
             . "/{$this->_table_name}"
             . "/" . $this->_fetchVersion()
             . "/$key";
             
         return $key;
     }
     
    /**
     * 
     * Do we have a cache entry for given key
     * 
     * @param string $key
     * 
     */
     protected function _getCacheEntry($key)
     {
         return $this->_cache->fetch($key);
     }
     
    /**
     * 
     * Set a cache entry
     * 
     * @param string $key
     * 
     * @param mixed $var
     * 
     */
     protected function _setCacheEntry($key, $var)
     {
         return $this->_cache->add($key, $var);
     }
}
