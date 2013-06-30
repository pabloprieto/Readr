<?php

namespace Readr\Model;

use PDO;

abstract class AbstractModel
{

	protected $db;
	
	public function __construct(PDO $db)
	{
		$this->db = $db;
	}

	public function lastInsertId()
	{
		return $this->getDb()->lastInsertId();
	}
	
	public function errorInfo()
	{
    	return $this->getDb()->errorInfo();
	}

	protected function getDb()
	{
		return $this->db;
	}

}