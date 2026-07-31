<?php

require_once __DIR__ . '/ScadenzeTestHelpers.php';

use Carbon\Carbon;
use Mockery\MockInterface;
use Modules\Fatture\Gestori\Scadenze;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class ScadenzeDateTest extends PHPUnit\Framework\TestCase
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

    public function test30ggDffmLuglio28()
    {
        $fattura = $this->getFatturaConRate('2025-07-28', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $this->mockGestoreMocks($gestore);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertNotEquals('2025-07-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals('2025-08-31', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function test30ggDffmLuglio28ConRiprogrammazioneAgosto()
    {
        $fattura = $this->getFatturaConRate('2025-07-28', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => -1, 'num_giorni' => 30, 'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestoreConRiprogrammazione($fattura, 8, 10);
        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertNotEquals('2025-07-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals('2025-09-10', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }
}