<?php

namespace Readr\Controller;

use Readr\Updater;

class UpdateController extends AbstractController
{

	public function indexAction()
	{
		$limit = (int) $this->getParam('limit', 500);
		
		$updater = new Updater(
			$this->getFeedsModel(),
			$this->getEntriesModel()
		);
		
		$updater->update($limit);

		echo "Done." . PHP_EOL;
		exit;
	}

}