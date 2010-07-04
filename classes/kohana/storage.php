<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @package     Storage
 *
 * @author      Chris Bandy
 * @copyright   (c) 2010 Chris Bandy
 * @license     http://www.opensource.org/licenses/isc-license.txt
 *
 * Persistent Key-Value storage with multiple backends
 */
abstract class Kohana_Storage
{
	/**
	 * @var array   Singleton instances
	 */
	protected static $_instances;

	/**
	 * Get a singleton Storage instance.
	 *
	 * The configuration group will be loaded from the storage configuration file
	 * based on the instance name unless it is passed directly.
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $name   Instance name
	 * @param   array   $config Configuration
	 * @return  Storage
	 */
	public static function instance($name = 'default', $config = NULL)
	{
		if ( ! isset(Storage::$_instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration
				$config = Kohana::config('storage')->$name;
			}

			if ( ! isset($config['type']))
				throw new Kohana_Exception('Storage type not defined in ":name" configuration', array(':name' => $name));

			// Set the driver class name
			$driver = 'Storage_'.$config['type'];

			// Create the storage instance
			new $driver($name, $config);
		}

		return Storage::$_instances[$name];
	}

	/**
	 * @var array   Configuration
	 */
	protected $_config;

	/**
	 * @var string  Instance name
	 */
	protected $_instance;

	/**
	 * @var array
	 */
	protected $_values;

	/**
	 * Create a singleton Storage instance. The driver type is not verified.
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $name   Instance name
	 * @param   array   $config Configuration
	 */
	protected function __construct($name, $config)
	{
		if (isset(Storage::$_instances[$name]))
			throw new Kohana_Exception('Storage instance ":name" already exists', array(':name' => $name));

		$this->_config = $config;
		$this->_instance = $name;

		Storage::$_instances[$name] = $this;
	}

	/**
	 * Delete a value. May or may not write directly to storage.
	 *
	 * @throws  Exception
	 * @param   string  $key
	 * @return  $this
	 */
	abstract public function delete($key);

	/**
	 * Delete all values. May or may not write directly to storage.
	 *
	 * @throws  Exception
	 * @return  $this
	 */
	abstract public function delete_all();

	/**
	 * Retrieve a value. May or may not read directly from storage.
	 *
	 * @throws  Exception
	 * @param   string  $key
	 * @param   mixed   $default    Default value if key does not exist
	 * @return  mixed
	 */
	abstract public function get($key, $default = NULL);

	/**
	 * Load the store into memory. This method should be called before any others.
	 *
	 * @throws  Exception
	 * @return  $this
	 */
	abstract public function load();

	/**
	 * Persist any assigned or deleted values. Call this method to guarantee that
	 * changes from set(), delete() and delete_all() are persisted in the store.
	 *
	 * @throws  Exception
	 * @return  $this
	 */
	abstract public function save();

	/**
	 * Assign a value. May or may not write directly to storage.
	 *
	 * @throws  Exception
	 * @param   string  $key
	 * @param   mixed   $value
	 * @return  $this
	 */
	public function set($key, $value)
	{
		$this->_values[$key] = $value;

		return $this;
	}
}
