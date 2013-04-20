<?php
class dbobject {

	/**
	 * Contains an array of keys => values for the database data
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * The name of the field which is the primary key
	 *
	 * @var string
	 */
	public $pkey = 'id';

	protected $hasBeenDeleted = false;
	protected $hasBeenCreated = false;

	/**
	 * An array of fields in the database
	 *
	 * @var array
	 */
	protected $fields = array();


	/**
	 * @var $apiUseFields - defines what fields the function __toApi() should use
	 *
	 * Note: make sure that your sub-classes also have this defined, otherwise they will inherit this root object's (singleton) value!
	 */
	public static $apiUseFields = 'fields';


	// ==========================================================================
	// magic methods
	// ==========================================================================

	public function getFields(){
		return $this->fields;
	}

	public function __toApi() {
		$res = new stdClass();
		$res->{$this->pkey} = $this->{$this->pkey};

		$arrFields = array();
		if (static::$apiUseFields == 'fields') {
			$arrFields = $this->getFields();
		} else {
			$arrFields = static::$apiUseFields;
		}

		foreach ($arrFields as $field) {
			if (is_numeric($this->{$field})) {
				$res->{$field} = $this->{$field};
			} elseif (is_string($this->{$field})) {
				$res->{$field} = utf8_encode($this->{$field});
			} else {
				$res->{$field} = $this->{$field};
			}
		}

		return $res;
	}

	public function __construct()
	{

		foreach ($this->fields as $field)
		{
			$this->_data[$field] = null;
		}
		$this->_data[$this->pkey] = null;

		if (in_array('dateCreated', $this->fields))
		{
			$this->dateCreated = time();
		}

		if (in_array('deleted', $this->fields))
		{
			$this->deleted = 0;
		}
	}

	/**
	 * Removes the object from the DBObjectRegistry
	 *
	 */
	public function __destruct()
	{
		unset($this);
	}

	/**
	 * @ignore
	 */
	public function __get($name)
	{
		if (isset($this->_data['_' . $name . '_id']))
		{
			return call_user_func(array(self::classify($name), 'find'), $this->_data['_' . $name . '_id']);
		}
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		} else {
			return null;
		}
	}

	/**
	 * @ignore
	 */
	public function __set($name, $value)
	{
		if (array_key_exists('_' . $name . '_id', $this->_data))
		{
			$this->_data['_' . $name . '_id'] = $value->pkey;
		}
		else
		{
			$this->_data[$name] = $value;
		}
	}

	/**
	 * @ignore
	 */
	public function __isset($name)
	{
		if (isset($this->_data['_' . $name . '_id']))
		{
			return isset($this->_data['_' . $name . '_id']);
		}
		return isset($this->_data[$name]);
	}

	/**
	 * @ignore
	 */
	public function __unset($name)
	{
		if (isset($this->_data['_' . $name . '_id']))
		{
			unset($this->_data['_' . $name . '_id']);
		}
		unset($this->_data[$name]);
	}



	public static function find($options, $noCache = false)
	{
		$class = get_called_class();
		$query = '';
		$values = array();
		if (isset($GLOBALS['BATCHMODE']) && $GLOBALS['BATCHMODE']==true) {
			$noCache = true;
		}

		// We've got an ID
		if (is_numeric($options))
		{
			if ($options < 0)
			{
				throw new RecordNotFoundException();
			}

			$o = new $class();
			$query = 'WHERE ' . $o->pkey . ' = ?';
			if(Dbo::$engine === Dbo::ENGINE_MYSQL)
			{
				$query .= ' LIMIT 1';
				$values = array($options);
			}
			else
			{
				$id = $options;

				$options = array();
				$options['limit'] = 1;
				$options['conditions'][1] = $options;
				$values = array($id);
			}

			$SQL_CALC_FOUND_ROWS = false;

		}
		elseif (is_array($options))
		{
			if (isset($options['conditions']) && is_array($options['conditions']) && isset($options['conditions'][0]) && substr_count($options['conditions'][0], '?') == sizeof($options['conditions']) - 1)
			{
				$query .= (empty($query) ? 'WHERE ' : ' AND ') . array_shift($options['conditions']);
				$values = array_merge($values, $options['conditions']);
			}
			elseif (isset($options['conditions']) && is_array($options['conditions']) && isset($options['conditions'][0]) && substr_count($options['conditions'][0], '?') != sizeof($options['conditions']) - 1)
			{
				throw new PDOException('The number of placeholders in your query does not match the number of arguments');
			}

			if (isset($options['id']))
			{
				$o = new $class();
				$query .= (empty($query) ? 'WHERE (' : ' AND (') . substr(str_repeat( $o->pkey . ' = ? OR ', sizeof($options['id'])), 0, -4) . ')';
				$values = array_merge($values, $options['id']);
			}

			if (isset($options['group']))
			{
				$query .= ' GROUP BY ' . $options['group'];
			}
			if (isset($options['order']) || isset($options['sort']))
			{
				$query .= ' ORDER BY ' .(isset($options['order'])?$options['order']:$options['sort']);
			}
			if (isset($options['limit']) && Dbo::$engine === Dbo::ENGINE_MYSQL)
			{
				$query .= ' LIMIT ' . $options['limit'];
			}
			if (isset($options['indexHint']))
			{
				$query = $options['indexHint'].' '.$query;
			}
			if (isset($options['lock']))
			{
				switch (strtoupper($options['lock'])) {
					case 'FOR UPDATE' : $query.= ' FOR UPDATE';
					break;
				}
			}
		}
		else
		{
			throw new InvalidArgumentException('Invalid search criteria specified.');
		}

		if (!isset($SQL_CALC_FOUND_ROWS) && isset($options['SQL_CALC_FOUND_ROWS']) && $options['SQL_CALC_FOUND_ROWS']===true && isset($options['limit']) && $options['limit']!=1) {
			$SQL_CALC_FOUND_ROWS = true;
		}

		if (isset($SQL_CALC_FOUND_ROWS) && $SQL_CALC_FOUND_ROWS == true) {
			$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . self::tableize($class) . ' ' . $query;
		} else {

			if(Dbo::$engine === Dbo::ENGINE_MSSQL && isset($options['limit']))
				$sql = 'SELECT TOP '.$options['limit'].' * FROM ' . self::tableize($class) . ' ' .  $query;
			else
				$sql = 'SELECT * FROM ' . self::tableize($class) . ' ' .  $query;

		}

		$std = Dbo::getConnection()->prepare($sql);
		if ($std==false) {
			$arrError=Dbo::getConnection()->errorInfo();
			throw new PDOException($arrError[2]);
		}
		if(Dbo::getConnection()->executeStatement($std, $values))
		{
			$objects = self::getObjectsFromResults($std);
			if (sizeof($objects) == 0)
			{
				if ($std instanceof PDOStatement) {
					$std->closeCursor();
				}
				throw new RecordNotFoundException();
			}
			elseif ((isset($options['limit']) && $options['limit']==1) || preg_match('/LIMIT 1($|[^0-9]+.*$)|TOP 1 /', $sql))
			{
				if ($std instanceof PDOStatement) {
					$std->closeCursor();
				}
				return $objects[0];
			}
			else
			{
				if ($std instanceof PDOStatement) {
					$std->closeCursor();
				}

				if(isset($options['key']))
				{
					$objects2 = array();
					foreach($objects as &$o)
						$objects2[$o->{$options['key']}] = $o;
					unset($objects);
					return $objects2;
				}
				else
					return $objects;

			}
		} else {
			$arrError=Dbo::getConnection()->errorInfo();
			throw new PDOException($arrError[2]);
		}
		if ($std instanceof PDOStatement) {
			$std->closeCursor();
		}
		throw new RecordNotFoundException();
	}

	public final static function classify($table, $flag_pluralise = false)
	{
		if ($flag_pluralise){
			$table = self::unpluralise($table);
		}
		return str_replace(' ', '_', ucwords(str_replace('_', ' ', str_replace(' ', '', ucwords(str_replace('-', ' ', $table))))));
	}

	public final static function tableize($class)
	{
		static $cache = array();

		if (is_object($class))
		{
			return self::tableize(get_class($class));
		}
		elseif (isset($cache[$class]))
		{
			return $cache[$class];
		}
		else
		{

			switch ($class) {
				//Add exceptions to the rule
				/*
				case 'Example-Class': return $cache[$class] = 'exampleclasses';
				break;
				case 'Help': return $cache[$class] = 'help';
				break;
				*/

			}

			$names = explode('_',$class);
			$name = '';
			foreach ($names as $n)
			{
				//$n = strtolower(preg_replace('#(?<=\\w)([A-Z])#', '-\\1', $n));
				$name .= self::pluralise($n).'_';
			}
			$name = substr($name, 0, strlen($name)-1);
			return $cache[$class] = $name;//self::pluralise($name);
		}
	}

	public static function pluralise($name){

		switch ($name) {
			//Add exceptions to the rule
			/*
			case 'family':
			return "families";
			break;
			*/
			default:
				return $name . 's';
			break;
		}

	}

	public static function unpluralise($name){

		switch ($name) {
			//Add exceptions to the rule
			/*
			case 'families':
			return 'family';
			break;
			*/
			default:
				return substr($name, 0, strlen($name)-1);
			break;
		}
	}

	public static function getObjectsFromResults($result, $noCache = false)
	{
		$class = get_called_class();
		/**
		 * @param DBObjectRegistry $reg
		 */
		if (!class_exists($class))
		{
			throw new PDOException('The class ' . $class . ' does not exist');
		}

		if ($result === false)
		{
			return false;
		}

		if (!$result instanceof PDOStatement)
		{
			throw new PDOException(__CLASS__ . '::' . __FUNCTION__ . ' $result must be a PDOStatement');
		}

		$results = array();
		foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $r)
		{
			$obj = new $class();

			foreach ($r as $key => $value)
			{
				switch ($key)
				{
					case 'dateModified':
					case 'dateCreated':
						$value = SW_Date::fromMySQL($value);
						break;
				}
				$obj->$key = $value;
			}

			$results[] = $obj;
		}
		return $results;
	}



	public function save($forceInsert=false)
	{
		// We only want to audit if the object gets saved, so audit within a transaction

		if (is_null($this->{$this->pkey})) {
			$this->hasBeenCreated=true;
		}

		if (self::insertOrUpdate(self::tableize(get_class($this)), $this->fields,$forceInsert)) {

		} else {
			return false;
		}

		// Commit the transaction and return true

		return true;
	}

	public function isSaveNeeded($ignoreFields=array()) {

		if (empty($ignoreFields)) {
			$diff = array_diff($this->_data,$this->_data_original);
		} else {

			$tmp1 = $this->_data;
			$tmp2 = $this->_data_original;

			foreach ($ignoreFields as $key) {
				unset($tmp1[$key]);
				unset($tmp2[$key]);
			}

			$diff = array_diff($tmp1,$tmp2);
		}

		if (empty($diff)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Gets the changes to the current object
	 *
	 * @return array An array with the key being the field name, and the value being an array with keys of old and new, and respective values
	 */
	public function getChanges()
	{
		$changes = array();

		if (!isset($this->fields))
		{
			return $changes;
		}

		foreach ($this->fields as $k)
		{
			if (!array_key_exists($k, $this->_data))
			{
				$this->_data[$k] = null;
			}

			if (!array_key_exists($k, $this->_data_original))
			{
				$this->_data_original[$k] = null;
			}

			if ($this->_data_original[$k] != $this->_data[$k])
			{
				$changes[$k] = array('old' => $this->_data_original[$k], 'new' => $this->_data[$k]);
			}
		}

		return $changes;
	}

	/**
	 * Saves the data in self::$_data in the database either by updating or inserting
	 *
	 * @param array $fields A list of fields to include in the SQL statement
	 * @param boolean $forceInsert set this to force an SQL insert instead of an update
	 */
	protected function insertOrUpdate($table, $fields, $forceInsert = false)
	{
		$action = ($forceInsert || is_null($this->{$this->pkey})) ? 'INSERT INTO' : 'UPDATE';

		$set = '';
		$values = array();
		// if forcing an insert, we need to set the primary key
		if ($forceInsert) {
			$fields[]=$this->pkey;
		}

		foreach ($fields as $f)
		{
			$set .= '`' . $f . '` = ' . ((is_null($this->$f) && ($f != 'dateModified' && $f != 'dateCreated')) ? 'NULL' : '?') . ', ';
			if (!is_null($this->$f) || ($f == 'dateModified' || $f == 'dateCreated'))
			{
				switch ($f)
				{
					case 'dateModified':
						// Update the modified date
						$this->$f = time();
					case 'dateCreated':
						if ($forceInsert || is_null($this->{$this->pkey}))
						{
							$val = date('Y-m-d H:i:s');
						}
						else
						{
							$val = date('Y-m-d H:i:s', $this->$f);
						}
						break;
					default:
						$val = $this->$f;
					break;
				}

				$values[] = $val;
			}
		}
		$set    = substr($set, 0, -2);

		if (!is_null($this->{$this->pkey}) && !$forceInsert)
		{
			$set .= ' WHERE `' . $this->pkey . '` = ?';
			$values[] = $this->{$this->pkey};
		}

		$query  = sprintf('%s `%s` SET %s', $action, $table, $set);

		$stm    = Dbo::getConnection()->prepare($query);

		if (Dbo::getConnection()->executeStatement($stm,$values))
		{

			if (is_null($this->{$this->pkey}))
			{
				$this->{$this->pkey} = Dbo::getConnection()->lastInsertId();
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	 * Deletes an object, or sets it's deleted flag to 1
	 *
	 * @return boolean
	 */
	public function delete($forceRemoval=false)
	{
		try {

			$this->onDelete($forceRemoval);

			if (in_array('deleted',$this->fields) && !$forceRemoval) {
				$this->deleted = 1;

				$result = $this->save();
				if ($result!==false) {
					return true;
				} else {
					return false;
				}
			} else {
				$sql = 'DELETE FROM '. self::tableize(get_class($this)) . ' WHERE ' . $this->pkey .' = ' . Dbo::getConnection()->quote($this->{$this->pkey});
				$result = Dbo::getConnection()->exec($sql);
				if ($result !== false) {

					// also delete all other rows which macth this _rootId
					if (in_array('_rootId',$this->fields)===true) {
						$sql = 'DELETE FROM ' . self::tableize(get_class($this)) . ' WHERE _rootId = ' . Dbo::getConnection()->quote($this->_rootId);
						$result = Dbo::getConnection()->exec($sql);
					}


					$this->hasBeenDeleted=true;

					// If the object extends DBObjectAuditable, then it needs to be audited!
					if ($this instanceof DBObjectAuditable && (!isset($GLOBALS['BATCHMODE']) || $GLOBALS['BATCHMODE']==false))
					{
						// If the audit failed, rollback and return false
						if (!SysAuditLog::audit($this, true))
						{
							Dbo::getConnection()->rollBack();
							return false;
						}
					}

					$this->onAfterDelete();

					return true;
				}
			}
		} catch (Exception $e) {
			throw($e);
		}
		return false;
	}

	public function onDelete() {
		return true;
	}
	public function onAfterDelete() {
		return true;
	}
	public function onAfterSave() {
	}
}

class DBException extends RuntimeException {}
class RecordNotFoundException extends DBException {}