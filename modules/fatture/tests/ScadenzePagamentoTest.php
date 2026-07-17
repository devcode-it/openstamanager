<?php

require_once __DIR__ . '/ScadenzeTestHelpers.inc';

use Carbon\Carbon;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Scadenze;
use Modules\Fatture\Tipo;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class ScadenzePagamentoTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function testPagamentoPersonalizzatoInFattura()
    {
        $tipo = $this->mockModel(Tipo::class);
        $tipo->shouldReceive('getAttribute')->with('dir')->andReturn('entrata');
        $tipo->shouldReceive('getTranslation')->andReturn('Fattura di vendita');

        $pagamentoPersonalizzato = $this->mockModel(Pagamento::class);
        $pagamentoPersonalizzato->shouldReceive('trovaRate')->andReturn([
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 60, 'prc' => 100,
            ]),
        ]);

        $pagamentoAnagrafica = $this->mockModel(Pagamento::class);
        $pagamentoAnagrafica->shouldReceive('trovaRate')->andReturn([
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ]);

        $fattura = $this->mockModel(Fattura::class, [
            'numero_esterno' => '2025-01',
            'idpagamento' => 'test-pag-1',
            'id_banca_controparte' => null,
            'id_banca_azienda' => null,
            'id_anagrafica' => 'test-an-1',
            'data' => '2025-03-30',
            'id' => 'test-1',
            'netto' => 500,
            'scadenze' => collect([]),
            'pagamento' => $pagamentoPersonalizzato,
            'tipo' => $tipo,
            'ritenuta_acconto' => null,
        ]);
        $fattura->shouldReceive('associate')->andReturn(null);
        $fattura->shouldReceive('isNota')->andReturn(false);

        $database = $this->mockModel(Database::class);
        $database->shouldReceive('delete')->andReturn(null);
        $database->shouldReceive('selectOne')->andReturn(null);

        $gestore = Mockery::mock(Scadenze::class, [$fattura, $database])->shouldAllowMockingProtectedMethods()->makePartial();
        $gestore->shouldReceive('generaScadenza')->andReturnUsing(function ($id_anagrafica, $descrizione, $importo, $data_scadenza) {
            return $this->mockModel(Scadenza::class, [
                'id_anagrafica' => $id_anagrafica,
                'scadenza' => Carbon::create($data_scadenza),
                'da_pagare' => $importo,
            ]);
        });
        $gestore->shouldReceive('trovaPagamento')->andReturn($pagamentoAnagrafica);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCreditiConScadenze')->andReturn(null);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(1, $scadenze);
        $this->assertEquals('2025-05-29', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(500, $scadenze[0]->da_pagare);
    }
}