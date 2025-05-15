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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;

class Group extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'zz_groups';

    protected static $translated_fields = [
        'title',
    ];

    public static function build($nome = null, $theme = null, $id_module_start = null)
    {
        $model = new static();
        $model->nome = $nome;
        $model->theme = $theme;
        $model->id_module_start = $id_module_start;
        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function users()
    {
        return $this->hasMany(User::class, 'idgruppo');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'zz_permissions', 'idgruppo', 'idmodule')->withPivot('permessi');
    }

    public function views()
    {
        return $this->belongsToMany(View::class, 'zz_group_view', 'id_gruppo', 'id_vista');
    }

    public function getModuleAttribute()
    {
        return '';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }

    /**
     * Sincronizza i permessi delle viste e dei segmenti per un modulo specifico.
     * Se il gruppo non ha permessi sul modulo, rimuove i permessi sulle viste e segmenti.
     * Se il gruppo ha permessi sul modulo, aggiunge i permessi su tutte le viste e segmenti.
     *
     * @param int    $id_module ID del modulo
     * @param string $permessi  Permessi ('r', 'rw', o '-')
     *
     * @return void
     */
    public function syncModulePermissions($id_module, $permessi)
    {
        $database = database();

        // Se i permessi sono di lettura o lettura/scrittura
        if ($permessi == 'r' || $permessi == 'rw') {
            // Sincronizza i permessi delle viste
            $count_views = $database->fetchNum('SELECT * FROM `zz_group_view` WHERE `id_gruppo` = '.prepare($this->id).' AND `id_vista` IN (SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($id_module).')');

            if (empty($count_views)) {
                $views = $database->fetchArray('SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($id_module));

                foreach ($views as $view) {
                    $database->attach('zz_group_view', ['id_vista' => $view['id']], ['id_gruppo' => $this->id]);
                }
            }

            // Sincronizza i permessi dei segmenti
            $count_segments = $database->fetchNum('SELECT * FROM `zz_group_segment` WHERE `id_gruppo` = '.prepare($this->id).' AND `id_segment` IN (SELECT `id` FROM `zz_segments` WHERE `id_module`='.prepare($id_module).')');

            if (empty($count_segments)) {
                $segments = $database->fetchArray('SELECT `id` FROM `zz_segments` WHERE `id_module`='.prepare($id_module));

                foreach ($segments as $segment) {
                    $database->attach('zz_group_segment', ['id_segment' => $segment['id']], ['id_gruppo' => $this->id]);
                }
            }
        } else {
            // Se i permessi sono stati rimossi, rimuovi anche i permessi su viste e segmenti
            $database->query('DELETE FROM `zz_group_view` WHERE `id_gruppo` = '.prepare($this->id).' AND `id_vista` IN (SELECT `id` FROM `zz_views` WHERE `id_module`='.prepare($id_module).')');
            $database->query('DELETE FROM `zz_group_segment` WHERE `id_gruppo` = '.prepare($this->id).' AND `id_segment` IN (SELECT `id` FROM `zz_segments` WHERE `id_module`='.prepare($id_module).')');
        }
    }
}
