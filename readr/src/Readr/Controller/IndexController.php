<?php

namespace Readr\Controller;

class IndexController extends AbstractController
{

	public function init()
	{
		$this->checkAuth();
	}

	public function indexAction()
	{
		$settings = $this->getServiceManager()->get('settings');
		
		return array(
			'username' => $settings->get('username')
		);
	}

}