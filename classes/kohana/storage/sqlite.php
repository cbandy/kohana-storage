<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @package     Storage
 *
 * @author      Chris Bandy
 * @copyright   (c) 2010 Chris Bandy
 * @license     http://www.opensource.org/licenses/isc-license.txt
 *
 * Persistent Key-Value storage using SQLite
 */
class Kohana_Storage_SQLite extends Storage
{
	/**
	 * @var PDOStatement
	 */
	protected $_delete;

	/**
	 * @var PDOStatement
	 */
	protected $_get;

	/**
	 * @var PDO Database connection
	 */
	protected $_pdo;

	/**
	 * Create a SQLite storage instance
	 *
	 *  Configuration        | Type   | Description
	 *  -------------        | ----   | -----------
	 *  encoding  (optional) | string | Encoding of the database
	 *  filename             | string | Path to the database
	 *  options   (optional) | array  | PDO options
	 *  table                | string | Table in which to store values
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $name   Instance name
	 * @param   array   $config Configuration
	 */
	protected function __construct($name, $config)
	{
		parent::__construct($name, $config);

		// Use exceptions for all errors
		$this->_config['options'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

		if (empty($this->_config['filename']))
			throw new Kohana_Exception('Filename must be configured');

		if (empty($this->_config['table']))
			throw new Kohana_Exception('Table must be configured');
	}

	/**
	 * Delete a value. Writes directly to storage.
	 *
	 * @throws  Exception
	 * @param   string  $key
	 * @return  $this
	 */
	public function delete($key)
	{
		unset($this->_values[$key]);

		$this->_delete->execute(array($key));

		return $this;
	}

	/**
	 * Delete all values. Writes directly to storage.
	 *
	 * @throws  Exception
	 * @return  $this
	 */
	public function delete_all()
	{
		$this->_values = array();

		$this->_pdo->exec('DELETE FROM "'.$this->_config['table'].'"');

		return $this;
	}

	public function get($key, $default = NULL)
	{
		if (array_key_exists($key, $this->_values))
			return $this->_values[$key];

		$this->_get->execute(array($key));

		if ( ! $result = $this->_get->fetchColumn())
			return $default;

		return $this->_values[$key] = unserialize($result);
	}

	public function load()
	{
		if ( ! $this->_pdo)
		{
			// Open the file
			$this->_pdo = new PDO('sqlite:'.$this->_config['filename'], NULL, NULL, $this->_config['options']);

			if ( ! empty($this->_config['encoding']))
			{
				// Set the encoding
				$this->_pdo->exec('PRAGMA encoding = "'.$this->_config['encoding'].'"');
			}

			// Create the schema, if necessary
			$this->_pdo->exec('CREATE TABLE IF NOT EXISTS "'.$this->_config['table'].'" ( "key" TEXT PRIMARY KEY NOT NULL, "value" TEXT )');

			// Prepare commands
			$this->_delete = $this->_pdo->prepare('DELETE FROM "'.$this->_config['table'].'" WHERE "key" = ?');
			$this->_get = $this->_pdo->prepare('SELECT "value" FROM "'.$this->_config['table'].'" WHERE "key" = ?');
		}

		$this->_values = array();

		return $this;
	}

	public function save()
	{
		if ($this->_values)
		{
			$statement = $this->_pdo->prepare(str_repeat('INSERT OR REPLACE INTO "'.$this->_config['table'].'" ("key","value") VALUES (?,?);', count($this->_values)));

			$i = 0;
			foreach ($this->_values as $key => $value)
			{
				$statement->bindValue(++$i, $key);
				$statement->bindValue(++$i, serialize($value));
			}

			$statement->execute();
		}

		return $this;
	}
}
