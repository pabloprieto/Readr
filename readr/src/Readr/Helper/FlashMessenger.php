<?php
/**
 * Readr
 *
 * @link	http://github.com/pabloprieto/Readr
 * @author	Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Helper;

class FlashMessenger
{

	protected $prefix = 'messages_';

	public function __construct()
	{
		if (session_id() == '') {
			session_start();
		}
	}
	
	/**
	 * @param string $message
	 * @param string $namespace (default: 'default')
	 * @return FlashMessenger
	 */
	public function add($message, $namespace = 'default')
	{
		$key = $this->prefix . $namespace;
	
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = array();
		}
		
		$_SESSION[$key][] = $message;
		
		return $this;
	}
	
	/**
	 * @param string $namespace (default: 'default')
	 * @return bool
	 */
	public function hasMessages($namespace = 'default')
	{
		$key = $this->prefix . $namespace;
		return isset($_SESSION[$key]) && count($_SESSION[$key]) > 0;
	}
	
	/**
	 * @param string $namespace (default: 'default')
	 * @param bool $clear (default: true)
	 * @return array|null
	 */
	public function getMessages($namespace = 'default', $clear = true)
	{
		$key = $this->prefix . $namespace;
	
		if (!isset($_SESSION[$key])) {
			return null;
		}
		
		$messages = $_SESSION[$key];
		
		if ($clear) {
			unset($_SESSION[$key]);
		}
		
		return $messages;
	}

}