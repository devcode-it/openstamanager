<?php

require_once __DIR__ . '/ScadenzeTestHelpers.inc';

use Modules\Pagamenti\Pagamento;

class ScadenzeDateTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function testUltimoDelMese()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 0, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-03-31', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testUltimoDelMeseConOffsetMensile()
    {
        $fattura = $this->getFatturaConRate('2025-01-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-02-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testUltimoDelMeseConOffsetGiorni()
    {
        $fattura = $this->getFatturaConRate('2025-01-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 15, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-01-31', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testGiorno31MeseCorto()
    {
        $fattura = $this->getFatturaConRate('2025-02-01', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 31, 'num_giorni' => 0, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-02-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testAnnoBisestileFebbraio29()
    {
        $fattura = $this->getFatturaConRate('2024-02-01', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 29, 'num_giorni' => 0, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2024-02-29', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testAnnoNonBisestileFebbraio28()
    {
        $fattura = $this->getFatturaConRate('2025-02-01', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 29, 'num_giorni' => 0, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-02-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testUltimoDelMeseFebbraioBisestile()
    {
        $fattura = $this->getFatturaConRate('2024-01-31', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2024-02-29', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testUltimoDelMeseFebbraioNonBisestile()
    {
        $fattura = $this->getFatturaConRate('2025-01-31', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-02-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }
}