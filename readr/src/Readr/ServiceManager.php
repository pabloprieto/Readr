<?php
/**
 * Readr
 *
 * @link    http://github.com/pabloprieto/Readr
 * @author  Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr;

use RuntimeException;

class ServiceManager
{

	/**
	 * @var array
	 */
	protected $factories;

	/**
	 * @var array
	 */
	protected $instances;

	/**
	 * @param array $factories (default: array())
	 * @return void
	 */
	public function __construct($factories = array())
	{
		$this->factories = $factories;
		$this->instances = array();
	}

	/**
	 * @param string $name
	 * @param callable $factory
	 * @return void
	 */
	public function add($name, $factory)
	{
		$this->factories[$name] = $factory;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get($name)
	{
		if (!array_key_exists($name, $this->instances)) {
			$this->create($name);
		}

		return $this->instances[$name];
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has($name)
	{
		return array_key_exists($name, $this->factories);
	}

	/**
	 * @param string $name
	 * @return void
	 * @throws RuntimeException
	 */
	protected function create($name)
	{
		if (!$this->has($name)) {
			throw new RuntimeException(sprintf(
				"%s: Invalid service name '%s'.",
				get_class($this),
				$name
			));
		}

		if (!is_callable($this->factories[$name])) {
			throw new RuntimeException(sprintf(
				"%s: Can't create service '%s', factory is not callable.",
				get_class($this),
				$name
			));
		}

		$this->instances[$name] = $this->factories[$name]($this);
	}

}