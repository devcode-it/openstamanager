<?php

namespace Helper\Common;

use AcceptanceTester;

class RowHelper extends \Codeception\Module
{
    /** @param string */
    protected $finalPattern = "//div[@class='panel-heading' and contains(string(), 'Righe')]/parent::*//table//tr[contains(string(), '|name|')]//td[2]";

    /**
     * Undocumented function
     *
     * @param AcceptanceTester $t
     * @param [type] $descrizione
     * @param [type] $qta
     * @param [type] $prezzo
     * @param integer $sconto
     * @param string $tipo_sconto
     * @param [type] $id_iva
     * @param [type] $id_rivalsa_inps
     * @param [type] $id_ritenuta_acconto
     * @return void
     */
    public function addRow(AcceptanceTester $t, $descrizione, $qta, $prezzo, $sconto = 0, $tipo_sconto = 'UNT', $id_iva = null, $id_rivalsa_inps = null, $id_ritenuta_acconto = null)
    {
        // Apre il modal
        $t->clickAndWaitModal('Riga', '#tab_0');

        // Completa le informazioni
        $t->fillField('Descrizione', $descrizione);
        $t->fillField('Q.tà', $qta);
        $t->fillField('Costo unitario', $prezzo);

        if (!empty($sconto)) {
            $t->fillField('Sconto unitario', $sconto);

            if (in_array($tipo_sconto, ['PRC', 'UNT'])) {
                $t->select2ajax('#tipo_sconto', $tipo_sconto);
            }
        }

        if ($id_iva) {
            $t->select2('#idiva', $id_iva);
        }

        if ($id_rivalsa_inps) {
            $t->select2('#id_rivalsa_inps', $id_rivalsa_inps);
        }
        if ($id_ritenuta_acconto) {
            $t->select2('#id_ritenuta_acconto', $id_ritenuta_acconto);
        }

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '.modal');

        // Controlla il salvataggio finale
        $t->see('Riga aggiunta');
    }

    /**
     * Undocumented function
     *
     * @param string $pattern
     * @return void
     */
    protected function setFinalPattern($pattern)
    {
        $this->finalPattern = $pattern;
    }

    /**
     * Undocumented function
     *
     * @param string $type
     * @return void
     */
    protected function getFinalValue($type)
    {
        return str_replace('|name|', strtoupper($type), $this->finalPattern);
    }

    /**
     * Undocumented function
     *
     * @param AcceptanceTester $t
     * @return void
     */
    public function testImporti(AcceptanceTester $t)
    {
        $this->addRow($t, 'Riga 1', 1, 34);
        $this->addRow($t, 'Riga 2', 1, 17.44);
        $this->addRow($t, 'Riga 3', 48, 0.52);
        $this->addRow($t, 'Riga 4', 66, 0.44);
        $this->addRow($t, 'Riga 5', 1, 104.90);
        $this->addRow($t, 'Riga 6', 1, 2);

        $t->see("212,34", $this->getFinalValue('Imponibile'));
        $t->see("46,71", $this->getFinalValue('IVA'));
        $t->see("259,05", $this->getFinalValue('Totale'));

        $this->addRow($t, 'Riga 7 con sconto in euro', 15, 12, 2);
        $this->addRow($t, 'Riga 8 con sconto percentuale', 15, 10, 20, 'PRC');

        $t->see("542,34", $this->getFinalValue('Imponibile'));
        $t->see("60,00", $this->getFinalValue('Sconto'));
        $t->see("482,34", $this->getFinalValue('Imponibile scontato'));
        $t->see("106,11", $this->getFinalValue('IVA'));
        $t->see("588,45", $this->getFinalValue('Totale'));
    }
}
