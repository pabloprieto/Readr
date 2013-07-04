<?php
/**
 * Readr
 *
 * @link    http://github.com/pabloprieto/Readr
 * @author  Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr;

class View
{
	
	/**
	 * @var string
	 */
	protected $path;
	
	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param string $path
	 * @param array $data (default: null)
	 * @return void
	 */
	public function __construct($path, array $data = null)
	{
		$this->path = $path;
		$this->data = $data;
	}

	public function __get($name)
	{
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function __set($name, $value)
	{
		if (!$this->data) $this->data = array();
		$this->data[$name] = $value;
	}

	/**
	 * @return string
	 * @throws \RuntimeException
	 */
	public function render()
	{
		if(!file_exists($this->path)){
			throw new \RuntimeException(sprintf(
				"Can't render template '%s', file does not exists.",
				$this->path
			));
		}
		
		ob_start();
		include $this->path;
		return ob_get_clean();
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public function escape($value)
	{
		return htmlspecialchars($value);
	}

	/**
	 * @param string $file (default: '')
	 * @return string
	 */
	protected function basePath($file = '')
	{
		$basePath = App::getBasePath();
		return $basePath . ltrim($file, '/');
	}

}