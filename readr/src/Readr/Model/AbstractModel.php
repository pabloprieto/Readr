<?php
/**
 * Readr
 *
 * @link	http://github.com/pabloprieto/Readr
 * @author	Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Model;

use PDO;

abstract class AbstractModel
{

	/**
	 * @var PDO
	 */
	protected $db;

	/**
	 * @param PDO $db
	 * @return void
	 */
	public function __construct(PDO $db)
	{
		$this->db = $db;
	}

	/**
	 * @return string
	 */
	public function lastInsertId()
	{
		return $this->getDb()->lastInsertId();
	}

	/**
	 * @return array
	 */
	public function errorInfo()
	{
		return $this->getDb()->errorInfo();
	}

	/**
	 * @return PDO
	 */
	protected function getDb()
	{
		return $this->db;
	}

}