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
use Readr\Opml;
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
			'messenger'   => $messenger,
			'username'    => $settings->get('username'),
			'emulateHTTP' => $settings->get('emulateHTTP', 0),
			'deleteAfter' => $settings->get('deleteAfter', null),
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

				$messenger->add('Password is empty', 'auth-error');

			} elseif ($data['password'] == $data['password_confirm']) {

				$hash = password_hash($data['password'], PASSWORD_DEFAULT);

				$settings->set('username', $data['username']);
				$settings->set('password', $hash);

			} else {

				$messenger->add('Password and confirmation do not match.', 'auth-error');

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
		
		$deleteAfter = intval($data['deleteAfter']);
		if ($deleteAfter > 0) {
			$settings->set('deleteAfter', $deleteAfter);
		} else {
			$settings->delete('deleteAfter');
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
		$file      = $this->getFile('file');
		$messenger = new FlashMessenger();
		
		if (!$file || $file['error'] > 0) {
			$messenger->add(sprintf('File \'%s\' has not been correctly uploaded.', $file['name']), 'import-error');
			$this->redirect('settings');
		}

		$subscriptions = @simplexml_load_file($file['tmp_name']);
		if (!$subscriptions || !$subscriptions->body) {
			$messenger->add(sprintf('\'%s\' is not a valid OPML file.', $file['name']), 'import-error');
			$this->redirect('settings');
		}
		
		$feeds   = $this->getServiceManager()->get('feeds');
		$tags    = $this->getServiceManager()->get('tags');
		$entries = $this->getServiceManager()->get('entries');
		
		$opml = new Opml();
		$opml->process($subscriptions->body, $feeds, $tags);

		$updater = new Updater(
			$feeds,
			$entries
		);

		$updater->update();

		return $this->redirect('');
	}
	
	public function exportAction()
	{
		$feeds = $this->getServiceManager()->get('feeds');
		
		$opml = new Opml();
		$xml  = $opml->create($feeds);
		
		header('Content-Type: application/xml');
		header('Content-Disposition: attachment; filename="subscriptions.xml"');
		
		return $xml;
	}

}