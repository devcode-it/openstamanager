<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Modules\ContrattiFornitori;

use Modules\ContrattiFornitori\ContrattoFornitoreService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class ContrattoFornitoreServiceTest extends TestCase
{
    private ContrattoFornitoreService $service;

    protected function setUp(): void
    {
        $reflection = new ReflectionClass(ContrattoFornitoreService::class);
        $this->service = $reflection->newInstanceWithoutConstructor();
    }

    public function testNormalizzaImportiItalianiENormalizzati(): void
    {
        self::assertSame(2000.50, $this->service->normalizeAmount('2.000,50'));
        self::assertSame(2000.50, $this->service->normalizeAmount('2000,50'));
        self::assertSame(2000.50, $this->service->normalizeAmount('2000.50'));
        self::assertSame(2000.00, $this->service->normalizeAmount('2000'));
    }

    public function testRifiutaImportoNegativo(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->normalizeAmount('-1');
    }

    public function testCalcoloScadenzaFineMese(): void
    {
        self::assertSame(
            '2026-02-28',
            $this->service->calculateExpiry('2026-01-31', 1, 'months')
        );

        self::assertSame(
            '2028-02-29',
            $this->service->calculateExpiry('2028-01-31', 1, 'months')
        );
    }

    public function testCalcoloScadenzaGiorni(): void
    {
        self::assertSame(
            '2026-01-30',
            $this->service->calculateExpiry('2026-01-01', 30, 'days')
        );
    }

    public function testCalcoloTermineDisdetta(): void
    {
        self::assertSame(
            '2026-08-26',
            $this->service->calculateCancellationDeadline('2026-09-25', 30)
        );
    }
}
