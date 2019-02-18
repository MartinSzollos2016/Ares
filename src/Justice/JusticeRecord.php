<?php

declare(strict_types = 1);

namespace Defr\Justice;

final class JusticeRecord
{

	/** @var array|\Defr\ValueObject\Person[] */
	private $people;

	public function __construct(array $people)
	{
		$this->people = $people;
	}

	/**
	 * @return array|\Defr\ValueObject\Person[]
	 */
	public function getPeople(): array
	{
		return $this->people;
	}

}
