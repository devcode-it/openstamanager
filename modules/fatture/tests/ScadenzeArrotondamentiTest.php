<?php

require_once __DIR__ . '/ScadenzeTestHelpers.inc';

use Modules\Pagamenti\Pagamento;

class ScadenzeArrotondamentiTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function test3rate100euro()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 100, [
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 30, 'prc' => 33]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 60, 'prc' => 33]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 90, 'prc' => 34]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(3, $scadenze);
        $somma = array_sum(array_map(fn($s) => $s->da_pagare, $scadenze));
        $this->assertEquals(100, $somma);
    }

    public function test4rate99e99()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 99.99, [
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 30, 'prc' => 25]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 60, 'prc' => 25]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 90, 'prc' => 25]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 120, 'prc' => 25]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(4, $scadenze);
        $somma = array_sum(array_map(fn($s) => $s->da_pagare, $scadenze));
        $this->assertEquals(99.99, $somma);
    }
}