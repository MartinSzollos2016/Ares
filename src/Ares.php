<?php

declare(strict_types = 1);

namespace Defr;

use Defr\Ares\AresRecord;
use Defr\Ares\AresRecords;
use Defr\Ares\TaxRecord;

/**
 * Class Ares.
 */
class Ares
{

	const URL_BAS = 'https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=%s';
	const URL_RES = 'https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_res.cgi?ICO=%s';
	const URL_TAX = 'https://wwwinfo.mfcr.cz/cgi-bin/ares/ares_es.cgi?ico=%s&filtr=0';
	const URL_FIND = 'https://wwwinfo.mfcr.cz/cgi-bin/ares/ares_es.cgi?obch_jm=%s&obec=%s&filtr=0';

	/** @var string */
	private $cacheStrategy = 'YW';

	/** @var string */
	private $cacheDir = null;

	/** @var bool */
	private $debug;

	/** @var string */
	private $balancer = null;

	/** @var array */
	private $contextOptions = [
		'ssl' => [
			'verify_peer' => false,
			'verify_peer_name' => false,
		],
	];

	/** @var string */
	private $lastUrl;

	/**
	 * @param null $cacheDir
	 * @param bool $debug
	 */
	public function __construct($cacheDir = null, bool $debug = false, $balancer = null)
	{
		if ($cacheDir === null) {
			$cacheDir = sys_get_temp_dir();
		}

		if ($balancer !== null) {
			$this->balancer = $balancer;
		}

		$this->cacheDir = $cacheDir . '/defr/ares';
		$this->debug = $debug;

		// Create cache dirs if they doesn't exist
		if (!is_dir($this->cacheDir)) {
			mkdir($this->cacheDir, 0777, true);
		}
	}

	/**
	 * @param string $balancer
	 *
	 * @return $this
	 */
	public function setBalancer(string $balancer)
	{
		$this->balancer = $balancer;

		return $this;
	}

	private function wrapUrl(string $url): string
	{
		if ($this->balancer) {
			$url = sprintf('%s?url=%s', $this->balancer, urlencode($url));
		}

		$this->lastUrl = $url;

		return $url;
	}

	public function getLastUrl(): string
	{
		return $this->lastUrl;
	}

	/**
	 * @param int|string $id
	 *
	 * @return AresRecord
	 * @throws Ares\AresException
	 */
	public function findByIdentificationNumber($id): AresRecord
	{
		$id = Lib::toInteger($id);
		$this->ensureIdIsInteger($id);

		if (empty($id)) {
			throw new \Defr\Ares\AresException('IČ firmy musí být zadáno.');
		}

		$cachedFileName = $id . '_' . date($this->cacheStrategy) . '.php';
		$cachedFile = $this->cacheDir . '/bas_' . $cachedFileName;
		$cachedRawFile = $this->cacheDir . '/bas_raw_' . $cachedFileName;

		if (is_file($cachedFile)) {
			return unserialize(file_get_contents($cachedFile));
		}

		// Sestaveni URL
		$url = $this->wrapUrl(sprintf(self::URL_BAS, $id));

		try {
			$aresRequest = file_get_contents($url, null, stream_context_create($this->contextOptions));
			if ($this->debug) {
				file_put_contents($cachedRawFile, $aresRequest);
			}
			$aresResponse = simplexml_load_string($aresRequest);

			if ($aresResponse) {
				$ns = $aresResponse->getDocNamespaces();
				$data = $aresResponse->children($ns['are']);
				$elements = $data->children($ns['D'])->VBAS;

				$ico = (int) $elements->ICO;
				if ($ico !== $id) {
					throw new \Defr\Ares\AresException('IČ firmy nebylo nalezeno.');
				}

				$record = new AresRecord();

				$record->setCompanyId((int) $elements->ICO);
				$record->setTaxId((string) $elements->DIC);
				$record->setCompanyName((string) $elements->OF);
				$record->setStreet((string) $elements->AA->NU);

				if ((string) $elements->AA->CO) {
					$record->setStreetHouseNumber((string) $elements->AA->CD);
					$record->setStreetOrientationNumber((string) $elements->AA->CO);
				} else {
					$record->setStreetHouseNumber((string) $elements->AA->CD);
				}

				if ((string) $elements->AA->N === 'Praha') { //Praha
					$record->setTown($elements->AA->NMC . ' - ' . $elements->AA->NCO);
				} elseif ((string) $elements->AA->NCO !== (string) $elements->AA->N) { //Ostrava
					$record->setTown($elements->AA->N . ' - ' . $elements->AA->NCO);
				} else {
					$record->setTown((string) $elements->AA->N);
				}

				$record->setZip((string) $elements->AA->PSC);
			} else {
				throw new \Defr\Ares\AresException('Databáze ARES není dostupná.');
			}
		} catch (\Throwable $e) {
			throw new \Defr\Ares\AresException($e->getMessage());
		}

		file_put_contents($cachedFile, serialize($record));

		return $record;
	}

	public function findInResById($id): AresRecord
	{
		$id = Lib::toInteger($id);
		$this->ensureIdIsInteger($id);

		// Sestaveni URL
		$url = $this->wrapUrl(sprintf(self::URL_RES, $id));

		$cachedFileName = $id . '_' . date($this->cacheStrategy) . '.php';
		$cachedFile = $this->cacheDir . '/res_' . $cachedFileName;
		$cachedRawFile = $this->cacheDir . '/res_raw_' . $cachedFileName;

		if (is_file($cachedFile)) {
			return unserialize(file_get_contents($cachedFile));
		}

		try {
			$aresRequest = file_get_contents($url, null, stream_context_create($this->contextOptions));
			if ($this->debug) {
				file_put_contents($cachedRawFile, $aresRequest);
			}
			$aresResponse = simplexml_load_string($aresRequest);

			if ($aresResponse) {
				$ns = $aresResponse->getDocNamespaces();
				$data = $aresResponse->children($ns['are']);
				$elements = $data->children($ns['D'])->Vypis_RES;

				if (strval($elements->ZAU->ICO) === $id) {
					$taxRecord = $this->findVatById($id);
					$record = new AresRecord();
					$record->setCompanyId($id);
					$record->setTaxId($taxRecord->getTaxId());
					$record->setCompanyName(strval($elements->ZAU->OF));
					$record->setStreet(strval($elements->SI->NU));
					$record->setStreetHouseNumber(strval($elements->SI->CD));
					$record->setStreetOrientationNumber(strval($elements->SI->CO));
					$record->setTown(strval($elements->SI->N));
					$record->setZip(strval($elements->SI->PSC));
				} else {
					throw new \Defr\Ares\AresException('IČ firmy nebylo nalezeno.');
				}
			} else {
				throw new \Defr\Ares\AresException('Databáze ARES není dostupná.');
			}
		} catch (\Throwable $e) {
			throw new \Defr\Ares\AresException($e->getMessage());
		}
		file_put_contents($cachedFile, serialize($record));

		return $record;
	}

	public function findVatById($id): TaxRecord
	{
		$id = Lib::toInteger($id);

		$this->ensureIdIsInteger($id);

		// Sestaveni URL
		$url = $this->wrapUrl(sprintf(self::URL_TAX, $id));

		$cachedFileName = $id . '_' . date($this->cacheStrategy) . '.php';
		$cachedFile = $this->cacheDir . '/tax_' . $cachedFileName;
		$cachedRawFile = $this->cacheDir . '/tax_raw_' . $cachedFileName;

		if (is_file($cachedFile)) {
			return unserialize(file_get_contents($cachedFile));
		}

		try {
			$vatRequest = file_get_contents($url, null, stream_context_create($this->contextOptions));
			if ($this->debug) {
				file_put_contents($cachedRawFile, $vatRequest);
			}
			$vatResponse = simplexml_load_string($vatRequest);

			if ($vatResponse) {
				$record = new TaxRecord();
				$ns = $vatResponse->getDocNamespaces();
				$data = $vatResponse->children($ns['are']);
				$elements = $data->children($ns['dtt'])->V->S;

				if ((int) $elements->ico === $id) {
					$record->setTaxId(str_replace('dic=', 'CZ', (string) $elements->p_dph));
				} else {
					throw new \Defr\Ares\AresException('DIČ firmy nebylo nalezeno.');
				}
			} else {
				throw new \Defr\Ares\AresException('Databáze MFČR není dostupná.');
			}
		} catch (\Throwable $e) {
			throw new \Exception($e->getMessage());
		}
		file_put_contents($cachedFile, serialize($record));

		return $record;
	}

	/**
	 * @param $name
	 * @param null $city
	 *
	 * @return array|\Defr\Ares\AresRecord[]|\Defr\Ares\AresRecords
	 */
	public function findByName($name, $city = null)
	{
		if (strlen($name) < 3) {
			throw new \InvalidArgumentException('Zadejte minimálně 3 znaky pro hledání.');
		}

		$url = $this->wrapUrl(sprintf(
			self::URL_FIND,
			urlencode(Lib::stripDiacritics($name)),
			urlencode(Lib::stripDiacritics($city))
		));

		$cachedFileName = date($this->cacheStrategy) . '_' . md5($name . $city) . '.php';
		$cachedFile = $this->cacheDir . '/find_' . $cachedFileName;
		$cachedRawFile = $this->cacheDir . '/find_raw_' . $cachedFileName;

		if (is_file($cachedFile)) {
			return unserialize(file_get_contents($cachedFile));
		}

		$aresRequest = file_get_contents($url, null, stream_context_create($this->contextOptions));
		if ($this->debug) {
			file_put_contents($cachedRawFile, $aresRequest);
		}
		$aresResponse = simplexml_load_string($aresRequest);
		if (!$aresResponse) {
			throw new \Defr\Ares\AresException('Databáze ARES není dostupná.');
		}

		$ns = $aresResponse->getDocNamespaces();
		$data = $aresResponse->children($ns['are']);
		$elements = $data->children($ns['dtt'])->V->S;

		if (!count($elements)) {
			throw new \Defr\Ares\AresException('Nic nebylo nalezeno.');
		}

		$records = new AresRecords();
		foreach ($elements as $element) {
			$record = new AresRecord();
			$record->setCompanyId((int) $element->ico);
			$record->setTaxId(
				($element->dph ? str_replace('dic=', 'CZ', (string)$element->p_dph) : '')
			);
			$record->setCompanyName((string)$element->ojm);
			//'adresa' => strval($element->jmn));
			$records[] = $record;
		}
		file_put_contents($cachedFile, serialize($records));

		return $records;
	}

	public function setCacheStrategy(string $cacheStrategy): void
	{
		$this->cacheStrategy = $cacheStrategy;
	}

	public function setDebug(bool $debug): void
	{
		$this->debug = $debug;
	}

	private function ensureIdIsInteger(int $id): void
	{
		if (!is_int($id)) {
			throw new \InvalidArgumentException('IČ firmy musí být číslo.');
		}
	}

}
