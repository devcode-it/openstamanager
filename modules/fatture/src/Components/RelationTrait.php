<?php

namespace Modules\Fatture\Components;

use Modules\Fatture\Fattura;
use Modules\Ritenute\RitenutaAcconto;
use Modules\Rivalse\RivalsaINPS;

trait RelationTrait
{
    public function getParentID()
    {
        return 'iddocumento';
    }

    public function parent()
    {
        return $this->belongsTo(Fattura::class, $this->getParentID());
    }

    public function fattura()
    {
        return $this->parent();
    }

    public function getNettoAttribute()
    {
        $result = $this->totale - $this->ritenuta_acconto - $this->ritenuta_contributi;

        if ($this->parent->split_payment) {
            $result = $result - $this->iva;
        }

        return $result;
    }

    /**
     * Restituisce i dati aggiuntivi per la fattura elettronica dell'elemento.
     *
     * @return array
     */
    public function getDatiAggiuntiviFEAttribute()
    {
        $result = json_decode($this->attributes['dati_aggiuntivi_fe'], true);

        return (array) $result;
    }

    /**
     * Imposta i dati aggiuntivi per la fattura elettronica dell'elemento.
     */
    public function setDatiAggiuntiviFEAttribute($values)
    {
        $values = (array) $values;
        $dati = array_deep_clean($values);

        $this->attributes['dati_aggiuntivi_fe'] = json_encode($dati);
    }

    /**
     * Restituisce il totale (imponibile + iva + rivalsa_inps + iva_rivalsainps) dell'elemento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->totale_imponibile + $this->iva + $this->rivalsa_inps + $this->iva_rivalsa_inps;
    }

    public function getRivalsaINPSAttribute()
    {
        return $this->totale_imponibile / 100 * $this->rivalsa->percentuale;
    }

    public function getIvaRivalsaINPSAttribute()
    {
        return $this->rivalsa_inps / 100 * $this->aliquota->percentuale;
    }

    public function getRitenutaAccontoAttribute()
    {
        $result = $this->totale_imponibile;

        if ($this->calcolo_ritenuta_acconto == 'IMP+RIV') {
            $result += $this->rivalsainps;
        }

        $ritenuta = $this->ritenuta;
        $result = $result * $ritenuta->percentuale_imponibile / 100;

        return $result / 100 * $ritenuta->percentuale;
    }

    public function getRitenutaContributiAttribute()
    {
        if ($this->attributes['ritenuta_contributi']) {
            $result = $this->totale_imponibile;
            $ritenuta = $this->parent->ritenutaContributi;

            $result = $result * $ritenuta->percentuale_imponibile / 100;

            return $result / 100 * $ritenuta->percentuale;
        }

        return 0;
    }

    /**
     * Imposta l'identificatore della Rivalsa INPS.
     *
     * @param int $value
     */
    public function setIdRivalsaINPSAttribute($value)
    {
        $this->attributes['idrivalsainps'] = $value;
        $this->load('rivalsa');
    }

    /**
     * Imposta l'identificatore della Ritenuta d'Acconto.
     *
     * @param int $value
     */
    public function setIdRitenutaAccontoAttribute($value)
    {
        $this->attributes['idritenutaacconto'] = $value;
        $this->load('ritenuta');
    }

    public function getIdContoAttribute()
    {
        return $this->idconto;
    }

    public function setIdContoAttribute($value)
    {
        $this->attributes['idconto'] = $value;
    }

    public function rivalsa()
    {
        return $this->belongsTo(RivalsaINPS::class, 'idrivalsainps');
    }

    public function ritenuta()
    {
        return $this->belongsTo(RitenutaAcconto::class, 'idritenutaacconto');
    }

    /**
     * Salva la riga, impostando i campi dipendenti dai parametri singoli.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->fixRitenutaAcconto();
        $this->fixRivalsaINPS();

        return parent::save($options);
    }

    public function delete()
    {
        $result = parent::delete();

        if (!empty($this->idintervento)) {
            database()->query("UPDATE in_interventi SET idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE codice = 'OK') WHERE id=".prepare($this->idintervento));
        }

        return $result;
    }

    /**
     * Effettua i conti per la Rivalsa INPS.
     */
    protected function fixRivalsaINPS()
    {
        $this->attributes['rivalsainps'] = $this->rivalsa_inps;
    }

    /**
     * Effettua i conti per la Ritenuta d'Acconto, basandosi sul valore del campo calcolo_ritenuta_acconto.
     */
    protected function fixRitenutaAcconto()
    {
        $this->attributes['ritenutaacconto'] = $this->ritenuta_acconto;
    }

    protected function evasione($diff)
    {
        parent::evasione($diff);

        $database = database();

        // Se c'è un collegamento ad un ddt, aggiorno la quantità evasa
        if (!empty($this->idddt)) {
            $database->query('UPDATE dt_righe_ddt SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idddt = '.prepare($this->idddt).' AND idiva = '.prepare($this->idiva).' AND qta_evasa < qta LIMIT 1');
        }

        // Se c'è un collegamento ad un ordine, aggiorno la quantità evasa
        elseif (!empty($this->idordine)) {
            $database->query('UPDATE or_righe_ordini SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idordine = '.prepare($this->idordine).' AND idiva = '.prepare($this->idiva).' AND qta_evasa < qta LIMIT 1');
        }

        // Se c'è un collegamento ad un preventivo, aggiorno la quantità evasa
        elseif (!empty($this->idpreventivo)) {
            $database->query('UPDATE co_righe_preventivi SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idpreventivo = '.prepare($this->idpreventivo).' AND idiva = '.prepare($this->idiva).' AND qta_evasa < qta LIMIT 1');
        }

        // Se c'è un collegamento ad un contratto, aggiorno la quantità evasa
        elseif (!empty($this->idcontratto)) {
            $database->query('UPDATE co_righe_contratti SET qta_evasa = qta_evasa + '.$diff.' WHERE descrizione = '.prepare($this->descrizione).' AND idarticolo = '.prepare($this->idarticolo).' AND idcontratto = '.prepare($this->idcontratto).' AND idiva = '.prepare($this->idiva).' AND qta_evasa < qta LIMIT 1');
        }
    }
}
