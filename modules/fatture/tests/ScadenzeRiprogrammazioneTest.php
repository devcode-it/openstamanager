<?php

require_once __DIR__ . '/ScadenzeTestHelpers.php';

use Carbon\Carbon;
use Mockery\MockInterface;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Scadenze;
use Modules\Fatture\Tipo;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class ScadenzeRiprogrammazioneTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    protected function getGestoreConRiprogrammazione($fattura, $mese_chiusura, $giorno_fisso): Scadenze&MockInterface
    {
        $database = $this->mockModel(Database::class);
        $database->shouldReceive('delete')->andReturn(null);
        $database->shouldReceive('selectOne')->andReturnUsing(function ($table, $fields, $conditions) use ($mese_chiusura, $giorno_fisso) {
            if ($table === 'an_pagamenti_anagrafiche' && $conditions['mese'] == str_pad($mese_chiusura, 2, '0', STR_PAD_LEFT)) {
                return ['id' => 1, 'mese' => $mese_chiusura, 'giorno_fisso' => $giorno_fisso, 'id_anagrafica' => $conditions['id_anagrafica']];
            }

            return null;
        });

        $gestore = Mockery::mock(Scadenze::class, [$fattura, $database])->shouldAllowMockingProtectedMethods()->makePartial();
        $gestore->shouldReceive('generaScadenza')->andReturnUsing(function ($id_anagrafica, $descrizione, $importo, $data_scadenza, $id_pagamento, $id_banca_azienda, $id_banca_controparte, $type, $is_pagato) {
            $scadenza = $this->mockModel(Scadenza::class, [
                'id_anagrafica' => $id_anagrafica,
                'descrizione' => $descrizione,
                'scadenza' => Carbon::create($data_scadenza),
                'da_pagare' => $importo,
                'tipo' => $type,
                'id_pagamento' => $id_pagamento,
                'id_banca_azienda' => $id_banca_azienda,
                'id_banca_controparte' => $id_banca_controparte,
                'pagato' => $is_pagato ? $importo : 0,
                'data_pagamento' => $is_pagato ? $data_scadenza : null,
            ]);

            return $scadenza;
        });
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCreditiConScadenze')->andReturn(null);

        return $gestore;
    }

    public function testRiprogrammazioneAgostoConGiorno10()
    {
        $fattura = $this->getFatturaConRate('2025-07-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-09-10', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testNessunaRiprogrammazioneSeFuoriMeseChiusura()
    {
        $fattura = $this->getFatturaConRate('2025-08-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-09-14', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRiprogrammazioneConGiornoFissoPagamento()
    {
        $fattura = $this->getFatturaConRate('2025-07-01', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 15, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-09-10', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRiprogrammazioneConUltimoDelMese()
    {
        $fattura = $this->getFatturaConRate('2025-07-01', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-09-10', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRiprogrammazioneMultiRate()
    {
        $fattura = $this->getFatturaConRate('2025-07-01', 600, [
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 0, 'prc' => 50]),
            $this->mockModel(Pagamento::class, ['giorno' => 0, 'num_giorni' => 31, 'prc' => 50]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(2, $scadenze);
        $this->assertEquals('2025-07-01', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(300, $scadenze[0]->da_pagare);
        $this->assertEquals('2025-09-10', $scadenze[1]->scadenza->format('Y-m-d'));
        $this->assertEquals(300, $scadenze[1]->da_pagare);
    }

    public function testRiprogrammazioneDicembre()
    {
        $fattura = $this->getFatturaConRate('2025-11-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 12, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2026-01-10', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRiprogrammazioneGiornoFisso31Overflow()
    {
        $fattura = $this->getFatturaConRate('2025-07-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 31);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-10-01', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRiprogrammazioneMeseDiversoNonApplicata()
    {
        $fattura = $this->getFatturaConRate('2025-07-15', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 9, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-08-14', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }
}