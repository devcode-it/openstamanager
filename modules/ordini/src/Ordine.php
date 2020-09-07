<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Common\Components\Description;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\DDT;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Ordine extends Document
{
    use ReferenceTrait;
    use RecordTrait;

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
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data)
    {
        $model = parent::build();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $direzione = $tipo_documento->dir;

        $database = database();

        if ($direzione == 'entrata') {
            $conto = 'vendite';
        } else {
            $conto = 'acquisti';
        }

        // Tipo di pagamento e banca predefinite dall'anagrafica
        $id_pagamento = $database->fetchOne('SELECT id FROM co_pagamenti WHERE id = :id_pagamento', [
            ':id_pagamento' => $anagrafica['idpagamento_'.$conto],
        ])['id'];

        // Se il ordine è un ordine cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);

        $model->save();

        // Salvataggio delle informazioni
        $model->data = $data;

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->numero = static::getNextNumero($data, $direzione);
        $model->numero_esterno = static::getNextNumeroSecondario($data, $direzione);

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

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Description $trigger)
    {
        parent::triggerEvasione($trigger);

        if (setting('Cambia automaticamente stato ordini fatturati')) {
            $righe = $this->getRighe();

            $qta_evasa = $righe->sum('qta_evasa');
            $qta = $righe->sum('qta');
            $parziale = $qta != $qta_evasa;

            $stato_attuale = $this->stato;

            // Impostazione del nuovo stato
            if ($qta_evasa == 0) {
                $descrizione = 'Bozza';
            } elseif (!in_array($stato_attuale->descrizione, ['Parzialmente fatturato', 'Fatturato']) && $trigger->parent instanceof DDT) {
                $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
            } else {
                $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';
            }

            $stato = Stato::where('descrizione', $descrizione)->first();
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
    public static function getNextNumero($data, $direzione)
    {
        $maschera = '#';

        $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoordine IN (SELECT id FROM or_tipiordine WHERE dir = '.prepare($direzione).')',
        ]);
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
    public static function getNextNumeroSecondario($data, $direzione)
    {
        if ($direzione == 'uscita') {
            return '';
        }

        $maschera = setting('Formato numero secondario ordine');

        $ultimo = Generator::getPreviousFrom($maschera, 'or_ordini', 'numero_esterno', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoordine IN (SELECT id FROM or_tipiordine WHERE dir = '.prepare($direzione).')',
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return $this->tipo->descrizione;
    }

    public function getReferenceNumber()
    {
        return $this->numero_esterno ?: $this->numero;
    }

    public function getReferenceDate()
    {
        return $this->data;
    }
}
