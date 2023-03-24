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

use Auth;
use Common\Components\Component;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
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

        $user = Auth::user();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $direzione = $tipo_documento->dir;
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

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

        // Se il ddt è un ddt cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate($stato_documento);
        $model->id_segment = $id_segment;
        $model->idagente = $anagrafica->idagente;

        $model->save();

        // Salvataggio delle informazioni
        $model->data = $data;

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->numero = static::getNextNumero($data, $direzione, $id_segment);
        $model->numero_esterno = static::getNextNumeroSecondario($data, $direzione, $id_segment);

        // Imposto, come sede aziendale, la prima sede disponibile come utente
        if ($direzione == 'entrata') {
            $model->idsede_partenza = $user->sedi[0];
        } else {
            $model->idsede_destinazione = $user->sedi[0];
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
        return $this->direzione == 'entrata' ? 'Ddt di vendita' : 'DDT di acquisto';
    }

    public function getDirezioneAttribute()
    {
        return $this->tipo->dir;
    }

    public function isImportabile()
    {
        $database = database();
        $stati = $database->fetchArray('SELECT descrizione FROM `dt_statiddt` WHERE `is_fatturabile` = 1');
        foreach ($stati as $stato) {
            $stati_importabili[] = $stato['descrizione'];
        }

        $causale = $database->fetchOne('SELECT * FROM `dt_causalet` WHERE `id` = '.prepare($this->idcausalet));

        return $causale['is_importabile'] && in_array($this->stato->descrizione, $stati_importabili);
    }

    public function getReversedAttribute()
    {
        $database = database();
        $causale = $database->fetchOne('SELECT * FROM `dt_causalet` WHERE `id` = '.prepare($this->idcausalet));

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

        $peso_lordo = $righe->sum(function ($item) {
            return $item->isArticolo() ? $item->articolo->peso_lordo * $item->qta : 0;
        });

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

        $volume = $righe->sum(function ($item) {
            return $item->isArticolo() ? $item->articolo->volume * $item->qta : 0;
        });

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

            $fattura = Fattura::find($trigger->iddocumento);
            if (!empty($fattura)) {
                $righe_fatturate = $fattura->getRighe()->where('idddt', '=', $this->id);
                $qta_fatturate = $righe_fatturate->sum('qta');
                $parziale_fatturato = $qta != $qta_fatturate;
            }

            // Impostazione del nuovo stato
            if ($qta_evasa == 0) {
                $descrizione = 'Bozza';
            } elseif (empty($qta_fatturate)) {
                $descrizione = $parziale ? 'Parzialmente evaso' : 'Evaso';
            } else {
                $descrizione = $parziale_fatturato ? 'Parzialmente fatturato' : 'Fatturato';
            }

            $stato = Stato::where('descrizione', $descrizione)->first();
            $this->stato()->associate($stato);
            $this->save();
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

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }
}
