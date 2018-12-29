<?php

namespace Helper\Common;

use AcceptanceTester;

class RowHelper extends \Codeception\Module
{
    /** @param string */
    protected $finalPattern = "//div[@class='panel-heading' and contains(string(), 'Righe')]/parent::*//table//tr[contains(string(), '|name|')]//td[2]";
    protected $dir;

    /**
     * Aggiunge una nuova riga.
     *
     * @param AcceptanceTester $t
     * @param string           $descrizione
     * @param int              $qta
     * @param float            $prezzo
     * @param int              $sconto
     * @param string           $tipo_sconto
     * @param int              $id_iva
     * @param int              $id_rivalsa_inps
     * @param int              $id_ritenuta_acconto
     */
    public function addRow(AcceptanceTester $t, $descrizione, $qta, $prezzo, $sconto = 0, $tipo_sconto = 'UNT', $id_iva = null, $id_rivalsa_inps = null, $id_ritenuta_acconto = null)
    {
        // Apre il modal
        $t->clickAndWaitModal('Riga', '#tab_0');

        $this->fill($t, $descrizione, $qta, $prezzo, $sconto, $tipo_sconto, $id_iva, $id_rivalsa_inps, $id_ritenuta_acconto);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '.modal');

        // Controlla il salvataggio finale
        $t->see('Riga aggiunta');
    }

    /**
     * Aggiunge un nuovo articolo.
     *
     * @param AcceptanceTester $t
     * @param string           $descrizione
     * @param int              $qta
     * @param float            $prezzo
     * @param int              $sconto
     * @param string           $tipo_sconto
     * @param int              $id_iva
     * @param int              $id_rivalsa_inps
     * @param int              $id_ritenuta_acconto
     */
    public function addArticle(AcceptanceTester $t, $id_articolo, $descrizione, $qta, $prezzo, $sconto = 0, $tipo_sconto = 'UNT', $id_iva = null, $id_rivalsa_inps = null, $id_ritenuta_acconto = null)
    {
        // Apre il modal
        $t->clickAndWaitModal('Articolo', '#tab_0');

        $t->select2ajax('#idarticolo', $id_articolo);
        $this->fill($t, $descrizione, $qta, $prezzo, $sconto, $tipo_sconto, $id_iva, $id_rivalsa_inps, $id_ritenuta_acconto);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '.modal');

        // Controlla il salvataggio finale
        $t->see('Articolo aggiunto');
    }

    /**
     * Undocumented function.
     *
     * @param AcceptanceTester $t
     */
    public function testImporti(AcceptanceTester $t, $direzione = 'entrata')
    {
        $this->dir = $direzione;

        // Righe di test (issue #98)
        $this->addRow($t, 'Riga 1', 1, 34);
        $this->addRow($t, 'Riga 2', 1, 17.44);
        $this->addRow($t, 'Riga 3', 48, 0.52);
        $this->addRow($t, 'Riga 4', 66, 0.44);
        $this->addRow($t, 'Riga 5', 1, 104.90);
        $this->addRow($t, 'Riga 6', 1, 2);

        $t->see('212,34', $this->getFinalValue('Imponibile'));
        $t->see('46,71', $this->getFinalValue('IVA'));
        $t->see('259,05', $this->getFinalValue('Totale'));

        // Righe di controllo sugli sconti
        $this->addRow($t, 'Riga 7 con sconto in euro', 15, 12, 2);
        $this->addRow($t, 'Riga 8 con sconto percentuale', 15, 10, 20, 'PRC');

        $t->see('542,34', $this->getFinalValue('Imponibile'));
        $t->see('60,00', $this->getFinalValue('Sconto'));
        $t->see('482,34', $this->getFinalValue('Imponibile scontato'));
        $t->see('106,11', $this->getFinalValue('IVA'));
        $t->see('588,45', $this->getFinalValue('Totale'));

        // Sconto globale in euro
        $t->fillField('Sconto incondizionato', 100);
        $t->select2ajax('#tipo_sconto_generico', 'UNT');
        $t->clickAndWait('Salva');

        $t->see('442,34', $this->getFinalValue('Imponibile'));
        $t->see('60,00', $this->getFinalValue('Sconto'));
        $t->see('382,34', $this->getFinalValue('Imponibile scontato'));
        $t->see('84,11', $this->getFinalValue('IVA'));
        $t->see('466,45', $this->getFinalValue('Totale'));

        // Sconto globale in percentuale
        $t->fillField('Sconto incondizionato', 10);
        $t->select2ajax('#tipo_sconto_generico', 'PRC');
        $t->clickAndWait('Salva');

        $t->see('494,11', $this->getFinalValue('Imponibile'));
        $t->see('60,00', $this->getFinalValue('Sconto'));
        $t->see('434,11', $this->getFinalValue('Imponibile scontato'));
        $t->see('95,50', $this->getFinalValue('IVA'));
        $t->see('529,61', $this->getFinalValue('Totale'));
    }

    /**
     * Completa le informazioni per la creazione di un nuovo elemento.
     *
     * @param AcceptanceTester $t
     * @param [type]           $descrizione
     * @param [type]           $qta
     * @param [type]           $prezzo
     * @param int              $sconto
     * @param string           $tipo_sconto
     * @param [type]           $id_iva
     * @param [type]           $id_rivalsa_inps
     * @param [type]           $id_ritenuta_acconto
     */
    protected function fill(AcceptanceTester $t, $descrizione, $qta, $prezzo, $sconto = 0, $tipo_sconto = 'UNT', $id_iva = null, $id_rivalsa_inps = null, $id_ritenuta_acconto = null)
    {
        $t->fillField('#descrizione_riga', $descrizione);
        $t->fillField('Q.tà', $qta);

        $label = ($this->dir == 'uscita') ? 'Prezzo unitario' : 'Prezzo unitario di vendita';
        $t->fillField($label, $prezzo);

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
    }

    /**
     * Undocumented function.
     *
     * @param string $pattern
     */
    protected function setFinalPattern($pattern)
    {
        $this->finalPattern = $pattern;
    }

    /**
     * Undocumented function.
     *
     * @param string $type
     */
    protected function getFinalValue($type)
    {
        return str_replace('|name|', strtoupper($type), $this->finalPattern);
    }
}
