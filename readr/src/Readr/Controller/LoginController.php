<?php
/**
 * Readr
 *
 * @link	http://github.com/pabloprieto/Readr
 * @author	Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Controller;

class LoginController extends AbstractController
{

	public function indexAction()
	{
		$data   = $this->getPostData();
		$errors = array();

		if (!empty($data)) {

			$auth = $this->checkCredentials(
				$data['username'],
				$data['password']
			);

			if ($auth) {
				return $this->redirect();
			} else {
				$errors[] = 'Wrong username or password.';
			}

		}

		return array(
			'errors' => $errors
		);
	}

	public function signoutAction()
	{
		session_start();
		session_destroy();
		$this->redirect();
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function checkCredentials($username, $password)
	{
		$settings = $this->getServiceManager()->get('settings');

		if (
			$username == $settings->get('username') &&
			password_verify($password, $settings->get('password'))
		) {
			session_start();
			session_set_cookie_params(86400*30);
			session_regenerate_id(true);
			$_SESSION['username'] = $username;
			return true;
		}

		return false;
	}

}