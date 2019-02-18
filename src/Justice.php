<?php

declare(strict_types = 1);

namespace Defr;

use Assert\Assertion;
use Defr\Justice\JusticeRecord;
use Defr\Parser\JusticeJednatelPersonParser;
use Defr\Parser\JusticeSpolecnikPersonParser;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

final class Justice
{

	const URL_BASE = 'https://or.justice.cz/ias/ui/';

	const URL_SUBJECTS = 'https://or.justice.cz/ias/ui/rejstrik-$firma?ico=%d';

	/** @var \Goutte\Client */
	private $client;

	/**
	 * Justice constructor.
	 *
	 * @param \Goutte\Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @param int $id
	 *
	 * @return \Defr\Justice\JusticeRecord|false
	 */
	public function findById(int $id)
	{
		Assertion::integer($id);

		$crawler = $this->client->request('GET', sprintf(self::URL_SUBJECTS, $id));
		$detailUrl = $this->extractDetailUrlFromCrawler($crawler);

		if ($detailUrl === false) {
			return false;
		}

		$people = [];

		$crawler = $this->client->request('GET', $detailUrl);
		$crawler->filter('.aunp-content .div-table')->each(function (Crawler $table) use (&$people): void {
			$title = $table->filter('.vr-hlavicka')->text();

			try {
				if ($title === 'jednatel: ') {
					$person = JusticeJednatelPersonParser::parseFromDomCrawler($table);
					$people[$person->getName()] = $person;
				} elseif ($title === 'Společník: ') {
					$person = JusticeSpolecnikPersonParser::parseFromDomCrawler($table);
					$people[$person->getName()] = $person;
				}
			} catch (\Throwable $e) {
				throw $e;
			}
		});

		return new JusticeRecord($people);
	}

	/**
	 * @param \Symfony\Component\DomCrawler\Crawler $crawler
	 *
	 * @return false|string
	 */
	private function extractDetailUrlFromCrawler(Crawler $crawler)
	{
		$linksFound = $crawler->filter('.result-links > li > a');
		if (!$linksFound) {
			return false;
		}

		$href = $linksFound->extract(['href']);
		if (!isset($href[1])) {
			return false;
		}

		return self::URL_BASE . $href[1];
	}

}
