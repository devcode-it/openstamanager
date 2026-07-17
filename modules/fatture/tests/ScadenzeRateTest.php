<?php

require_once __DIR__ . '/ScadenzeTestHelpers.php';

use Modules\Pagamenti\Pagamento;

class ScadenzeRateTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function testRimessaDiretta90Giorni()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRimessaDiretta90GiorniAl15()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 15, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-06-15', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function test3RateStatiche()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 600, [
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 90, 'prc' => 33]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 180, 'prc' => 33]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 270, 'prc' => 34]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(3, $scadenze);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(198, $scadenze[0]->da_pagare);
        $this->assertEquals('2025-09-26', $scadenze[1]->scadenza->format('Y-m-d'));
        $this->assertEquals(198, $scadenze[1]->da_pagare);
        $this->assertEquals('2025-12-25', $scadenze[2]->scadenza->format('Y-m-d'));
        $this->assertEquals(204, $scadenze[2]->da_pagare);
    }
}