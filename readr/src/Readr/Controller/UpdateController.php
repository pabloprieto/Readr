<?php
/**
 * Readr
 *
 * @link    http://github.com/pabloprieto/Readr
 * @author  Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr\Controller;

use Readr\Updater;

class UpdateController extends AbstractController
{

	public function indexAction()
	{
		$this->insertNewEntries();
		$this->deleteOldEntries();

		return "Done." . PHP_EOL;
	}
	
	protected function insertNewEntries()
	{
		$feedsModel   = $this->getServiceManager()->get('feeds');
		$entriesModel = $this->getServiceManager()->get('entries');

		$updater = new Updater(
			$feedsModel,
			$entriesModel
		);
		
		$updater->update(1000);
	}
	
	protected function deleteOldEntries()
	{
		$settings = $this->getServiceManager()->get('settings');
		$deleteAfter = intval($settings->get('deleteAfter', 0));
		
		if ($deleteAfter > 0) {
			$timestamp = time() - $deleteAfter * 86400;
			
			$entriesModel = $this->getServiceManager()->get('entries');
			$entriesModel->deleteAll($timestamp);
		}
	}

}