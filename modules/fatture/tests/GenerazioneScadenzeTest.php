<?php

use Illuminate\Support\Str;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Scadenze;
use Modules\Fatture\Tipo;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class GenerazioneScadenzeTest extends PHPUnit\Framework\TestCase
{
    public function testRimessaDiretta90Giorni()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0,
                'num_giorni' => 90,
                'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);

        $scadenze = $gestore->registra(false, true);

        $this->assertEquals(1, count($scadenze));

        $this->assertEquals('2025-03-30', $scadenze[0]->data_emissione);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza);
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function testRimessaDiretta90GiorniAl15()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 500, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 15,
                'num_giorni' => 90,
                'prc' => 100,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);

        $scadenze = $gestore->registra(false, true);

        $this->assertEquals(1, count($scadenze));

        $this->assertEquals('2025-03-30', $scadenze[0]->data_emissione);
        $this->assertEquals('2025-06-15', $scadenze[0]->scadenza);
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }

    public function test3RateStatiche()
    {
        $fattura = $this->getFatturaConRate('2025-03-30', 600, [
            $this->mockModel(Pagamento::class, [
                'giorno' => 0,
                'num_giorni' => 90,
                'prc' => 33,
            ]),
            $this->mockModel(Pagamento::class, [
                'giorno' => 0,
                'num_giorni' => 180,
                'prc' => 33,
            ]),
            $this->mockModel(Pagamento::class, [
                'giorno' => 0,
                'num_giorni' => 270,
                'prc' => 34,
            ]),
        ]);

        $gestore = $this->getGestore($fattura);
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);

        $scadenze = $gestore->registra(false, true);

        $this->assertEquals(3, count($scadenze));

        $this->assertEquals('2025-03-30', $scadenze[0]->data_emissione);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza);
        $this->assertEquals(198, $scadenze[0]->da_pagare);

        $this->assertEquals('2025-03-30', $scadenze[1]->data_emissione);
        $this->assertEquals('2025-09-26', $scadenze[1]->scadenza);
        $this->assertEquals(198, $scadenze[1]->da_pagare);

        $this->assertEquals('2025-03-30', $scadenze[2]->data_emissione);
        $this->assertEquals('2025-12-25', $scadenze[2]->scadenza);
        $this->assertEquals(204, $scadenze[2]->da_pagare);
    }

    protected function mockModel($class, $attributes = null)
    {
        $ref = Mockery::mock($class)->shouldAllowMockingProtectedMethods()->makePartial();

        $ref->shouldReceive('save')->andReturn(null);
        $ref->shouldReceive('delete')->andReturn(null);
        $ref->shouldReceive('getDateFormat')->andReturn('Y-m-d');

        // Fix per gestione attributi su modello mocked di Eloquent
        $ref->shouldReceive('getMutatedAttributes')->andReturnUsing(function () use ($class) {
            preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

            $ref = $matches[1];

            return collect($ref)
                ->merge($attributeMutatorMethods)
                ->map(function ($match) {
                    return lcfirst(static::$snakeAttributes ? Str::snake($match) : $match);
                })->all();
        });

        if (!empty($attributes)) {
            foreach ($attributes as $key => $value) {
                $ref->shouldReceive('getAttribute')->with($key)->andReturn($value);
            }
        }

        return $ref;
    }

    protected function getFatturaConRate($data, $netto, $rate): Fattura
    {
        $tipo = $this->mockModel(Tipo::class);
        $tipo->shouldReceive('getAttribute')->with('dir')->andReturn('entrata');
        $tipo->shouldReceive('getTranslation')->andReturn('Fattura di vendita');

        $pagamento = $this->mockModel(Pagamento::class);
        $pagamento->shouldReceive('trovaRate')->andReturn($rate);

        $fattura = $this->mockModel(Fattura::class, [
            'numero_esterno' => '2025-01',
            'idpagamento' => null,
            'id_banca_controparte' => null,
            'id_banca_azienda' => null,
            'idanagrafica' => 'test-an-1',
            'data' => $data,
            'id' => 'test-1',
            'netto' => $netto,
            'scadenze' => [],
            'pagamento' => $pagamento,
            'tipo' => $tipo,
            'ritenuta_acconto' => null,
        ]);
        $fattura->shouldReceive('associate')->andReturn(null);
        $fattura->shouldReceive('isNota')->andReturn(false);

        return $fattura;
    }

    protected function getGestore($fattura): Scadenze
    {
        $database = $this->mockModel(Database::class);
        $database->shouldReceive('delete')->andReturn(null);
        $database->shouldReceive('selectOne')->andReturn(null);

        $gestore = Mockery::mock(Scadenze::class, [$fattura, $database])->shouldAllowMockingProtectedMethods()->makePartial();
        $gestore->shouldReceive('generaScadenza')->andReturnUsing(function ($idanagrafica, $descrizione, $importo, $data_scadenza, $id_pagamento, $id_banca_azienda, $id_banca_controparte, $type, $is_pagato) use ($fattura) {
            $scadenza = $this->mockModel(Scadenza::class, [
                'idanagrafica' => $idanagrafica,
                'descrizione' => $descrizione,
                'scadenza' => $data_scadenza,
                'da_pagare' => $importo,
                'tipo' => $type,
                'id_pagamento' => $id_pagamento,
                'id_banca_azienda' => $id_banca_azienda,
                'id_banca_controparte' => $id_banca_controparte,

                'pagato' => $is_pagato ? $importo : 0,
                'data_pagamento' => $is_pagato ? $data_scadenza : null,
            ]);

            $scadenza->shouldReceive('documento')->andReturn($fattura);

            return $scadenza;
        });

        return $gestore;
    }
}
