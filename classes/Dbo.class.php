<?php

class Dbo extends PDO
{
	/**
	 * Singleton instance of the connection to the database
	 *
	 * @var DB_PDO
	 */
	private static $connection = null;

	/**
	 * The character set of the connection
	 *
	 * @var string
	 */
	const CHARSET = 'utf8';

	/**
	 * Number of queries
	 *
	 * @var int
	 */
	public $queryCount = 0;

	/**
	 * The last query run
	 *
	 * @var string
	 */
	public $lastQuery = null;

	/**
	 * Store of the last 10 queries
	 *
	 * @var array
	 */
	public $arrQueries = array();

	/**
	 * The time spent doing SQL queries
	 *
	 * @var int
	 */
	public $queryTime = 0;

	/**
	 * A timer to time SQL actions
	 *
	 * @var Timer
	 */
	private $timer = null;

	public static $sqlTableQuoteChar = '"';

	public static $engine = null;

	public static $logQueries = true;

	const ENGINE_MYSQL = 1;
	const ENGINE_ORACLE = 2;
	const ENGINE_MSSQL = 3;

	/**
	 * Gets the singleton instance of DB_PDO
	 *
	 * @param array $params
	 * @return DB_PDO
	 */
	public static final function getConnection($params = null)
	{
		if (is_null(self::$connection))
		{
			if (empty($params))
			{
				throw new InvalidArgumentException('Database configuration parameters not defined');
			}

			if (isset($params['options']) && !is_array($params['options']))
			{
				throw new InvalidArgumentException('$params[options] must be an array');
			}
			elseif (!isset($params['options']))
			{
				$params['options'] = array();
			}

			// Set some defaults for the values which can be overriden by $params
			$params = array_merge(array(
				'engine' 		=> 'mysql',
				'host' 		=> 'localhost',
				'username' 	=> 'root',
				'password' 	=> ''), $params);

			switch($params['engine']) {
				case 'mysql':

					// The initial command is to SET NAMES utf8 and turn on autocommit, which can be overriden by setting it in $params['options'][PDO::MYSQL_ATTR_INIT_COMMAND]
					$params['options'] += array(
							PDO::MYSQL_ATTR_INIT_COMMAND  => 'SET NAMES ' . self::CHARSET,
							PDO::ATTR_AUTOCOMMIT          => 1,
							PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
							PDO::ATTR_PERSISTENT => true
							);

					if (!isset($params['db']))
					{
						throw new InvalidArgumentException('$params[db] must be set');
					}

					self::$sqlTableQuoteChar = '`';
					self::$engine = 'mysql';

					self::$connection = new self('mysql:host=' . $params['host'] . ';dbname=' . $params['db'], $params['username'], $params['password'], $params['options']);

				break;
				case 'oci':

						$params['options'] += array(
							PDO::ATTR_AUTOCOMMIT          => 1,
							PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
							PDO::ATTR_PERSISTENT => true
							);

					if (!isset($params['db']))
					{
						throw new InvalidArgumentException('$params[db] must be set');
					}

					self::$sqlTableQuoteChar = '"';
					self::$engine = 'oci';

					self::$connection = new self('oci:dbname='.$params['host'].'/'. $params['db'].';charset='.self::CHARSET, $params['username'], $params['password'], $params['options']);

				break;
				default:
					throw new InvalidArgumentException('Sorry, your SQL engine of '.$params['engine'].' is not supported at the moment.');
				break;
			}

		}

		return self::$connection;
	}

	private function startTimer()
	{
		if (is_null($this->timer))
		{
			$this->timer = new Timer();
		}

		$this->timer->start();
	}

	private function endTimer()
	{
		if (is_null($this->timer))
		{
			return false;
		}

		$mtime = $this->timer->stop();

		$this->queryTime +=$mtime;
		return $mtime;
	}

	/**
	 * Performs a query
	 *
	 * @param string $query
	 */
	public function exec($query)
	{
		$this->lastQuery = $query;
		$this->startTimer();
		$return = parent::exec($query);
		$mtime = number_format($this->endTimer(),3);
		if ($return !== false)
		{
			$this->queryCount++;
			$this->addLastQueryToArray($mtime.' '.$query);
		}
		return $return;
	}

	/**
	 * Prepare a statement
	 *
	 * @param string $statement (SQL Statement)
	 * @param array $driver_options
	 * @return PDOStatement
	 */
	public function prepare($statement, $driver_options = array())
	{
		if ($this->inTransaction()) { $statement='/* IN TRANSACTION ('.$this->transactionCount.') */ '.$statement; }
		$this->lastQuery=$statement;
		return parent::prepare($statement, $driver_options);
	}

	/**
	 * Execute a statement returned by prepare
	 *
	 * @param PDOStatement $statement
	 * @param array $parameters An array of parameters for the statement
	 * @return mixed
	 */
	public function executeStatement($statement, $parameters)
	{
		if (self::$logQueries==true) {
			$this->startTimer();
		}

		$q = $this->lastQuery;
		if (sizeof($parameters) > 0)
		{
			$q = SW_String::str_replace_many('?', array_map(array($this, 'quote'), $parameters), $q);
		}


		$return = $statement->execute($parameters);
		if (self::$logQueries==true) {
			$mtime = number_format($this->endTimer(),3);
			$this->addLastQueryToArray($mtime.' '.$q);
		}

		if ($return === true)
		{
			$this->queryCount++;
		}
		return $return;
	}

	public function addLastQueryToArray($sql) {
			if (self::$logQueries==true) {
			array_unshift($this->arrQueries,$sql);
		}
	}


}
