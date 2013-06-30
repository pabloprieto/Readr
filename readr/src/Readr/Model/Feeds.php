<?php

namespace Readr\Model;

use PDO;

class Feeds extends AbstractModel
{

	public function fetch($id)
	{
		$sql = "SELECT feeds.*, GROUP_CONCAT(tags.name,',') AS tags FROM feeds 
		        LEFT JOIN tags ON tags.feed_id = feeds.id 
		        WHERE id = :id LIMIT 1";

		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':id'  => $id
		));

		$row = $statement->fetch(PDO::FETCH_ASSOC);
		return empty($row) ? null : $row;
	}

	public function fetchAll($limit = -1, $offset = 0, $order = 'title ASC')
	{
		$sql = "SELECT 
		        feeds.*, 
		        (SELECT GROUP_CONCAT(tags.name) FROM tags WHERE tags.feed_id = feeds.id) AS tags,
		        COUNT(entries.id) AS entries_count,
		        SUM(CASE WHEN entries.read = 0 THEN 1 ELSE 0 END) AS unread_count
		        FROM feeds 
		        LEFT JOIN entries ON entries.feed_id = feeds.id 
		        GROUP BY feeds.id
		        ORDER BY {$order} LIMIT :limit OFFSET :offset";

		$statement = $this->getDb()->prepare($sql);
		$statement->execute(array(
			':limit'  => $limit,
			':offset' => $offset
		));

		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function insert($title, $url, $link)
	{
		$statement = $this->getDb()->prepare("INSERT INTO feeds (title,url,link) VALUES (:title,:url,:link)");
		$statement->execute(array(
			':title' => trim($title),
			':url'   => trim($url),
			':link'  => trim($link)
		));

		return $statement->rowCount();
	}

	public function update($id, $title, $url)
	{
		$statement = $this->getDb()->prepare("UPDATE feeds SET title = :title, url = :url WHERE id = :id");
		$statement->execute(array(
			':title' => trim($title),
			':url'   => trim($url),
			':id'    => $id
		));

		return $statement->rowCount();
	}

	public function setUpdateData($id, $timestamp, $error = null)
	{
		$statement = $this->getDb()->prepare("UPDATE feeds SET last_update = :timestamp, last_error = :error WHERE id = :id");
		$statement->execute(array(
			':id'        => $id,
			':timestamp' => $timestamp,
			':error'     => $error
		));

		return $statement->rowCount();
	}

	public function delete($id)
	{
		$statement = $this->getDb()->prepare("DELETE FROM feeds WHERE id = :id");
		$statement->execute(array(
			':id' => $id
		));

		return $statement->rowCount();
	}

}