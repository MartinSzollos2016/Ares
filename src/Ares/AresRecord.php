<?php

declare(strict_types = 1);

namespace Defr\Ares;

use Defr\Justice;
use Goutte\Client as GouteClient;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Class AresRecord.
 */
class AresRecord
{

	/** @var int */
	private $companyId;

	/** @var string */
	private $taxId;

	/** @var string */
	private $companyName;

	/** @var string */
	private $street;

	/** @var string */
	private $streetHouseNumber;

	/** @var string */
	private $streetOrientationNumber;

	/** @var string */
	private $town;

	/** @var string */
	private $zip;

	/** @var null|\Goutte\Client */
	protected $client;

	/**
	 * AresRecord constructor.
	 *
	 * @param null $companyId
	 * @param null $taxId
	 * @param null $companyName
	 * @param null $street
	 * @param null $streetHouseNumber
	 * @param null $streetOrientationNumber
	 * @param null $town
	 * @param null $zip
	 */
	public function __construct(
		$companyId = null,
		$taxId = null,
		$companyName = null,
		$street = null,
		$streetHouseNumber = null,
		$streetOrientationNumber = null,
		$town = null,
		$zip = null
	)
	{
		$this->companyId = $companyId;
		$this->taxId = !empty($taxId) ? $taxId : null;
		$this->companyName = $companyName;
		$this->street = $street;
		$this->streetHouseNumber = !empty($streetHouseNumber) ? $streetHouseNumber : null;
		$this->streetOrientationNumber = !empty($streetOrientationNumber) ? $streetOrientationNumber : null;
		$this->town = $town;
		$this->zip = $zip;
	}

	public function getStreetWithNumbers(): string
	{
		return $this->street . ' '
			. ($this->streetOrientationNumber
				?
				$this->streetHouseNumber . '/' . $this->streetOrientationNumber
				:
				$this->streetHouseNumber);
	}

	public function __toString(): string
	{
		return (string) $this->companyName;
	}

	/**
	 * @return mixed
	 */
	public function getCompanyId()
	{
		return $this->companyId;
	}

	/**
	 * @return mixed
	 */
	public function getTaxId()
	{
		return $this->taxId;
	}

	/**
	 * @return mixed
	 */
	public function getCompanyName()
	{
		return $this->companyName;
	}

	/**
	 * @return mixed
	 */
	public function getStreet()
	{
		return $this->street;
	}

	/**
	 * @return mixed
	 */
	public function getStreetHouseNumber()
	{
		return $this->streetHouseNumber;
	}

	/**
	 * @return mixed
	 */
	public function getStreetOrientationNumber()
	{
		return $this->streetOrientationNumber;
	}

	/**
	 * @return mixed
	 */
	public function getTown()
	{
		return $this->town;
	}

	/**
	 * @return mixed
	 */
	public function getZip()
	{
		return $this->zip;
	}

	/**
	 * @param \Goutte\Client $client
	 *
	 * @return $this
	 */
	public function setClient(GouteClient $client)
	{
		$this->client = $client;

		return $this;
	}

	public function getClient(): GouteClient
	{
		if (!$this->client) {
			$this->client = new GouteClient();
			$this->client->setClient(new GuzzleClient(['verify' => false]));
		}

		return $this->client;
	}

	/**
	 * @return array|\Defr\ValueObject\Person[]
	 */
	public function getCompanyPeople(): array
	{
		$client = $this->getClient();
		$justice = new Justice($client);
		$justiceRecord = $justice->findById($this->companyId);
		if ($justiceRecord) {
			return $justiceRecord->getPeople();
		}

		return [];
	}

	public function setCompanyId(int $companyId): void
	{
		$this->companyId = $companyId;
	}

	public function setTaxId(string $taxId): void
	{
		$this->taxId = $taxId;
	}

	public function setCompanyName(string $companyName): void
	{
		$this->companyName = $companyName;
	}

	public function setStreet(string $street): void
	{
		$this->street = $street;
	}

	public function setStreetHouseNumber(string $streetHouseNumber): void
	{
		$this->streetHouseNumber = $streetHouseNumber;
	}

	public function setStreetOrientationNumber(string $streetOrientationNumber): void
	{
		$this->streetOrientationNumber = $streetOrientationNumber;
	}

	public function setTown(string $town): void
	{
		$this->town = $town;
	}

	public function setZip(string $zip): void
	{
		$this->zip = $zip;
	}

}
