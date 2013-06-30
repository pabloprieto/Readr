<?php

namespace Readr\Controller;

use Readr\App;
use Readr\Updater;

class SettingsController extends AbstractController
{
	
	public function init()
	{
		$this->checkAuth();
	}
	
	public function indexAction()
	{
		$settings = $this->getServiceManager()->get('settings');
		$data     = $this->getPostData();
		$errors   = array();

		if (!empty($data)) {
			
			if ($data['username']) {
			
				if (empty($data['password'])) {
				
					$errors[] = 'Password is empty';
				
				} elseif ($data['password'] == $data['password_confirm']) {
				
					$hash = password_hash($data['password'], PASSWORD_DEFAULT);	
					
					$settings->set('username', $data['username']);
					$settings->set('password', $hash);
					
				} else {
				
					$errors[] = 'Password and confirmation do not match.';
					
				}
				
			} else {
				
				if (isset($_SESSION)) {
					unset($_SESSION['username']);
				}
				
				$settings->delete('username');
				$settings->delete('password');
				
			}
			
		}
	
		return array(
			'errors'   => $errors,
			'username' => $settings->get('username'),
			'release'  => implode('.', App::getRelease()),
			'version'  => App::getVersion()
		);
	}

	public function importAction()
	{
		$file = $this->getFile('file');
	
		if (!$file || $file['error'] > 0) {
			return $this->redirect('settings');
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

				$result = $this->getFeedsModel()->insert(
					(string) $outline->attributes()->title,
					(string) $outline->attributes()->xmlUrl,
					(string) $outline->attributes()->htmlUrl
				);

				if ($result && $title) {
					$this->getTagsModel()->insert(
						$title,
						$this->getFeedsModel()->lastInsertId()
					);
				}

			} else {

				$this->processOpml($outline);

			}

		}
	}

	protected function updateFeeds()
	{
		$updater = new Updater(
			$this->getFeedsModel(),
			$this->getEntriesModel()
		);
		
		$updater->update(1000);
	}

}