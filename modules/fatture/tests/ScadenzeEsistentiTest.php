<?php

require_once __DIR__ . '/ScadenzeTestHelpers.php';

use Carbon\Carbon;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class ScadenzeEsistentiTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function testScadenzeGiaInserite()
    {
        $scadenza_vecchia = $this->mockModel(Scadenza::class, [
            'id_anagrafica' => 'test-an-1',
            'scadenza' => Carbon::parse('2025-01-15'),
            'da_pagare' => 500,
            'pagato' => 0,
        ]);

        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ], 'test-an-1', collect([$scadenza_vecchia]));

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testScadenzeGiaInseriteParzialmentePagate()
    {
        $scadenza_parziale = $this->mockModel(Scadenza::class, [
            'id_anagrafica' => 'test-an-1',
            'scadenza' => Carbon::parse('2025-01-15'),
            'da_pagare' => 500,
            'pagato' => 200,
        ]);

        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ], 'test-an-1', collect([$scadenza_parziale]));

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }
}