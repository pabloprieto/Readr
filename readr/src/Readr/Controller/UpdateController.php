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
		$feedsModel   = $this->getServiceManager()->get('feeds');
		$entriesModel = $this->getServiceManager()->get('entries');
	
		$updater = new Updater(
			$feedsModel,
			$entriesModel
		);
		
		$updater->update(1000);

		return "Done." . PHP_EOL;
	}

}