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

namespace Modules\Ordini;

use Common\Components\Component;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\DDT;
use Modules\Interventi\Intervento;
use Modules\Pagamenti\Pagamento;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Ordine extends Document
{
    use ReferenceTrait;
    use RecordTrait;

    /**
     * @var bool Disabilita movimentazione automatica
     */
    public static $movimenta_magazzino = false;

    protected $table = 'or_ordini';

    protected $with = [
        'tipo',
    ];

    /**
     * Crea un nuovo ordine.
     *
     * @param string $data
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment = null)
    {
        $model = new static();

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

        // Se il ordine è un ordine cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
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
        return $this->direzione == 'entrata' ? 'Ordini cliente' : 'Ordini fornitore';
    }

    public function getDirezioneAttribute()
    {
        return $this->tipo->dir;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'idtipoordine');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstatoordine');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idordine');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idordine');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idordine');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idordine');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_ordine');
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        if (setting('Cambia automaticamente stato ordini fatturati')) {
            $righe = $this->getRighe();

            $qta_evasa = $righe->sum('qta_evasa');
            $qta = $righe->sum('qta');
            $parziale = $qta != $qta_evasa;

            $stato_attuale = $this->stato;
            $nome_stato = (database()->isConnected() && database()->tableExists('or_statiordine_lang') ? $stato_attuale->getTranslation('title', \Models\Locale::getPredefined()->id) : $stato_attuale->descrizione);

            $righe_fatturate = $righe->where('qta_evasa', '>', 0);
            $qta_fatturate = 0;
            $fatture_collegate_totali = 0;

            foreach ($righe_fatturate as $riga) {
                $fatture_collegate = database()->table('co_righe_documenti')
                    ->where('original_id', $riga->id)
                    ->where('original_type', get_class($riga))
                    ->join('co_documenti', 'co_righe_documenti.iddocumento', '=', 'co_documenti.id')
                    ->count();

                $fatture_collegate_totali += $fatture_collegate;

                if ($fatture_collegate > 0) {
                    $qta_fatturate += $riga->qta;
                }
            }

            $parziale_fatturato = $qta != $qta_fatturate;

            // Impostazione del nuovo stato
            if ($qta_evasa == 0) {
                $descrizione = 'Accettato';
            } elseif ($trigger->getDocument() instanceof \Modules\Fatture\Fattura) {
                $descrizione = $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
            } elseif ($trigger->getDocument() instanceof \Modules\DDT\DDT) {
                $ddt = $trigger->getDocument();
                $fatture_ddt = database()->table('co_righe_documenti')
                    ->where('idddt', $ddt->id)
                    ->join('co_documenti', 'co_righe_documenti.iddocumento', '=', 'co_documenti.id')
                    ->count();

                if ($fatture_ddt > 0) {
                    $descrizione = $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
                } else {
                    $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
                }
            } elseif ($fatture_collegate_totali > 0) {
                $descrizione = $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
            } elseif (in_array($nome_stato, ['Parzialmente fatturato', 'Fatturato'])) {
                $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
            } elseif ($qta_evasa > 0) {
                $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
            } else {
                $descrizione = $nome_stato;
            }

            if (database()->isConnected() && database()->tableExists('or_statiordine_lang')) {
                $stato = Stato::where('name', $descrizione)->first()->id;
            } else {
                $stato = Stato::where('descrizione', $descrizione)->first()->id;
            }
            $this->stato()->associate($stato);
            $this->save();
        }
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di ordine.
     *
     * @param string $data
     * @param string $direzione
     * @param int    $id_segment
     *
     * @return string
     */
    public static function getNextNumero($data, $direzione, $id_segment)
    {
        if ($direzione == 'entrata') {
            $maschera = '#';
        } else {
            $maschera = Generator::getMaschera($id_segment);

            if (str_contains($maschera, 'm')) {
                $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero', [
                    'YEAR(data) = '.prepare(date('Y', strtotime($data))),
                    'MONTH(data) = '.prepare(date('m', strtotime($data))),
                    'idtipoordine IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = '.prepare($direzione).')',
                ]);
            } elseif (str_contains($maschera, 'YYYY') or str_contains($maschera, 'yy')) {
                $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero', [
                    'YEAR(data) = '.prepare(date('Y', strtotime($data))),
                    'idtipoordine IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = '.prepare($direzione).')',
                ]);
            } else {
                $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero', [
                    'YEAR(data) = '.prepare(date('Y', strtotime($data))),
                    'idtipoordine IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = '.prepare($direzione).')',
                ]);
            }
        }

        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }

    /**
     * Calcola il nuovo numero secondario di ordine.
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

        if (str_contains($maschera, 'm')) {
            $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero_esterno', [
                'YEAR(data) = '.prepare(date('Y', strtotime($data))),
                'MONTH(data) = '.prepare(date('m', strtotime($data))),
                'idtipoordine IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = '.prepare($direzione).')',
            ]);
        } elseif (str_contains($maschera, 'YYYY') or str_contains($maschera, 'yy')) {
            $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero_esterno', [
                'YEAR(data) = '.prepare(date('Y', strtotime($data))),
                'idtipoordine IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = '.prepare($direzione).')',
            ]);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero_esterno', [
                'YEAR(data) = '.prepare(date('Y', strtotime($data))),
                'idtipoordine IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = '.prepare($direzione).')',
            ]);
        }

        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return $this->tipo->getTranslation('title');
    }

    public function getReferenceNumber()
    {
        return setting('Visualizza numero ordine cliente') ? ($this->numero_cliente ?: ($this->numero_esterno ?: $this->numero)) : ($this->numero_esterno ?: $this->numero);
    }

    public function getReferenceDate()
    {
        return setting('Visualizza numero ordine cliente') ? ($this->data_cliente ?: $this->data) : $this->data;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }
}
