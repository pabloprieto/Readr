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
use Readr\Helper\FlashMessenger;
use Readr\Updater;

class SettingsController extends AbstractController
{

	public function init()
	{
		if (!$this->checkAuth()) {
			$this->redirect('login');
		}
	}

	public function indexAction()
	{
		$settings  = $this->getServiceManager()->get('settings');
		$messenger = new FlashMessenger();
		
		return array(
			'errors'      => $messenger->getMessages('error'),
			'username'    => $settings->get('username'),
			'emulateHTTP' => $settings->get('emulateHTTP', 0),
			'release'     => implode('.', App::getRelease()),
			'version'     => App::getVersion()
		);
	}
	
	public function authAction()
	{
		$settings = $this->getServiceManager()->get('settings');
		$data     = $this->getPostData();
		
		if (isset($data['username']) && !empty($data['username'])) {
		
			$messenger = new FlashMessenger();

			if (empty($data['password'])) {

				$messenger->add('Password is empty', 'error');

			} elseif ($data['password'] == $data['password_confirm']) {

				$hash = password_hash($data['password'], PASSWORD_DEFAULT);

				$settings->set('username', $data['username']);
				$settings->set('password', $hash);

			} else {

				$messenger->add('Password and confirmation do not match.', 'error');

			}

		} else {

			if (isset($_SESSION)) {
				unset($_SESSION['username']);
			}

			$settings->delete('username');
			$settings->delete('password');

		}
		
		$this->redirect('settings');
	}
	
	public function miscAction()
	{
		$settings = $this->getServiceManager()->get('settings');
		$data     = $this->getPostData();
			
		if (isset($data['emulateHTTP'])) {
			$settings->set('emulateHTTP', 1);
		} else {
			$settings->delete('emulateHTTP');
		}
		
		$this->redirect('settings');
	}
	
	public function collapsedAction()
	{
		$data = $this->getPostData();
		
		if (isset($data['name'])) {
			
			$settings  = $this->getServiceManager()->get('settings');
			$collapsed = json_decode($settings->get('collapsed', '{}'));
			
			if (!$data['collapsed']) {
				unset($collapsed->$data['name']);
			} else {
				$collapsed->$data['name'] = 1;
			}
			
			$settings->set('collapsed', json_encode($collapsed));
			
		}
		
		return false;
	}

	public function importAction()
	{
		$file = $this->getFile('file');

		if (!$file || $file['error'] > 0) {
			$this->redirect('settings');
		}

		$subscriptions = simplexml_load_file($file['tmp_name']);
		$this->processOpml($subscriptions->body);
		$this->updateFeeds();

		return $this->redirect('');
	}

	protected function processOpml($xml)
	{
		$title = (string) $xml->attributes()->title;

		foreach ($xml->outline as $outline) {

			$type = (string) $outline->attributes()->type;

			if ($type == 'rss') {

				$feedsModel = $this->getServiceManager()->get('feeds');

				$result = $feedsModel->insert(
					(string) $outline->attributes()->title,
					(string) $outline->attributes()->xmlUrl,
					(string) $outline->attributes()->htmlUrl
				);

				if ($result && $title) {
					$tagsModel = $this->getServiceManager()->get('tags');
					$tagsModel->insert(
						$title,
						$feedsModel->lastInsertId()
					);
				}

			} else {

				$this->processOpml($outline);

			}

		}
	}

	protected function updateFeeds()
	{
		$feedsModel   = $this->getServiceManager()->get('feeds');
		$entriesModel = $this->getServiceManager()->get('entries');

		$updater = new Updater(
			$feedsModel,
			$entriesModel
		);

		$updater->update(1000);
	}

}