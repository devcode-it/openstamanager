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

namespace Modules\ListeNewsletter;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Traits\RecordTrait;

class Lista extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_lists';

    protected static $translated_fields = [
        'title',
        'description',
    ];

    public static function build($name = null)
    {
        $model = new static();
        $model->save();

        return $model;
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        $query = $this->query;
        if (!empty($query)) {
            $database = database();

            // Rimozione record precedenti
            $database->delete('em_list_receiver', [
                'id_list' => $this->id,
            ]);

            // Ricerca nuovi record
            $database->query('INSERT INTO em_list_receiver (id_list, record_id, record_type) '.preg_replace('/'.preg_quote('SELECT', '/').'/', 'SELECT '.prepare($this->id).',', $query, 1));
        }

        return $result;
    }

    public function getNumeroDestinatariSenzaEmail()
    {
        $anagrafiche = $this->getDestinatari(Anagrafica::class)
            ->join('an_anagrafiche', 'idanagrafica', '=', 'record_id')
            ->where('email', '=', '')
            ->count();

        $sedi = $this->getDestinatari(Sede::class)
            ->join('an_sedi', 'an_sedi.id', '=', 'record_id')
            ->where('email', '=', '')
            ->count();

        $referenti = $this->getDestinatari(Referente::class)
            ->join('an_referenti', 'an_referenti.id', '=', 'record_id')
            ->where('email', '=', '')
            ->count();

        return $anagrafiche + $sedi + $referenti;
    }

    public function getNumeroDestinatariSenzaConsenso()
    {
        $anagrafiche = $this->getDestinatari(Anagrafica::class)
            ->join('an_anagrafiche', 'idanagrafica', '=', 'record_id')
            ->where('an_anagrafiche.enable_newsletter', '=', false)
            ->count();

        $sedi = $this->getDestinatari(Sede::class)
            ->join('an_sedi', 'an_sedi.id', '=', 'record_id')
            ->join('an_anagrafiche', 'an_anagrafiche.idanagrafica', '=', 'an_sedi.idanagrafica')
            ->where('an_anagrafiche.enable_newsletter', '=', false)
            ->count();

        $referenti = $this->getDestinatari(Referente::class)
            ->join('an_referenti', 'an_referenti.id', '=', 'record_id')
            ->join('an_anagrafiche', 'an_anagrafiche.idanagrafica', '=', 'an_referenti.idanagrafica')
            ->where('an_anagrafiche.enable_newsletter', '=', false)
            ->count();

        return $anagrafiche + $sedi + $referenti;
    }

    public function getDestinatari($tipo)
    {
        return $this->destinatari()
            ->where('record_type', '=', $tipo);
    }

    // Relazione Eloquent

    public function destinatari()
    {
        return $this->hasMany(Destinatario::class, 'id_list');
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Liste';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
