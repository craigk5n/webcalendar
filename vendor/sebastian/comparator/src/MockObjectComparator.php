<?php declare(strict_types=1);
/*
 * This file is part of sebastian/comparator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Comparator;

use function assert;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Compares PHPUnit\Framework\MockObject\MockObject instances for equality.
 */
final class MockObjectComparator extends ObjectComparator
{
    /**
     * Returns whether the comparator can compare two values.
     *
     * @param mixed $expected The first value to compare
     * @param mixed $actual   The second value to compare
     */
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof MockObject && $actual instanceof MockObject;
    }

    protected function toArray(object $object): array
    {
        assert($object instanceof MockObject);

        $array = parent::toArray($object);

        unset($array['__phpunit_invocationMocker']);

        return $array;
    }
}
