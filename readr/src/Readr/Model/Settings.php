<?php
/**
 * Readr
 *
 * @link    http://github.com/pabloprieto/Readr
 * @author  Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Model;

use PDO;

class Settings extends AbstractModel
{

	public function get($name, $default = null)
	{
		$sql = "SELECT * FROM settings WHERE name = :name LIMIT 1";

		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':name'  => $name
		));

		$row = $statement->fetch(PDO::FETCH_ASSOC);
		return empty($row) ? $default : $row['value'];
	}
	
	public function set($name, $value)
	{
		$sql = "INSERT OR REPLACE INTO settings (name,value) VALUES (:name,:value)";
		
		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':name'  => trim($name),
			':value' => trim($value)
		));

		return $statement->rowCount();
	}
	
	public function delete($name)
	{
		$sql = "DELETE FROM settings WHERE name = :name";
		
		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':name'  => trim($name)
		));

		return $statement->rowCount();
	}

}