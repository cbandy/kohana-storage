<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @package     Storage
 *
 * @author      Chris Bandy
 * @copyright   (c) 2010 Chris Bandy
 * @license     http://www.opensource.org/licenses/isc-license.txt
 *
 * Persistent Key-Value storage using XML
 *
 * File format:
 *
 *     <?xml version="1.0" encoding="UTF-8" ?>
 *     <values>
 *       <value xml:id=""></value>
 *       ...
 *     </values>
 *
 * @todo Concurrency issues
 */
class Kohana_Storage_XML extends Storage
{
	/**
	 * @var DOMDocument
	 */
	protected $_document;

	/**
	 * Create an XML storage instance
	 *
	 *  Configuration        | Type   | Description
	 *  -------------        | ----   | -----------
	 *  encoding  (optional) | string | Encoding of the file, defaults to `UTF-8`
	 *  filename             | string | Path to the file
	 *
	 * @throws  Kohana_Exception
	 * @param   string  $name   Instance name
	 * @param   array   $config Configuration
	 */
	protected function __construct($name, $config)
	{
		parent::__construct($name, $config);

		if (empty($this->_config['encoding']))
		{
			$this->_config['encoding'] = 'UTF-8';
		}

		if (empty($this->_config['filename']))
			throw new Kohana_Exception('Filename must be configured');

		$this->config['filename'] = realpath($this->_config['filename']);
	}

	/**
	 * Delete a value. Does not write directly to storage.
	 *
	 * @see Storage::save()
	 *
	 * @param   string  $key
	 * @return  $this
	 */
	public function delete($key)
	{
		unset($this->_values[$key]);

		if ($element = $this->_document->getElementById($key))
		{
			$this->_document->documentElement->removeChild($element);
		}

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
		if (file_exists($this->_config['filename']))
		{
			unlink($this->_config['filename']);
		}

		return $this->load();
	}

	public function get($key, $default = NULL)
	{
		if (array_key_exists($key, $this->_values))
			return $this->_values[$key];

		if ( ! $element = $this->_document->getElementById($key))
			return $default;

		return $this->_values[$key] = unserialize($element->nodeValue);
	}

	public function load()
	{
		$this->_document = new DOMDocument('1.0', $this->_config['encoding']);

		if (file_exists($this->_config['filename']))
		{
			$this->_document->load($this->_config['filename']);
		}

		$this->_values = array();

		return $this;
	}

	public function save()
	{
		if ( ! $root = $this->_document->documentElement)
		{
			$root = $this->_document->createElement('values');
			$this->_document->appendChild($root);
		}

		if ($this->_values)
		{
			foreach ($this->_values as $key => $value)
			{
				if ($element = $this->_document->getElementById($key))
				{
					$element->nodeValue = serialize($value);
				}
				else
				{
					$element = $this->_document->createElement('value', serialize($value));
					$element->setAttribute('xml:id', $key);

					$root->appendChild($element);
				}
			}
		}

		$this->_document->save($this->_config['filename']);

		return $this;
	}
}
