<?php
/**
 * Readr
 *
 * @link	http://github.com/pabloprieto/Readr
 * @author	Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Controller;

use Readr\App;
use Readr\Model;
use Readr\ServiceManager;

abstract class AbstractController
{

	/**
	 * @var ServiceManager
	 */
	protected $serviceManager;

	/**
	 * @param ServiceManager $serviceManager
	 * @return void
	 */
	public function __construct(ServiceManager $serviceManager)
	{
		$this->serviceManager = $serviceManager;
		$this->init();
	}

	public function init(){}

	/**
	 * @return bool
	 */
	protected function checkAuth()
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

		return false;
	}

	/**
	 * @return ServiceManager
	 */
	protected function getServiceManager()
	{
		return $this->serviceManager;
	}

	/**
	 * @param string $name
	 * @param string $default (default: null)
	 * @return string|null
	 */
	protected function getParam($name, $default = null)
	{
		if (!isset($_REQUEST[$name])) {
			return $default;
		}

		return filter_var($_REQUEST[$name], FILTER_SANITIZE_STRING);
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	protected function getFile($name)
	{
		if (isset($_FILES[$name])) {
			return $_FILES[$name];
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function getQueryData()
	{
		return filter_var_array($_GET, FILTER_SANITIZE_STRING);
	}

	/**
	 * @return array
	 */
	protected function getPostData()
	{
		return filter_var_array($_POST, FILTER_SANITIZE_STRING);
	}

	/**
	 * @return array
	 */
	protected function getInputData()
	{
		$content = file_get_contents("php://input");
		return json_decode($content, true);
	}

	/**
	 * @return string
	 */
	protected function getHttpMethod()
	{
		if (array_key_exists('HTTP_X_HTTP_METHOD_OVERRIDE', $_SERVER)) {
			return strtolower($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		}
		
		return strtolower($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * @param string $url (default: '')
	 * @return void
	 */
	protected function redirect($url = '')
	{
		$url = App::getBasePath() . $url;
		header('Location: ' . $url);
		exit;
	}

}