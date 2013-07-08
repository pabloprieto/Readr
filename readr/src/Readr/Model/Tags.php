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

class Tags extends AbstractModel
{

	public function fetch($name)
	{
		$statement = $this->getDb()->prepare("SELECT name, COUNT(feed_id) AS feeds_count FROM tags WHERE name = :name GROUP BY name");
		$statement->execute(array(
			':name' => trim($name)
		));

		return $statement->fetch(PDO::FETCH_ASSOC);
	}

	public function fetchAll()
	{
		$statement = $this->getDb()->prepare("SELECT name, COUNT(feed_id) AS feeds_count FROM tags GROUP BY name");
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function insert($name, $feed_id)
	{
		$statement = $this->getDb()->prepare("INSERT INTO tags (name,feed_id) VALUES (:name,:feed_id)");
		$statement->execute(array(
			':name'    => trim($name),
			':feed_id' => $feed_id
		));

		return $statement->rowCount();
	}

	public function update($name, $newName)
	{
		$statement = $this->getDb()->prepare("UPDATE tags SET name = :newName WHERE name = :name");
		$statement->execute(array(
			':name'    => trim($name),
			':newName' => trim($newName)
		));

		return $statement->rowCount();
	}

	public function remove($feed_id)
	{
		$statement = $this->getDb()->prepare("DELETE FROM tags WHERE feed_id = :feed_id");
		$statement->execute(array(
			':feed_id' => $feed_id
		));

		return $statement->rowCount();
	}

	public function delete($name)
	{
		$statement = $this->getDb()->prepare("DELETE FROM tags WHERE name = :name");
		$statement->execute(array(
			':name' => trim($name)
		));

		return $statement->rowCount();
	}

}