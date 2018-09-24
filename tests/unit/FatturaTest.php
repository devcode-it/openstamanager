<?php

class FatturaTest extends \Codeception\Test\Unit
{
    public function testCreate()
    {
        $data = date('Y-m-d H:i:s');

        $fattura = Modules\Fatture\Fattura::create([
            'idanagrafica' => 1,
            'data' => $data,
            'id_segment' => 1,
            'tipo' => 2,
        ]);

        $this->assertEquals($fattura->idanagrafica, 1);
        $this->assertEquals($fattura->idtipodocumento, 2);
        $this->assertEquals($fattura->id_segment, 1);
        $this->assertEquals($fattura->data, $data);
    }
}
