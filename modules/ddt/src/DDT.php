<?php

namespace Modules\DDT;

use Auth;
use Common\Components\Description;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
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
    public static function build(Anagrafica $anagrafica, Tipo $tipo_documento, $data)
    {
        $model = parent::build();

        $user = Auth::user();

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

        // Se il ddt è un ddt cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
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
        $stati_non_importabili = ['Bozza', 'Fatturato'];

        $database = database();
        $causale = $database->fetchOne('SELECT * FROM `dt_causalet` WHERE `id` = '.prepare($this->idcausalet));

        return $causale['is_importabile'] && !in_array($this->stato->descrizione, $stati_non_importabili);
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
    public function triggerEvasione(Description $trigger)
    {
        parent::triggerEvasione($trigger);

        if (setting('Cambia automaticamente stato ddt fatturati')) {
            $righe = $this->getRighe();

            $qta_evasa = $righe->sum('qta_evasa');
            $qta = $righe->sum('qta');
            $parziale = $qta != $qta_evasa;

            // Impostazione del nuovo stato
            if ($qta_evasa == 0) {
                $descrizione = 'Bozza';
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
     * Calcola il nuovo numero di ddt.
     *
     * @param string $data
     * @param string $direzione
     *
     * @return string
     */
    public static function getNextNumero($data, $direzione)
    {
        $maschera = '#';

        $ultimo = Generator::getPreviousFrom($maschera, 'dt_ddt', 'numero', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'idtipoddt IN (SELECT id FROM dt_tipiddt WHERE dir = '.prepare($direzione).')',
        ]);
        $numero = Generator::generate($maschera, $ultimo);

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
    public static function getNextNumeroSecondario($data, $direzione)
    {
        if ($direzione == 'uscita') {
            return '';
        }

        $maschera = setting('Formato numero secondario ddt');

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
}
