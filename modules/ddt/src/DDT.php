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
     * Cache per la causale del DDT.
     *
     * @var array|null
     */
    protected $causaleCache = null;

    /**
     * Crea un nuovo ddt.
     *
     * @param string $data
     *
     * @return self
     */
    /**
     * Cache per lo stato "Bozza".
     *
     * @var int|null
     */
    protected static $statoBozzaCache = null;

    /**
     * Ottiene l'ID dello stato "Bozza" con cache.
     *
     * @return int
     */
    protected static function getStatoBozzaId()
    {
        if (self::$statoBozzaCache === null) {
            self::$statoBozzaCache = Stato::where('name', 'Bozza')->first()->id;
        }

        return self::$statoBozzaCache;
    }

    /**
     * Ottiene la sede dell'utente in base alla direzione del documento.
     *
     * @param string $direzione
     * @return int
     */
    protected static function getSedeUtente($direzione)
    {
        $user = auth_osm()->getUser();
        
        if (empty($user->sedi)) {
            return 0;
        }

        return in_array(0, $user->sedi) ? 0 : $user->sedi[0];
    }

    /**
     * Ottiene l'ID del pagamento in base alla direzione e all'anagrafica.
     *
     * @param Anagrafica $anagrafica
     * @param string $direzione
     * @return int|null
     */
    protected static function getIdPagamento(Anagrafica $anagrafica, $direzione)
    {
        $conto = $direzione == 'entrata' ? 'vendite' : 'acquisti';
        $id_pagamento = $anagrafica['idpagamento_'.$conto];

        // Se il ddt è un ddt cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($direzione == 'entrata' && empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        return $id_pagamento;
    }

    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data, $id_segment = null)
    {
        $model = new static();
        $direzione = $tipo_documento->dir;
        $id_segment = $id_segment ?: getSegmentPredefined($model->getModule()->id);

        $model->anagrafica()->associate($anagrafica);
        $model->tipo()->associate($tipo_documento);
        $model->stato()->associate(self::getStatoBozzaId());
        $model->id_segment = $id_segment;
        $model->idagente = $anagrafica->idagente;
        $model->data = $data;

        $id_pagamento = self::getIdPagamento($anagrafica, $direzione);
        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->numero = static::getNextNumero($data, $direzione, $id_segment);
        $model->numero_esterno = static::getNextNumeroSecondario($data, $direzione, $id_segment);

        // Imposto la sede in base alla direzione
        $id_sede = self::getSedeUtente($direzione);
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

    /**
     * Ottiene la causale del DDT con cache per evitare query duplicate.
     *
     * @return array|null
     */
    protected function getCausale()
    {
        if ($this->causaleCache === null) {
            $database = database();
            $this->causaleCache = $database->fetchOne('SELECT * FROM `dt_causalet` LEFT JOIN `dt_causalet_lang` ON (`dt_causalet`.`id` = `dt_causalet_lang`.`id_record` AND `dt_causalet_lang`.`id_lang` ='.prepare(\Models\Locale::getDefault()->id).') WHERE `dt_causalet`.`id` = '.prepare($this->idcausalet));
        }

        return $this->causaleCache;
    }

    /**
     * Ottiene gli stati importabili con cache.
     *
     * @return array
     */
    protected static $statiImportabiliCache = null;

    protected function getStatiImportabili()
    {
        if (self::$statiImportabiliCache === null) {
            $stati = Stato::where('is_fatturabile', 1)->get();
            self::$statiImportabiliCache = [];
            foreach ($stati as $stato) {
                self::$statiImportabiliCache[] = $stato->getTranslation('title');
            }
        }

        return self::$statiImportabiliCache;
    }

    public function isImportabile()
    {
        $causale = $this->getCausale();
        $stati_importabili = $this->getStatiImportabili();

        return $causale['is_importabile'] && in_array($this->stato->getTranslation('title'), $stati_importabili);
    }

    public function getReversedAttribute()
    {
        $causale = $this->getCausale();

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
     * Verifica se il DDT è collegato a un ordine.
     *
     * @return bool
     */
    protected function isCollegatoAOrdine()
    {
        return $this->getRighe()->contains(function ($riga) {
            return !empty($riga->original_id) && !empty($riga->original_type) && str_contains((string) $riga->original_type, 'Ordini');
        });
    }

    /**
     * Ottiene la quantità totale fatturata per questo DDT.
     *
     * @return float
     */
    protected function getQtaFatturata()
    {
        return database()->table('co_righe_documenti')
            ->selectRaw('SUM(qta) as qta_fatturata')
            ->where('idddt', $this->id)
            ->value('qta_fatturata') ?? 0;
    }

    /**
     * Verifica se ci sono fatture collegate a questo DDT.
     *
     * @return bool
     */
    protected function hasFattureCollegate()
    {
        return database()->table('co_righe_documenti')
            ->where('idddt', $this->id)
            ->exists();
    }

    /**
     * Effettua un controllo sui campi del documento.
     * Viene richiamato dalle modifiche alle righe del documento.
     */
    public function triggerEvasione(Component $trigger)
    {
        parent::triggerEvasione($trigger);

        if (!setting('Cambia automaticamente stato ddt fatturati')) {
            return;
        }

        $righe = $this->getRighe();
        $qta = $righe->sum('qta');
        $qta_evasa = $righe->sum('qta_evasa');
        $parziale = $qta != $qta_evasa;
        $collegato_a_ordine = $this->isCollegatoAOrdine();
        $fatture = $this->hasFattureCollegate();

        // Impostazione del nuovo stato
        if ($qta_evasa == 0 && !$collegato_a_ordine) {
            $descrizione = 'Bozza';
        } elseif ($fatture) {
            $qta_fatturate = $this->getQtaFatturata();
            $parziale_fatturato = $qta != $qta_fatturate;
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

    /**
     * Aggiorna lo stato degli ordini collegati a questo DDT.
     * Quando il DDT passa a "Fatturato" o "Parzialmente fatturato", anche gli ordini collegati
     * devono passare a "Fatturato" o "Parzialmente fatturato".
     */
    public function aggiornaStatiOrdiniCollegati()
    {
        $ordini_da_aggiornare = [];

        // Raccogli tutti gli ordini collegati
        foreach ($this->getRighe() as $riga_ddt) {
            if (!empty($riga_ddt->original_id) && !empty($riga_ddt->original_type) && str_contains((string) $riga_ddt->original_type, 'Ordini')) {
                $riga_ordine = $riga_ddt->getOriginalComponent();

                if (!empty($riga_ordine)) {
                    $ordine = $riga_ordine->getDocument();

                    if (!empty($ordine) && !isset($ordini_da_aggiornare[$ordine->id])) {
                        $ordini_da_aggiornare[$ordine->id] = $ordine;
                    }
                }
            }
        }

        // Aggiorna gli stati degli ordini
        foreach ($ordini_da_aggiornare as $ordine) {
            $righe_ordine = $ordine->getRighe();
            $qta = $righe_ordine->sum('qta');
            $qta_evasa = $righe_ordine->sum('qta_evasa');
            $parziale = $qta != $qta_evasa;

            $descrizione = $parziale ? 'Parzialmente fatturato' : 'Fatturato';

            // Usa la colonna appropriata in base alla tabella disponibile
            $stato = \Modules\Ordini\Stato::where(database()->tableExists('or_statiordine_lang') ? 'name' : 'descrizione', $descrizione)->first()->id;

            $ordine->stato()->associate($stato);
            $ordine->save();
        }
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di ddt.
     *
     * @param string $data
     * @param string $direzione
     * @param int $id_segment
     * @return string
     */
    public static function getNextNumero($data, $direzione, $id_segment)
    {
        return getNextNumeroProgressivo('dt_ddt', 'numero', $data, $id_segment, [
            'direction' => $direzione,
            'skip_direction' => 'entrata',
            'type_document_field' => 'idtipoddt',
            'type_document_table' => 'dt_tipiddt',
            'use_date_pattern' => true,
        ]);
    }

    /**
     * Calcola il nuovo numero secondario di ddt.
     *
     * @param string $data
     * @param string $direzione
     * @param int $id_segment
     * @return string
     */
    public static function getNextNumeroSecondario($data, $direzione, $id_segment)
    {
        return getNextNumeroSecondarioProgressivo('dt_ddt', 'numero_esterno', $data, $id_segment, [
            'direction' => $direzione,
            'skip_direction' => 'uscita',
            'type_document_field' => 'idtipoddt',
            'type_document_table' => 'dt_tipiddt',
            'use_date_pattern' => true,
        ]);
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
