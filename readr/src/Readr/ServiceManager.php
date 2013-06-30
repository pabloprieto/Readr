<?php

namespace Readr;

class ServiceManager
{
	
	protected $services;
	
	public function __construct()
	{
		$this->services = array();
	}

	public function get($name)
	{
		if (!array_key_exists($name, $this->services)) {
			return null;
		}
		
		return $this->services[$name];
	}
	
	public function set($name, $service)
	{
		$this->services[$name] = $service;
	}

}