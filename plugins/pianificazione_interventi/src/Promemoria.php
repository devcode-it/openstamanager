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

namespace Plugins\PianificazioneInterventi;

use Common\Document;
use Modules\Contratti\Contratto;
use Modules\Interventi\Intervento;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Traits\RecordTrait;

class Promemoria extends Document
{
    use RecordTrait;

    /**
     * @var bool Disabilita movimentazione automatica
     */
    public static $movimenta_magazzino = false;

    protected $table = 'co_promemoria';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'data_richiesta',
    ];

    /**
     * Crea un nuovo promemoria.
     *
     * @param string $data_richiesta
     *
     * @return self
     */
    public static function build(Contratto $contratto, TipoSessione $tipo, $data_richiesta)
    {
        $model = new static();

        $model->contratto()->associate($contratto);
        $model->tipo()->associate($tipo);

        $model->data_richiesta = $data_richiesta;
        $model->idsede = $contratto->idsede;

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function getPluginAttribute()
    {
        return 'Pianificazione interventi';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function pianifica(Intervento $intervento, $copia_impianti = true)
    {
        $this->intervento()->associate($intervento); // Collego l'intervento ai promemoria
        $this->save();

        // Copia delle righe
        $righe = $this->getRighe();
        foreach ($righe as $riga) {
            $copia = $riga->copiaIn($intervento, $riga->qta);
        }

        // Copia degli allegati
        $allegati = $this->uploads();
        foreach ($allegati as $allegato) {
            $allegato->copia([
                'id_module' => $intervento->getModule()->id,
                'id_record' => $intervento->id,
            ]);
        }

        // Collego gli impianti del promemoria all'intervento
        $database = database();
        if ($copia_impianti && !empty($this->idimpianti)) {
            $impianti = explode(',', $this->idimpianti);
            $impianti = array_unique($impianti);
            foreach ($impianti as $impianto) {
                $database->insert('my_impianti_interventi', [
                    'idintervento' => $intervento->id,
                    'idimpianto' => $impianto,
                ]);
            }
        }
    }

    public function anagrafica()
    {
        return $this->contratto->anagrafica();
    }

    public function contratto()
    {
        return $this->belongsTo(Contratto::class, 'idcontratto');
    }

    public function intervento()
    {
        return $this->belongsTo(Intervento::class, 'idintervento');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoSessione::class, 'idtipointervento');
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'id_promemoria');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'id_promemoria');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'id_promemoria');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'id_promemoria');
    }

    public function getModuleAttribute()
    {
        // TODO: Implement getModuleAttribute() method.
    }

    public function getReferenceName()
    {
        // TODO: Implement getReferenceName() method.
    }

    public function getReferenceNumber()
    {
        // TODO: Implement getReferenceNumber() method.
    }

    public function getReferenceDate()
    {
        // TODO: Implement getReferenceDate() method.
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }


    public function getReference()
    {
        // TODO: Implement getReference() method.
    }
}
