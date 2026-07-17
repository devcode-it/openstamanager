<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Scadenze;
use Modules\Fatture\Tipo;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

trait ScadenzeTestHelpers
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    protected function mockModel($class, $attributes = null)
    {
        $ref = Mockery::mock($class)->shouldAllowMockingProtectedMethods()->makePartial();

        $ref->shouldReceive('save')->andReturn(null);
        $ref->shouldReceive('delete')->andReturn(null);
        $ref->shouldReceive('getDateFormat')->andReturn('Y-m-d');

        $ref->shouldReceive('getMutatedAttributes')->andReturnUsing(function () use ($class) {
            preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

            $ref = $matches[1];

            return collect($ref)
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

    protected function getFatturaConRate($data, $netto, $rate, $idAnagrafica = 'test-an-1', $scadenze = null): Fattura
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
            'id_anagrafica' => $idAnagrafica,
            'data' => $data,
            'id' => 'test-1',
            'netto' => $netto,
            'scadenze' => $scadenze ?: collect([]),
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

        return $gestore;
    }

    protected function mockGestoreMocks($gestore): void
    {
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCreditiConScadenze')->andReturn(null);
    }
}