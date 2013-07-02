<?php

namespace Readr;

use PDO;
use Readr\Model\Settings;

class App
{
	protected static $basePath;
	protected $serviceManager;

	public function run()
	{
		$this->serviceManager = new ServiceManager;
		
		$this->initDb();
		$this->initSettings();
		$this->checkInstall();
		
		try {
			$this->route();
		} catch (\Exception $e) {
			header($_SERVER['SERVER_PROTOCOL'] . ' ' . $e->getCode());
			die($e->getMessage());
		}
	}

	public static function getBasePath()
	{
		if (!self::$basePath) {
			$path = dirname($_SERVER['SCRIPT_NAME']);
			self::$basePath = rtrim($path, '/') . '/';
		}
		
		return self::$basePath;
	}
	
	public static function getRelease()
	{
		return array(0,5,1);
	}
	
	public static function getVersion()
	{
		return 20130703;
	}

	protected function route()
	{
		$path = $this->getPathInfo();

		if (empty($path)) {

			$controllerName = 'index';
			$actionName = 'index';
			$args = array();

		} else {
			
			$segments = explode('/', $path);

			$controllerName = $segments[0];
			$actionName = isset($segments[1]) ? $segments[1] : 'index';
			$args = filter_var_array(array_slice($segments, 2), FILTER_SANITIZE_STRING);

		}

		$class = '\\Readr\\Controller\\' . ucfirst($controllerName) . 'Controller';
		
		if (!class_exists($class)) {
			throw new \Exception("Page not found", 404);
		}

		$controller = new $class($this->serviceManager);
		$method = $actionName . 'Action';

		if (!method_exists($controller, $method)) {
			throw new \Exception("Page not found", 404);
		}

		$response = call_user_func_array(array($controller, $method), $args);

		if (is_string($response)) {

			echo $response;

		} elseif (is_array($response) || is_null($response)) {

			$template = 'readr/views/' . strtolower($controllerName) . '/' . strtolower($actionName) . '.phtml'; 
			$view = new View($template, $response);

			$layout = new View('readr/views/layout.phtml', array(
				'title'   => 'Readr',
				'content' => $view->render()
			));

			echo $layout->render();

		}
	}
	
	protected function initDb()
	{
		if (!is_dir('data')) {
			mkdir('data', 0755);
		}
	
		$db = new PDO('sqlite:data/reader.db');
		$db->exec('PRAGMA foreign_keys=ON');
		
		$this->serviceManager->set('db', $db);
	}
	
	protected function initSettings()
	{
		$db = $this->serviceManager->get('db');
		$settings = new Settings($db);
		
		$this->serviceManager->set('settings', $settings);
	}

	protected function checkInstall()
	{
		$db = $this->serviceManager->get('db');
		
		$statement = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
		$count = (int) $statement->fetchColumn(0);
		
		if ($count == 0) {
			
			$db->exec(
				'CREATE TABLE "feeds" (
				"id" INTEGER PRIMARY KEY AUTOINCREMENT,
				"title" TEXT NOT NULL,
				"url" TEXT NOT NULL UNIQUE,
				"link" TEXT,
				"last_update" INTEGER,
				"last_error" TEXT)'
			);
			           
			$db->exec(
				'CREATE TABLE "tags" (
				"name" TEXT NOT NULL,
				"feed_id" INTEGER NOT NULL REFERENCES "feeds"("id") ON DELETE CASCADE,
				PRIMARY KEY ("name", "feed_id"))'
			);
	
			$db->exec(
				'CREATE TABLE "entries" (
				"id" INTEGER PRIMARY KEY AUTOINCREMENT,
				"feed_id" INTEGER NOT NULL REFERENCES "feeds"("id") ON DELETE CASCADE,
				"title" TEXT NOT NULL,
				"content" TEXT NOT NULL,
				"author" TEXT,
				"link" TEXT NOT NULL UNIQUE,
				"date" INTEGER NOT NULL,
				"read" INTEGER NOT NULL DEFAULT 0,
				"favorite" INTEGER NOT NULL DEFAULT 0)'
			);
			
			$db->exec(
				'CREATE TABLE "settings" (
				"name" TEXT PRIMARY KEY,
				"value" TEXT)'
			);
			
			$settings = $this->serviceManager->get('settings');
			$settings->set('version', self::getVersion());
			
			return false;
			
		}
		
		return true;
	}

	protected function getPathInfo()
	{
		if (isset($_SERVER['PATH_INFO'])) {
	        return ltrim($_SERVER['PATH_INFO'], '/');
	    }

	    $basePath = self::getBasePath();
	    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	    return (string) substr($uri, strpos($uri, $basePath) + strlen($basePath));
	}

}