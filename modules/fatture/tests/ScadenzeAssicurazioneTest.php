<?php

require_once __DIR__ . '/ScadenzeTestHelpers.inc';

use Carbon\Carbon;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class ScadenzeAssicurazioneTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function testAssicurazioneCrediti()
    {
        $assicurazione = $this->mockModel('Plugins\AssicurazioneCrediti\AssicurazioneCrediti', [
            'id_anagrafica' => 'test-an-1',
            'data_inizio' => Carbon::parse('2025-01-01'),
            'data_fine' => Carbon::parse('2025-12-31'),
        ]);
        $assicurazione->shouldReceive('fixTotale')->andReturn(null);

        $scadenza_esistente = $this->mockModel(Scadenza::class, [
            'id_anagrafica' => 'test-an-1',
            'scadenza' => Carbon::parse('2025-03-30'),
            'da_pagare' => 500,
            'pagato' => 0,
        ]);

        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ], 'test-an-1', collect([$scadenza_esistente]));

        $gestore = $this->getGestore($fattura);
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(collect([$assicurazione]));
        $gestore->shouldReceive('trovaAssicurazioneCreditiConScadenze')->andReturn(null);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }
}