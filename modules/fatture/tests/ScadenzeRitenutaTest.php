<?php

require_once __DIR__ . '/ScadenzeTestHelpers.php';

use Carbon\Carbon;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Scadenze;
use Modules\Fatture\Tipo;
use Modules\Pagamenti\Pagamento;
use Modules\Scadenzario\Scadenza;

class ScadenzeRitenutaTest extends PHPUnit\Framework\TestCase
{
    use ScadenzeTestHelpers;

    public function testRitenutaAcconto()
    {
        $netto = 1000;
        $ritenuta = 200;

        $tipo = $this->mockModel(Tipo::class);
        $tipo->shouldReceive('getAttribute')->with('dir')->andReturn('uscita');
        $tipo->shouldReceive('getTranslation')->andReturn('Fattura di acquisto');

        $pagamento = $this->mockModel(Pagamento::class);
        $pagamento->shouldReceive('trovaRate')->andReturn([
            $this->mockModel(Pagamento::class, [
                'giorno' => 0, 'num_giorni' => 90, 'prc' => 100,
            ]),
        ]);

        $scadenza_esistente = $this->mockModel(Scadenza::class, [
            'scadenza' => Carbon::parse('2025-06-28'),
        ]);
        $relationMock = Mockery::mock('Illuminate\Database\Eloquent\Relations\HasMany');
        $relationMock->shouldReceive('orderBy')->with('scadenza', 'desc')->andReturn($relationMock);
        $relationMock->shouldReceive('first')->andReturn($scadenza_esistente);

        $fattura = $this->mockModel(Fattura::class, [
            'numero_esterno' => '2025-01',
            'idpagamento' => 'test-pag-1',
            'id_banca_controparte' => null,
            'id_banca_azienda' => null,
            'id_anagrafica' => 'test-an-1',
            'data' => '2025-03-30',
            'id' => 'test-1',
            'netto' => $netto,
            'scadenze' => collect([$scadenza_esistente]),
            'pagamento' => $pagamento,
            'tipo' => $tipo,
            'ritenuta_acconto' => $ritenuta,
            'is_ritenuta_pagata' => false,
            'sconto_finale_percentuale' => null,
        ]);
        $fattura->shouldReceive('associate')->andReturn(null);
        $fattura->shouldReceive('isNota')->andReturn(false);
        $fattura->shouldReceive('scadenze')->andReturn($relationMock);

        $database = $this->mockModel(Database::class);
        $database->shouldReceive('delete')->andReturn(null);
        $database->shouldReceive('selectOne')->andReturn(null);

        $gestore = Mockery::mock(Scadenze::class, [$fattura, $database])->shouldAllowMockingProtectedMethods()->makePartial();
        $gestore->shouldReceive('generaScadenza')->andReturnUsing(function ($id_anagrafica, $descrizione, $importo, $data_scadenza, $id_pagamento, $id_banca_azienda, $id_banca_controparte, $type) {
            return $this->mockModel(Scadenza::class, [
                'id_anagrafica' => $id_anagrafica,
                'descrizione' => $descrizione,
                'scadenza' => Carbon::create($data_scadenza),
                'da_pagare' => $importo,
                'tipo' => $type,
                'pagato' => 0,
            ]);
        });
        $gestore->shouldReceive('trovaPagamento')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCrediti')->andReturn(null);
        $gestore->shouldReceive('trovaAssicurazioneCreditiConScadenze')->andReturn(null);

        $scadenze = $gestore->registra(false, true);

        $this->assertCount(2, $scadenze);
        $this->assertEquals('2025-06-28', $scadenze[0]->scadenza->format('Y-m-d'));
        $this->assertEquals(-$netto, $scadenze[0]->da_pagare);
        $this->assertEquals('2025-07-15', $scadenze[1]->scadenza->format('Y-m-d'));
        $this->assertEquals(-$ritenuta, $scadenze[1]->da_pagare);
        $this->assertEquals('ritenuta_acconto', $scadenze[1]->tipo);
    }
}