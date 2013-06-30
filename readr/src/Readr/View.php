<?php

namespace Readr;

class View
{
	
	protected $path;
	protected $data;

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

	public function render()
	{
		if(file_exists($this->path)){
			ob_start();
			include $this->path;
			return ob_get_clean();
		}
	}

	public function escape($value)
	{
		return htmlspecialchars($value);
	}

	protected function basePath($file = '')
	{
		$basePath = App::getBasePath();
		return $basePath . ltrim($file, '/');
	}

}