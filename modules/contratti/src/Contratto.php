<?php

namespace Modules\Contratti;

use Carbon\Carbon;
use Common\Document;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Interventi\TipoSessione;
use Traits\RecordTrait;
use Util\Generator;

class Contratto extends Document
{
    use RecordTrait;

    protected $table = 'co_contratti';

    /**
     * Crea un nuovo contratto.
     *
     * @param Anagrafica $anagrafica
     * @param string     $nome
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $nome)
    {
        $model = parent::build();

        $stato_documento = Stato::where('descrizione', 'Bozza')->first();

        $id_anagrafica = $anagrafica->id;
        $id_agente = $anagrafica->idagente;

        $id_pagamento = $anagrafica->idpagamento_vendite;
        if (empty($id_pagamento)) {
            $id_pagamento = setting('Tipo di pagamento predefinito');
        }

        $model->anagrafica()->associate($anagrafica);
        $model->stato()->associate($stato_documento);

        $model->numero = static::getNextNumero();

        // Salvataggio delle informazioni
        $model->nome = $nome;
        $model->data_bozza = Carbon::now();

        if (!empty($id_agente)) {
            $model->idagente = $id_agente;
        }

        if (!empty($id_pagamento)) {
            $model->idpagamento = $id_pagamento;
        }

        $model->save();

        $model->fixTipiSessioni();

        return $model;
    }

    public function fixTipiSessioni()
    {
        $database = database();
        $database->query('DELETE FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare($this->id));

        // Aggiunta associazioni costi unitari al contratto
        $tipi = TipoSessione::where('costo_orario', '<>', 0)
            ->where('costo_km', '<>', 0)
            ->where('costo_diritto_chiamata', '<>', 0)
            ->get();

        foreach ($tipi as $tipo) {
            $database->insert('co_contratti_tipiintervento', [
                'idcontratto' => $this->id,
                'idtipointervento' => $tipo->id,
                'costo_ore' => $tipo->costo_orario,
                'costo_km' => $tipo->costo_km,
                'costo_dirittochiamata' => $tipo->costo_diritto_chiamata,
                'costo_ore_tecnico' => $tipo->costo_orario_tecnico,
                'costo_km_tecnico' => $tipo->costo_km_tecnico,
                'costo_dirittochiamata_tecnico' => $tipo->costo_diritto_chiamata_tecnico,
            ]);
        }
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Contratti';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstato');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idcontratto');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idcontratto');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idcontratto');
    }

    public function interventi()
    {
        return $this->hasMany(Intervento::class, 'id_contratto');
    }

    // Metodi statici

    /**
     * Calcola il nuovo numero di contratto.
     *
     * @return string
     */
    public static function getNextNumero()
    {
        $maschera = setting('Formato codice contratti');

        $ultimo = Generator::getPreviousFrom($maschera, 'co_contratti', 'numero');
        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }
}
