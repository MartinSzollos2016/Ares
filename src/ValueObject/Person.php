<?php

declare(strict_types = 1);

namespace Defr\ValueObject;

use DateTime;
use DateTimeInterface;

final class Person
{

	/** @var string */
	private $name;

	/** @var \DateTimeInterface */
	private $birthday;

	/** @var string */
	private $address;

	public function __construct(string $name, DateTimeInterface $birthday, string $address)
	{
		$this->name = $name;
		$this->birthday = $birthday;
		$this->address = $address;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBirthday(): DateTime
	{
		return $this->birthday;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

}
