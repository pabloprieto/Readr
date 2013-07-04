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