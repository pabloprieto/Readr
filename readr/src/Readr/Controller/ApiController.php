<?php
/**
 * Readr
 *
 * @link    http://github.com/pabloprieto/Readr
 * @author  Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Controller;

use SimplePie;

class ApiController extends AbstractController
{

	public function init()
	{
		if (!$this->checkAuth()) {
			throw new \Exception(json_encode(array(
				'error' => 'Authentication required'
			)), 403);
		};
	}

	public function feedsAction($id = null)
	{
		header('Content-type: application/json');
	
		$method = $this->getHttpMethod();
		$model  = $this->getServiceManager()->get('feeds');
		
		if ($id) {
			$feed = $model->fetch($id);
								
			if (!$feed) {
				throw new \Exception(json_encode(array(
					'error' => 'Feed not found'
				)), 404);
			}
		}

		switch ($method) {

			case 'put':
			case 'patch':
				if ($id) {
					$data = $this->getInputData();
					$result = $model->update(
						$id, 
						$data['title'], 
						$data['url']
					);
					
					if ($data['tags']) {
						$tags = explode(',', $data['tags']);
						$tagsModel = $this->getServiceManager()->get('tags');
						$tagsModel->remove($id);
						foreach ($tags as $tag) {
							$tagsModel->insert($tag, $id);
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
				
				$result = $model->insert(
					$data['title'], 
					$data['url'],
					$data['link']
				);
				
				if (!$result) {
					throw new \Exception(json_encode(array(
						 'error' => 'A feed with the same url is already registered'
					)), 400);
				}
				
				$id = $model->lastInsertId();
				
				if ($data['tags']) {
					$tags = explode(',', $data['tags']);
					$tagsModel = $this->getServiceManager()->get('tags');
					foreach ($tags as $tag) {
						$tagsModel->insert($tag, $id);
					}
				}
				
				$items = $simplePie->get_items();

				foreach ($items as $item) {
					if (!$item) continue;

					$author = $item->get_author();
					$entriesModel = $this->getServiceManager()->get('entries');
					$entriesModel->insert(
						$id,
						$item->get_title(),
						$item->get_content(),
						$author ? $author->get_name() : null,
						$item->get_permalink(),
						$item->get_date('U')
					);
				}
				
				$model->setUpdateData(
					$id, 
					time()
				);
				
				$feed = $model->fetch($id);
				return json_encode($feed);

			case 'delete':
				if ($id) {
					$result = $model->delete($id);
				}

				break;

			case 'get':
			default:
				if ($id) {
					return json_encode($feed);
				} else {
					$feeds = $model->fetchAll();
					return json_encode($feeds);
				}

		}
		
		return false;
	}

	public function tagsAction($name = null)
	{
		header('Content-type: application/json');
	
		$method = $this->getHttpMethod();
		$model  = $this->getServiceManager()->get('tags');
		
		if ($name) {
			$name = urldecode($name);
			$tag  = $model->fetch($name);
			
			if (!$tag) {
				throw new \Exception(json_encode(array(
					'error' => 'Tag not found'
				)), 404);
			}
		}

		switch ($method) {

			case 'put':
				if ($name) {
					$name = urldecode($name);
					$data = $this->getInputData();
					$model->update($name, $data['name']);
				}

				break;

			case 'delete':
				if ($name) {
					$name = urldecode($name);
					$model->delete($name);
				}
				
				break;

			case 'get':
			default:
				if ($name) {
					return json_encode($tag);
				} else {
					$tags = $model->fetchAll();
					return json_encode($tags);
				}

		}
		
		return false;
	}

	public function entriesAction($id = null)
	{
		header('Content-type: application/json');
	
		$method = $this->getHttpMethod();
		$model  = $this->getServiceManager()->get('entries');
		
		if ($id) {
			$entry = $model->fetch($id);
		
			if (!$entry) {
				throw new \Exception(json_encode(array(
					'error' => 'Entry not found'
				)), 404);
			}
		}

		switch ($method) {

			case 'put':
			case 'patch':

				$data = $this->getInputData();

				if (isset($data['read'])) {
					if ($id) {
						$model->updateReadStatus($data['read'], $id);
					} elseif (isset($data['feed_id'])) {
						$model->updateReadStatus($data['read'], null, $data['feed_id']);
					} elseif (isset($data['tag'])) {
						$model->updateReadStatus($data['read'], null, null, $data['tag']);
					} else {
						$model->updateReadStatus($data['read']);
					}
				} elseif ($id && isset($data['favorite'])) {
					$model->updateFavoriteStatus($data['favorite'], $id);
				}

				break;
			
			case 'get':
			default:

				if ($id) {
					return json_encode($entry);
				} else {
					$offset = $this->getParam('offset', 0);
					$limit	 = $this->getParam('limit', 50);
					
					$params = $this->getQueryData();
					unset($params['offset']);
					unset($params['limit']);

					$entries = $model->fetchAll($limit, $offset, $params);
					return json_encode($entries);
				}
				
		}
		
		return false;
	}

	public function faviconAction($feed_id)
	{
		$model = $this->getServiceManager()->get('feeds');
		$feed  = $model->fetch($feed_id);
		
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