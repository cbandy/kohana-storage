<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @package     Storage
 *
 * @author      Chris Bandy
 * @copyright   (c) 2010 Chris Bandy
 * @license     http://www.opensource.org/licenses/isc-license.txt
 *
 * Read-only persistent Key-Value storage using PHP arrays
 */
class Kohana_Storage_PHP extends Storage
{
	/**
	 * Create a PHP storage instance
	 *
	 *  Configuration        | Type   | Description
	 *  -------------        | ----   | -----------
	 *  filename             | string | Path to the file
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $name   Instance name
	 * @param   array   $config Configuration
	 */
	protected function __construct($name, $config)
	{
		parent::__construct($name, $config);

		if (empty($this->_config['filename']))
			throw new Kohana_Exception('Filename must be configured');

		$this->config['filename'] = realpath($this->_config['filename']);
	}

	/**
	 * Delete a value. Does not write directly to storage.
	 *
	 * @param   string  $key
	 * @return  $this
	 */
	public function delete($key)
	{
		unset($this->_values[$key]);

		return $this;
	}

	/**
	 * Delete all values. Does not write directly to storage.
	 *
	 * @return  $this
	 */
	public function delete_all()
	{
		$this->_values = array();

		return $this;
	}

	public function get($key, $default = NULL)
	{
		if (array_key_exists($key, $this->_values))
			return $this->_values[$key];

		return $default;
	}

	public function load()
	{
		$this->_values = array();

		if (file_exists($this->_config['filename']))
		{
			$this->_values = Kohana::load($this->_config['filename']);
		}

		return $this;
	}

	/**
	 * @throws  Kohana_Exception
	 */
	public function save()
	{
		throw new Kohana_Exception('Storage_PHP is read-only');
	}
}
