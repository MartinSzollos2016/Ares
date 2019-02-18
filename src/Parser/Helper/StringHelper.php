<?php

declare(strict_types = 1);

namespace Defr\Parser\Helper;

final class StringHelper
{

	public static function removeEmptyLines(string $text): string
	{
		return preg_replace('/^[ \t]*[\r\n]+/m', '', $text);
	}

}
