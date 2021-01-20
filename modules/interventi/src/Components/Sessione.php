<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\Interventi\Components;

use Common\SimpleModelTrait;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
/*
 * Notazione: i costi sono rivolti all'azienda, i prezzi al cliente.
 *
 * @since 2.4.9
 */
use Modules\Iva\Aliquota;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Sessione extends Model
{
    use SimpleModelTrait;
    use RelationTrait;

    protected $table = 'in_interventi_tecnici';

    protected $aliquota_iva = null;

    /**
     * Crea un nuova sessione collegata ad un intervento.
     *
     * @param string $inizio
     * @param string $fine
     *
     * @return self
     */
    public static function build(Intervento $intervento, Anagrafica $anagrafica, $inizio, $fine)
    {
        if (!$anagrafica->isTipo('Tecnico')) {
            throw new InvalidArgumentException();
        }

        $model = new static();

        $model->document()->associate($intervento);
        $model->anagrafica()->associate($anagrafica);

        $id_tipo = $intervento['idtipointervento'];
        $tipo_sessione = TipoSessione::find($id_tipo);
        $model->tipo()->associate($tipo_sessione);

        $model->orario_inizio = $inizio;
        $model->orario_fine = $fine;

        // Sede secondaria
        if (!empty($intervento['idsede_destinazione'])) {
            $sede = database()->fetchOne('SELECT km FROM an_sedi WHERE id = '.prepare($intervento['idsede_destinazione']));
            $km = $sede['km'];
        }

        // Sede legale dell'anagrafica
        else {
            $km = $intervento->anagrafica->sedeLegale->km;
        }

        $model->km = empty($km) ? 0 : $km;

        $model->save();

        $model->setTipo($id_tipo, true);
        $model->save();

        return $model;
    }

    public function setTipo($id_tipo, $reset = false)
    {
        $previous = $this->idtipointervento;

        $tipo_sessione = TipoSessione::find($id_tipo);
        $this->tipo()->associate($tipo_sessione);

        if ($previous != $id_tipo || $reset) {
            $tariffa = $this->getTariffa($id_tipo);

            // Azzeramento forzato del diritto di chiamata nel caso la sessione non sia la prima dell'intervento nel giorno di inizio o fine
            $sessioni = database()->fetchArray('SELECT id FROM in_interventi_tecnici WHERE (DATE(orario_inizio) = DATE('.prepare($this->orario_inizio).') OR DATE(orario_fine) = DATE('.prepare($this->orario_fine).')) AND (prezzo_dirittochiamata != 0 OR prezzo_dirittochiamata_tecnico != 0) AND id != '.prepare($this->id).' AND idintervento = '.prepare($this->intervento->id));
            if (!empty($sessioni)) {
                $tariffa['costo_dirittochiamata_tecnico'] = 0;
                $tariffa['costo_dirittochiamata'] = 0;

                // Fix se reset non attivo
                $this->prezzo_dirittochiamata = $tariffa['costo_dirittochiamata'];
            }

            // Modifica dei costi
            $this->prezzo_ore_unitario_tecnico = $tariffa['costo_ore_tecnico'];
            $this->prezzo_km_unitario_tecnico = $tariffa['costo_km_tecnico'];
            $this->prezzo_dirittochiamata_tecnico = $tariffa['costo_dirittochiamata_tecnico'];

            // Modifica dei prezzi
            if ($reset) {
                $this->prezzo_ore_unitario = $tariffa['costo_ore'];
                $this->prezzo_km_unitario = $tariffa['costo_km'];
                $this->prezzo_dirittochiamata = $tariffa['costo_dirittochiamata'];
            }
        }
    }

    public function getOreAttribute()
    {
        $inizio = new DateTime($this->orario_inizio);
        $diff = $inizio->diff(new DateTime($this->orario_fine));

        $ore = $diff->i / 60 + $diff->h + ($diff->days * 24);

        return $ore;
    }

    /**
     * Salva la sessione, impostando i campi dipendenti dai singoli parametri.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->attributes['ore'] = $this->ore;

        $this->attributes['prezzo_ore_consuntivo'] = $this->prezzo_manodopera + $this->prezzo_diritto_chiamata;
        $this->attributes['prezzo_km_consuntivo'] = $this->prezzo_viaggio;

        $this->attributes['prezzo_ore_consuntivo_tecnico'] = $this->costo_manodopera + $this->costo_diritto_chiamata;
        $this->attributes['prezzo_km_consuntivo_tecnico'] = $this->costo_viaggio;

        $this->attributes['sconto'] = $this->sconto_totale_manodopera;
        $this->attributes['scontokm'] = $this->sconto_totale_viaggio;

        return parent::save($options);
    }

    public function getDocumentID()
    {
        return 'idintervento';
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idtecnico');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function parent()
    {
        return $this->belongsTo(Intervento::class, $this->getDocumentID());
    }

    // Costi per l'azienda

    /**
     * Restituisce il costo orario (per l'azienda) per la sessione del tecnico.
     *
     * @return float
     */
    public function getCostoOrarioAttribute()
    {
        return $this->attributes['prezzo_ore_unitario_tecnico'];
    }

    /**
     * Restituisce il costo del diritto di chiamata (per l'azienda) per la sessione del tecnico.
     *
     * @return float
     */
    public function getCostoDirittoChiamataAttribute()
    {
        return $this->attributes['prezzo_dirittochiamata_tecnico'];
    }

    /**
     * Restituisce il costo chilometrico (per l'azienda) del viaggio del tecnico.
     *
     * @return float
     */
    public function getCostoChilometricoAttribute()
    {
        return $this->attributes['prezzo_km_unitario_tecnico'];
    }

    /**
     * Restituisce il costo totale della manodopera escluso il diritto di chiamata (per l'azienda) per la sessione del tecnico.
     *
     * @return float
     */
    public function getCostoManodoperaAttribute()
    {
        return $this->costo_orario * $this->ore;
    }

    /**
     * Restituisce il costo totale (per l'azienda) del viaggio del tecnico.
     *
     * @return float
     */
    public function getCostoViaggioAttribute()
    {
        return $this->costo_chilometrico * $this->km;
    }

    // Prezzi per il cliente

    /**
     * Restituisce il prezzo del diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoDirittoChiamataAttribute()
    {
        return $this->attributes['prezzo_dirittochiamata'];
    }

    /**
     * Restituisce il prezzo del diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoChilometricoAttribute()
    {
        return $this->attributes['prezzo_km_unitario'];
    }

    /**
     * Restituisce il prezzo del diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoOrarioAttribute()
    {
        return $this->attributes['prezzo_ore_unitario'];
    }

    /**
     * Restituisce il prezzo totale della manodopera escluso il diritto di chiamata (per il cliente) per la sessione del tecnico.
     *
     * @return float
     */
    public function getPrezzoManodoperaAttribute()
    {
        return $this->prezzo_orario * $this->ore;
    }

    /**
     * Restituisce lo sconto totale km in euro.
     *
     * @return float
     */
    public function getScontoTotaleManodoperaAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->sconto_unitario,
            'prezzo' => $this->prezzo_orario,
            'qta' => $this->ore,
            'tipo' => $this->tipo_sconto,
        ]);
    }

    /**
     * Restituisce il prezzo totale scontato (per il cliente) del viaggio del tecnico.
     *
     * @return float
     */
    public function getPrezzoManodoperaScontatoAttribute()
    {
        return $this->prezzo_manodopera - $this->sconto_totale_manodopera;
    }

    /**
     * Restituisce il prezzo totale (per il cliente) del viaggio del tecnico.
     *
     * @return float
     */
    public function getPrezzoViaggioAttribute()
    {
        return $this->prezzo_chilometrico * $this->km;
    }

    /**
     * Restituisce lo sconto totale km in euro.
     *
     * @return float
     */
    public function getScontoTotaleViaggioAttribute()
    {
        return calcola_sconto([
            'sconto' => $this->scontokm_unitario,
            'prezzo' => $this->prezzo_chilometrico,
            'qta' => $this->km,
            'tipo' => $this->tipo_scontokm,
        ]);
    }

    /**
     * Restituisce il prezzo totale scontato (per il cliente) del viaggio del tecnico.
     *
     * @return float
     */
    public function getPrezzoViaggioScontatoAttribute()
    {
        return $this->prezzo_viaggio - $this->sconto_totale_viaggio;
    }

    // Attributi di contabilità

    /**
     * Restituisce l'imponibile dell'elemento.
     *
     * @return float
     */
    public function getImponibileAttribute()
    {
        return $this->prezzo_manodopera + $this->prezzo_viaggio + $this->prezzo_diritto_chiamata;
    }

    /**
     * Restituisce il totale imponibile dell'elemento.
     *
     * @return float
     */
    public function getTotaleImponibileAttribute()
    {
        return $this->prezzo_manodopera_scontato + $this->prezzo_viaggio_scontato + $this->prezzo_diritto_chiamata;
    }

    /**
     * Restituisce il totale (imponibile + iva) dell'elemento.
     *
     * @return float
     */
    public function getTotaleAttribute()
    {
        return $this->totale_imponibile + $this->iva;
    }

    /**
     * Restituisce la spesa (costo_unitario * qta) relativa all'elemento.
     *
     * @return float
     */
    public function getSpesaAttribute()
    {
        return $this->costo_manodopera;
    }

    /**
     * Restituisce il margine totale (imponibile - spesa) relativo all'elemento.
     *
     * @return float
     */
    public function getMargineAttribute()
    {
        return $this->imponibile - $this->spesa;
    }

    /**
     * Restituisce lo sconto della riga corrente in euro.
     *
     * @return float
     */
    public function getScontoAttribute()
    {
        return $this->sconto_totale_manodopera + $this->sconto_totale_viaggio;
    }

    /**
     * Restituisce il margine percentuale relativo all'elemento.
     *
     * @return float
     */
    public function getMarginePercentualeAttribute()
    {
        return $this->imponibile ? (1 - ($this->spesa / $this->imponibile)) * 100 : 100;
    }

    public function getIvaIndetraibileAttribute()
    {
        return $this->iva / 100 * $this->aliquota->indetraibile;
    }

    public function getIvaAttribute()
    {
        return ($this->totale_imponibile) * $this->aliquota->percentuale / 100;
    }

    public function getIvaDetraibileAttribute()
    {
        return $this->iva - $this->iva_indetraibile;
    }

    public function getAliquotaAttribute()
    {
        if (!isset($this->aliquota_iva)) {
            $id_iva = setting('Iva predefinita');

            $this->aliquota_iva = Aliquota::find($id_iva);
        }

        return $this->aliquota_iva;
    }

    protected function getTariffa($id_tipo)
    {
        $database = database();

        // Costi unitari dalla tariffa del tecnico
        $result = $database->fetchOne('SELECT * FROM in_tariffe WHERE idtecnico='.prepare($this->anagrafica->id).' AND idtipointervento = '.prepare($id_tipo));

        // Costi unitari del contratto
        $id_contratto = $this->intervento->id_contratto;
        if (!empty($id_contratto)) {
            $tariffa_contratto = $database->fetchOne('SELECT costo_ore, costo_km, costo_dirittochiamata FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare($id_contratto).' AND idtipointervento = '.prepare($id_tipo));

            if (!empty($tariffa_contratto)) {
                $result = array_merge($result, $tariffa_contratto);
            }
        }

        return $result;
    }
}
