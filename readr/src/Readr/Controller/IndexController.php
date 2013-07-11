<?php
/**
 * Readr
 *
 * @link	http://github.com/pabloprieto/Readr
 * @author	Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Controller;

class IndexController extends AbstractController
{

	public function init()
	{
		if (!$this->checkAuth()) {
			$this->redirect('login');
		}
	}

	public function indexAction()
	{
		$settings = $this->getServiceManager()->get('settings');

		return array(
			'username'    => $settings->get('username'),
			'emulateHTTP' => $settings->get('emulateHTTP', 0),
			'collapsed'   => $settings->get('collapsed', '{}')
		);
	}

}