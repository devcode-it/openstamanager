<?php

declare(strict_types=1);

namespace Tests\Common;

use Common\DocumentRounding;
use PHPUnit\Framework\TestCase;

class DocumentRoundingTest extends TestCase
{
    /** @dataProvider roundingProvider */
    public function testTarget(float $value, string $mode, float $expected): void
    {
        $this->assertSame($expected, DocumentRounding::target($value, $mode));
    }

    public static function roundingProvider(): array
    {
        return [
            'nearest below half' => [100.499, 'nearest', 100.0],
            'nearest at half' => [100.500, 'nearest', 101.0],
            'nearest above half' => [100.501, 'nearest', 101.0],
            'down' => [100.999, 'down', 100.0],
            'up' => [100.001, 'up', 101.0],
            'already whole up' => [100.0, 'up', 100.0],
        ];
    }
}
