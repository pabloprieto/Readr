<?php

namespace Readr;

use SimplePie;
use Readr\Model\Entries;
use Readr\Model\Feeds;

class Updater
{

	protected $feedsModel;
	protected $entriesModel;

	public function __construct(Feeds $feedsModel, Entries $entriesModel)
	{
		$this->feedsModel   = $feedsModel;
		$this->entriesModel = $entriesModel;
	}

	public function update($limit = 1000)
	{
		@set_time_limit(600);
		@error_reporting(E_ERROR);

		$feeds = $this->feedsModel->fetchAll($limit, 0, 'last_update ASC');

		$simplePie = new SimplePie();
		$simplePie->enable_cache(false);

		foreach ($feeds as $feed) {

			$simplePie->set_feed_url($feed['url']);
			$result = $simplePie->init();

			if ($result) {

				$items = $simplePie->get_items();

				foreach ($items as $item) {
					if (!$item) continue;

					$author = $item->get_author();

					$this->entriesModel->insert(
						$feed['id'],
						$item->get_title(),
						$item->get_content(),
						$author ? $author->get_name() : null,
						$item->get_permalink(),
						$item->get_date('U')
					);
				}

			}

			$this->feedsModel->setUpdateData(
				$feed['id'], 
				time(), 
				$result ? null : $simplePie->error()
			);

		}
	}

}