<?php
/**
 * Readr
 *
 * @link    http://github.com/pabloprieto/Readr
 * @author  Pablo Prieto
 * @license http://opensource.org/licenses/GPL-3.0
 */

namespace Readr;

use SimpleXMLElement;
use Readr\Model\Feeds;
use Readr\Model\Tags;

class Opml
{

	/**
	 * @param SimpleXMLElement $xml
	 * @param Feeds $feeds
	 * @param Tags $tags
	 * @return void
	 */
	public function process(SimpleXMLElement $xml, Feeds $feeds, Tags $tags)
	{
		$title = (string) $xml->attributes()->text;

		foreach ($xml->outline as $outline) {

			$type = (string) $outline->attributes()->type;

			if ($type == 'rss') {

				$result = $feeds->insert(
					(string) $outline->attributes()->text,
					(string) $outline->attributes()->xmlUrl,
					(string) $outline->attributes()->htmlUrl
				);

				if ($result && $title) {
					$tags->insert(
						$title,
						$feeds->lastInsertId()
					);
				}

			} else {

				$this->process($outline, $feeds, $tags);

			}

		}
	}
	
	/**
	 * @param Feeds $feeds
	 * @return string
	 */
	public function create(Feeds $feeds)
	{
		$items = $feeds->fetchAll();
		$tags  = array();
		$unclassified = array();
		
		foreach ($items as $item) {
			$t = array_filter(explode(',', $item['tags']), 'strlen');
			
			if (!empty($t)) {
				foreach ($t as $tag) {
					if (!isset($tags[$tag])) $tags[$tag] = array();
					$tags[$tag][] = $item;
				}
			} else {
				$unclassified[] = $item;
			}
		}
		
		ksort($tags);
		
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<opml version="1.0">' . PHP_EOL;
		$xml .= '<head><title>Readr subscriptions</title></head>' . PHP_EOL;
		$xml .= '<body>' . PHP_EOL;
		
		foreach ($tags as $tag => $items) {
		
			$tag  = htmlspecialchars($tag);
			$xml .= sprintf('<outline text="%s">', $tag, $tag) . PHP_EOL;
			
			foreach ($items as $item) {
				$title = htmlspecialchars($item['title']);
				$xml  .= sprintf(
					'    <outline text="%s" title="%s" type="rss" xmlUrl="%s" htmlUrl="%s"/>',
					$title,
					$title,
					$item['url'],
					$item['link']
				) . PHP_EOL;
			}
			
			$xml .= '</outline>' . PHP_EOL;
			
		}
		
		foreach ($unclassified as $item) {
			
			$title = htmlspecialchars($item['title']);
			$xml  .= sprintf(
				'<outline text="%s" title="%s" type="rss" xmlUrl="%s" htmlUrl="%s"/>',
				$title,
				$title,
				$item['url'],
				$item['link']
			) . PHP_EOL;
			
		}
		
		$xml .= '</body>' . PHP_EOL;
		$xml .= '</opml>';
		
		return $xml;
	}

}