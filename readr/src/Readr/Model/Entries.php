<?php

namespace Readr\Model;

use PDO;

class Entries extends AbstractModel
{

	public function fetch($id)
	{
		$sql  = "SELECT entries.*, feeds.title AS feed_title FROM entries";
		$sql .= " JOIN feeds ON feeds.id = entries.feed_id";
		$sql .= " WHERE entries.id = :id LIMIT 1";

		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':id'  => $id
		));

		$row = $statement->fetch(PDO::FETCH_ASSOC);
		return empty($row['id']) ? null : $row;
	}

	public function fetchAll($limit = 50, $offset = 0, $filters = array())
	{
		$columns = array(
			'entries.id',
			'entries.feed_id',
			'entries.title', 
			'entries.date',
			'entries.read', 
			'entries.favorite', 
			'feeds.title AS feed_title',
			'GROUP_CONCAT(tags.name,\',\') AS tags'
		);

		if (isset($filters['feed_id'])) {
			$filters['entries.feed_id'] = $filters['feed_id'];
			unset($filters['feed_id']);
		}

		if (isset($filters['tag'])) {
			$filters['tags.name'] = $filters['tag'];
			unset($filters['tag']);
		}

		$where = array();
		foreach ($filters as $name => $value) {
			$where[] = sprintf("$name = %s", $this->getDb()->quote($value));
		}

		$sql  = "SELECT " . implode(', ', $columns) . " FROM entries";
		$sql .= " JOIN feeds ON feeds.id = entries.feed_id";
		$sql .= " LEFT JOIN tags ON feeds.id = tags.feed_id";
		
		if (count($where)) {
			$sql .= " WHERE " . implode(' AND ', $where);
		}

		$sql .= " GROUP BY entries.id ORDER BY date DESC LIMIT :limit OFFSET :offset";

		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':limit'  => $limit,
			':offset' => $offset
		));

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function insert($feed_id, $title, $content, $author, $link, $date)
	{
		$sql = "INSERT INTO entries (feed_id, title, content, author, link, date) 
		        VALUES (:feed_id, :title, :content, :author, :link, :date)";

		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':feed_id' => $feed_id,
			':title'   => $title,
			':content' => $content,
			':author'  => $author,
			':link'    => $link,
			':date'    => $date
		));

		return $statement->rowCount();
	}

	public function updateReadStatus($read, $id = null, $feed_id = null, $tag_name = null)
	{
		$sql = "UPDATE entries SET read = :read";
		$params = array(':read' => $read ? 1 : 0);

		if ($id) {
			$sql .= " WHERE id = :id";
			$params[':id'] = $id;
		} elseif ($feed_id) {
			$sql .= " WHERE feed_id = :feed_id";
			$params[':feed_id'] = $feed_id;
		} elseif ($tag_name) {
			$sql .= " WHERE feed_id IN (SELECT feed_id FROM tags WHERE name = :tag_name)";
			$params[':tag_name'] = $tag_name;
		} 

		$statement = $this->getDb()->prepare($sql);
		$statement->execute($params);

		return $statement->rowCount();
	}

	public function updateFavoriteStatus($favorite, $id)
	{
		$statement = $this->getDb()->prepare("UPDATE entries SET favorite = :favorite WHERE id = :id");
		$statement->execute(array(
			':favorite' => $favorite,
			':id'       => $id
		));

		return $statement->rowCount();
	}

}