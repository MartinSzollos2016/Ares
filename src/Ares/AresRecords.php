<?php

declare(strict_types = 1);

namespace Defr\Ares;

use ArrayIterator;

/**
 * Class AresRecords.
 */
final class AresRecords implements \ArrayAccess, \IteratorAggregate, \Countable
{

	/** @var \Defr\Ares\AresRecord[] */
	private $array = [];

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		if (isset($this->array[$offset])) {
			return true;
		}

		return false;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool|\Defr\Ares\AresRecord
	 */
	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return $this->array[$offset];
		}

		return false;
	}

	/**
	 * @param mixed $offset
	 * @param \Defr\Ares\AresRecord $value
	 */
	public function offsetSet($offset, $value): void
	{
		if ($offset) {
			$this->array[$offset] = $value;
		} else {
			$this->array[] = $value;
		}
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		unset($this->array[$offset]);
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->array);
	}

	/**
	 * {@inheritdoc}
	 */
	public function count()
	{
		return count($this->array);
	}

}
