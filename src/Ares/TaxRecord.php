<?php

declare(strict_types = 1);

namespace Defr\Ares;

/**
 * Class TaxRecord.
 */
class TaxRecord
{

	/** @var string|null */
	private $taxId = null;

	/**
	 * TaxRecord constructor.
	 *
	 * @param string $taxId
	 */
	public function __construct(?string $taxId = null)
	{
		$this->taxId = $taxId;
	}

	public function getTaxId(): ?string
	{
		return $this->taxId;
	}

	public function __toString(): string
	{
		return (string) $this->taxId;
	}

	public function setTaxId($taxId): void
	{
		$this->taxId = $taxId;
	}

}
