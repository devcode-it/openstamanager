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

namespace Modules\DDT;

use Common\Components\Component;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
use Modules\Pagamenti\Pagamento;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class DDT extends Document
{
    use ReferenceTrait;
    use RecordTrait;

    protected $table = 'dt_ddt';

    protected $casts = [
        'bollo' => 'float',
        'peso' => 'float',
        'volume' => 'float',

        'sconto_finale' => 'float',
        'sconto_finale_percentuale' => 'float',
    ];

    protected $with = [
        'tipo',
    ];

    /**
     * Crea un nuovo ddt.
     *
     * @param string $data
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment = null)
    {
        $model = new static();
        $user = auth_osm()->getUser();

        $stato_documento = Stato::where('name', 'Bozza')->first()->id;

        $direzione = $tipo_documento->dir;
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

        if ($direzione == 'entrata') {
            $conto = 'vendite';
        } else {
            $conto = 'acquisti';
        }

        // Tipo di pagamento e banca predefinite dall'anagrafica
        $id_pagamento = $anagrafica['idpagamento_'.$conto];

        // Se il ddt è un ddt cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);
        $model->id_segment = $id_segment;
        $model->idagente = $anagrafica->idagente;

        // Salvataggio delle informazioni
        $model->data = $data;

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->numero = static::getNextNumero($data, $direzione, $id_segment);
        $model->numero_esterno = static::getNextNumeroSecondario($data, $direzione, $id_segment);

        // Imposto, come sede aziendale, la sede legale (0) se disponibile, altrimenti la prima sede disponibile
        $id_sede = 0;
        if (!empty($user->sedi)) {
            // Verifico se la sede legale (0) è tra le sedi dell'utente
            if (in_array(0, $user->sedi)) {
                $id_sede = 0;
            } else {
                // Se la sede legale non è disponibile, prendo la prima sede dell'utente
                $id_sede = $user->sedi[0];
            }
        }

        if ($direzione == 'entrata') {
            $model->idsede_partenza = $id_sede;
        } else {
            $model->idsede_destinazione = $id_sede;
        }

        $model->save();

        return $model;
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return $this->direzione == 'entrata' ? 'Ddt in uscita' : 'DDT in entrata';
    }

    public function getDirezioneAttribute()
    {
        return $this->tipo->dir;
    }

    public function isImportabile()
    {
        $database = database();
        $stati = Stato::where('is_fatturabile', 1)->get();

        foreach ($stati as $stato) {
            $stati_importabili[] = $stato->getTranslation('title');
        }

        $causale = $database->fetchOne('SELECT * FROM `dt_causalet` LEFT JOIN `dt_causalet_lang` ON (`dt_causalet`.`id` = `dt_causalet_lang`.`id_record` AND `dt_causalet_lang`.`id_lang` ='.prepare(\Models\Locale::getDefault()->id).') WHERE `dt_causalet`.`id` = '.prepare($this->idcausalet));

        return $causale['is_importabile'] && in_array($this->stato->getTranslation('title'), $stati_importabili);
    }

    public function getReversedAttribute()
    {
        $database = database();
        $causale = $database->fetchOne('SELECT * FROM `dt_causalet` LEFT JOIN `dt_causalet_lang` ON (`dt_causalet`.`id` = `dt_causalet_lang`.`id_record` AND `dt_causalet_lang`.`id_lang` ='.prepare(\Models\Locale::getDefault()->id).') WHERE `dt_causalet`.`id` = '.prepare($this->idcausalet));

        return $causale['reversed'];
    }

    /**
     * Restituisce il peso calcolato sulla base degli articoli del documento.
     *
     * @return float
     */
    public function getPesoCalcolatoAttribute()
    {
        $righe = $this->getRighe();

        $peso_lordo = $righe->sum(fn ($item) => $item->isArticolo() ? $item->articolo->peso_lordo * $item->qta : 0);

        return $peso_lordo;
    }

    /**
     * Restituisce il volume calcolato sulla base degli articoli del documento.
     *
     * @return float
     */
    public function getVolumeCalcolatoAttribute()
    {
        $righe = $this->getRighe();

        $volume = $righe->sum(fn ($item) => $item->isArticolo() ? $item->articolo->volume * $item->qta : 0);

        return $volume;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'idtipoddt');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatoddt');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idddt');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idddt');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idddt');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idddt');
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        if (setting('Cambia automaticamente stato ddt fatturati')) {
            $righe = $this->getRighe();
            $qta = $righe->sum('qta');
            $qta_evasa = $righe->sum('qta_evasa');
            $parziale = $qta != $qta_evasa;

            $fatture_collegate = database()->table('co_righe_documenti')
                ->where('idddt', $this->id)
                ->join('co_documenti', 'co_righe_documenti.iddocumento', '=', 'co_documenti.id')
                ->count();

            $qta_fatturate = 0;
            $parziale_fatturato = true;
            $fattura = Fattura::find($trigger->iddocumento);
            if (!empty($fattura)) {
                $righe_fatturate = $fattura->getRighe()->where('idddt', $this->id);
                $qta_fatturate = $righe_fatturate->sum('qta');
                $parziale_fatturato = $qta != $qta_fatturate;
            }

            $collegato_a_ordine = false;
            foreach ($righe as $riga) {
                if (!empty($riga->original_id) && !empty($riga->original_type) && str_contains((string) $riga->original_type, 'Ordini')) {
                    $collegato_a_ordine = true;
                    break;
                }
            }

            // Impostazione del nuovo stato
            if ($qta_evasa == 0 && !$collegato_a_ordine) {
                $descrizione = 'Bozza';
            } elseif ($fatture_collegate > 0) {
                $descrizione = $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
            } else {
                $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
            }

            $stato = Stato::where('name', $descrizione)->first()->id;
            $this->stato()->associate($stato);
            $this->save();

            if ($descrizione == 'Fatturato' || $descrizione == 'Parzialmente fatturato') {
                $this->aggiornaStatiOrdiniCollegati();
            }
        }
    }

    /**
     * Aggiorna lo stato degli ordini collegati a questo DDT.
     * Quando il DDT passa a "Fatturato" o "Parzialmente fatturato", anche gli ordini collegati
     * devono passare a "Fatturato" o "Parzialmente fatturato".
     */
    public function aggiornaStatiOrdiniCollegati()
    {
        $righe_ddt = $this->getRighe();

        foreach ($righe_ddt as $riga_ddt) {
            if (!empty($riga_ddt->original_id) && !empty($riga_ddt->original_type) && str_contains((string) $riga_ddt->original_type, 'Ordini')) {
                $riga_ordine = $riga_ddt->getOriginalComponent();

                if (!empty($riga_ordine)) {
                    $ordine = $riga_ordine->getDocument();

                    if (!empty($ordine)) {
                        $fatture_collegate = database()->table('co_righe_documenti')
                            ->where('idddt', $this->id)
                            ->join('co_documenti', 'co_righe_documenti.iddocumento', '=', 'co_documenti.id')
                            ->count();

                        if ($fatture_collegate > 0) {
                            $righe_ordine = $ordine->getRighe();
                            $qta = $righe_ordine->sum('qta');
                            $qta_evasa = $righe_ordine->sum('qta_evasa');
                            $parziale = $qta != $qta_evasa;

                            $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';

                            if (database()->isConnected() && database()->tableExists('or_statiordine_lang')) {
                                $stato = \Modules\Ordini\Stato::where('name', $descrizione)->first()->id;
                            } else {
                                $stato = \Modules\Ordini\Stato::where('descrizione', $descrizione)->first()->id;
                            }

                            $ordine->stato()->associate($stato);
                            $ordine->save();
                        }
                    }
                }
            }
        }
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di ddt.
     *
     * @param string $data
     * @param string $direzione
     *
     * @return string
     */
    public static function getNextNumero($data, $direzione, $id_segment)
    {
        if ($direzione == 'entrata') {
            return '';
        }

        $maschera = Generator::getMaschera($id_segment);

        $ultimo = Generator::getPreviousFrom($maschera, 'dt_ddt', 'numero', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir = '.prepare($direzione).')',
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }

    /**
     * Calcola il nuovo numero secondario di ddt.
     *
     * @param string $data
     * @param string $direzione
     *
     * @return string
     */
    public static function getNextNumeroSecondario($data, $direzione, $id_segment)
    {
        if ($direzione == 'uscita') {
            return '';
        }

        $maschera = Generator::getMaschera($id_segment);

        $ultimo = Generator::getPreviousFrom($maschera, 'dt_ddt', 'numero_esterno', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir = '.prepare($direzione).')',
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return $this->tipo->getTranslation('title');
    }

    public function getReferenceNumber()
    {
        return $this->numero_esterno ?: $this->numero;
    }

    public function getReferenceSecondaryNumber()
    {
        return null;
    }

    public function getReferenceDate()
    {
        return $this->data;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }
}
