<?php

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
		unset($_SESSION['username']);
		return $this->redirect();
	}
	
	protected function checkCredentials($username, $password)
	{
		$settings = $this->getServiceManager()->get('settings');
		
		if (
			$username == $settings->get('username') && 
			password_verify($password, $settings->get('password'))
		) {
			session_start();
			$_SESSION['username'] = $username;
			return true;
		}
		
		return false;
	}

}