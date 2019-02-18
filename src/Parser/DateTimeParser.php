<?php

declare(strict_types = 1);

namespace Defr\Parser;

use DateTime;
use DateTimeInterface;

final class DateTimeParser
{

	/** @var string[] */
	private static $czechToEnglishMonths = [
		'ledna' => 'January',
		'února' => 'February',
		'března' => 'March',
		'dubna' => 'April',
		'května' => 'May',
		'června' => 'June',
		'července' => 'July',
		'srpna' => 'August',
		'září' => 'September',
		'října' => 'October',
		'listopadu' => 'November',
		'prosince' => 'December',
	];

	public static function parseFromCzechDateString(string $czechDate): DateTimeInterface
	{
		$czechDate = trim($czechDate);
		$englishDate = strtr($czechDate, self::$czechToEnglishMonths);

		return new DateTime($englishDate);
	}

}
