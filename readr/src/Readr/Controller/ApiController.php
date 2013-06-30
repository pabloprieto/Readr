<?php

namespace Readr\Controller;

use SimplePie;

class ApiController extends AbstractController
{

	public function init()
	{
		$this->checkAuth(json_encode(array(
			'error' => 'Authentication required'
		)));
	}

	public function feedsAction($id = null)
	{
		header('Content-type: application/json');
	
		$method = $this->getHttpMethod();
		$feedsModel = $this->getFeedsModel();

		switch ($method) {

			case 'put':
			case 'patch':
				if ($id) {
					$data = $this->getInputData();
					$result = $feedsModel->update(
						$id, 
						$data['title'], 
						$data['url']
					);
					
					if (isset($data['tags'])) {
						$tags = explode(',', $data['tags']);
						$this->getTagsModel()->remove($id);
						foreach ($tags as $tag) {
							$this->getTagsModel()->insert($tag, $id);
						}
					}
				}

				break;

			case 'post':
				$data = $this->getInputData();
				
				$simplePie = new SimplePie();
				$simplePie->enable_cache(false);
				$simplePie->set_feed_url($data['url']);
				$result = $simplePie->init();
				
				if (!$result) {
					throw new \Exception(json_encode(array(
						 'error' => $simplePie->error()
					)), 400);
				}
					
				if ($feeds = $simplePie->get_all_discovered_feeds()) {
					if (is_array($feeds)) $feed = $feeds[0];
					else $feed = $feeds;
					
					$simplePie->set_file($feed);
					$simplePie->init();
					
					$data['url'] = $feed->url;
				}
				
				$data['title'] = $simplePie->get_title();
				$data['link']  = $simplePie->get_permalink();
				
				$result = $feedsModel->insert(
					$data['title'], 
					$data['url'],
					$data['link']
				);
				
				if (!$result) {
					throw new \Exception(json_encode(array(
						 'error' => 'A feed with the same url is already registered'
					)), 400);
				}
				
				$id = $feedsModel->lastInsertId();
				
				if (isset($data['tags'])) {
					$tags = explode(',', $data['tags']);
					foreach ($tags as $tag) {
						$this->getTagsModel()->insert($tag, $id);
					}
				}
				
				$items = $simplePie->get_items();

				foreach ($items as $item) {
					if (!$item) continue;

					$author = $item->get_author();

					$this->getEntriesModel()->insert(
						$id,
						$item->get_title(),
						$item->get_content(),
						$author ? $author->get_name() : null,
						$item->get_permalink(),
						$item->get_date('U')
					);
				}
				
				$feedsModel->setUpdateData(
					$id, 
					time()
				);
				
				$feed = $feedsModel->fetch($id);
				return json_encode($feed);

			case 'delete':
				if ($id) {
					$result = $feedsModel->delete($id);
				}

				break;

			case 'get':
			default:
				if ($id) {
					$feed = $feedsModel->fetch($id);
					return json_encode($feed);
				} else {
					$feeds = $this->getFeedsModel()->fetchAll();
					return json_encode($feeds);
				}

		}
		
		return false;
	}

	public function tagsAction($name = null)
	{
		header('Content-type: application/json');
	
		$method = $this->getHttpMethod();

		switch ($method) {

			case 'put':
				if ($name) {
					$name = urldecode($name);
					$data = $this->getInputData();
					$this->getTagsModel()->update($name, $data['name']);
				}

				break;

			case 'delete':
				if ($name) {
					$name = urldecode($name);
					$this->getTagsModel()->delete($name);
				}
				
				break;

			case 'get':
			default:
				if ($name) {
					$name = urldecode($name);
					$tag = $this->getTagsModel()->fetch($name);
					return json_encode($tag);
				} else {
					$tags = $this->getTagsModel()->fetchAll();
					return json_encode($tags);
				}

		}
		
		return false;
	}

	public function entriesAction($id = null)
	{
		header('Content-type: application/json');
	
		$method = $this->getHttpMethod();

		switch ($method) {

			case 'put':
			case 'patch':

				$data = $this->getInputData();

				if (isset($data['read'])) {
					if ($id) {
						$this->getEntriesModel()->updateReadStatus($data['read'], $id);
					} elseif (isset($data['feed_id'])) {
						$this->getEntriesModel()->updateReadStatus($data['read'], null, $data['feed_id']);
					} elseif (isset($data['tag'])) {
						$this->getEntriesModel()->updateReadStatus($data['read'], null, null, $data['tag']);
					} else {
						$this->getEntriesModel()->updateReadStatus($data['read']);
					}
				} elseif ($id && isset($data['favorite'])) {
					$this->getEntriesModel()->updateFavoriteStatus($data['favorite'], $id);
				}

				break;
			
			case 'get':
			default:

				if ($id) {
					$entry = $this->getEntriesModel()->fetch($id);
					return json_encode($entry);
				} else {
					$offset = $this->getParam('offset', 0);
					$limit	 = $this->getParam('limit', 50);
					
					$params = $this->getQueryData();
					unset($params['offset']);
					unset($params['limit']);

					$entries = $this->getEntriesModel()->fetchAll($limit, $offset, $params);
					return json_encode($entries);
				}
				
		}
		
		return false;
	}

	public function faviconAction($feed_id)
	{
		$feed = $this->getFeedsModel()->fetch($feed_id);
		
		if (!$feed) {
			throw new \Exception(json_encode(array(
				   'error' => 'Feed not found'
			)), 404);
		}
		
		$domain = parse_url($feed['link'], PHP_URL_HOST);
		
		header('HTTP/1.1 301');
		header('Location: https://plus.google.com/_/favicon?domain=' . $domain);
		exit();
	}
	
}