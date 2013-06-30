<?php

namespace Readr\Controller;

use Readr\App;
use Readr\Model;
use Readr\ServiceManager;

abstract class AbstractController
{
	
	protected $serviceManager;
	protected $feedsModel;
	protected $tagsModel;
	protected $entriesModel;

	public function __construct(ServiceManager $serviceManager)
	{
		$this->serviceManager = $serviceManager;
		$this->init();
	}
	
	public function init()
	{}
	
	protected function checkAuth($message = null)
	{
		$settings = $this->getServiceManager()->get('settings');
		$username = $settings->get('username');
		
		if (!$username) {
			return true;
		}
		
		session_start();
		
		if (array_key_exists('username', $_SESSION) && $username == $_SESSION['username']) {
			return true;
		}
		
		if ($message) {
			throw new \Exception($message, 403);
		} else {
			return $this->redirect('login');
		}
	}
	
	protected function getServiceManager()
	{
		return $this->serviceManager;
	}
	
	protected function getFeedsModel()
	{
		if (!$this->feedsModel) {
			$db = $this->getServiceManager()->get('db');
			$this->feedsModel = new Model\Feeds($db);
		}

		return $this->feedsModel;
	}

	protected function getTagsModel()
	{
		if (!$this->tagsModel) {
			$db = $this->getServiceManager()->get('db');
			$this->tagsModel = new Model\Tags($db);
		}

		return $this->tagsModel;
	}

	protected function getEntriesModel()
	{
		if (!$this->entriesModel) {
			$db = $this->getServiceManager()->get('db');
			$this->entriesModel = new Model\Entries($db);
		}

		return $this->entriesModel;
	}

	protected function getParam($name, $default = null)
	{
		if (!isset($_REQUEST[$name])) {
			return $default;
		}

		return filter_var($_REQUEST[$name], FILTER_SANITIZE_STRING);
	}

	protected function getFile($name)
	{
		if (isset($_FILES[$name])) {
			return $_FILES[$name];
		}

		return null;
	}

	protected function getQueryData()
	{
		return filter_var_array($_GET, FILTER_SANITIZE_STRING);
	}

	protected function getPostData()
	{
		return filter_var_array($_POST, FILTER_SANITIZE_STRING);
	}	

	protected function getInputData()
	{
		$content = file_get_contents("php://input");
		return json_decode($content, true);
	}

	protected function getHttpMethod()
	{
		return strtolower($_SERVER['REQUEST_METHOD']);
	}

	protected function redirect($url = '')
	{
		$url = App::getBasePath() . $url;
		header('Location: ' . $url);
		exit;
	}

}