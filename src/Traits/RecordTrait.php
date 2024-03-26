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

namespace Traits;

use Models\Locale;
use Models\Module;
use Models\Plugin;

trait RecordTrait
{
    abstract public function getModuleAttribute();

    public function getModule()
    {
        return !empty($this->module) ? Module::find((new Module())->getByField('name', $this->module)) : null;
    }

    public function getPlugin()
    {
        return !empty($this->plugin) ? Plugin::find((new Plugin())->getByField('name', $this->plugin)) : null;
    }

    /**
     * @param string $name
     */
    public function customField($name)
    {
        $field = database()->table('zz_fields')
            ->leftJoin('zz_field_record', 'zz_fields.id', '=', 'zz_field_record.id_field')
                ->where('zz_fields.name', '=', $name)
                ->where('zz_fields.id_module', '=', $this->getModule()->id)
                ->where('zz_field_record.id_record', '=', $this->id)
            ->first();

        return $field->value;
    }

    public function uploads($record = null)
    {
        $module = $this->getModule();
        $plugin = $this->getPlugin();

        if (!empty($module)) {
            return $module->files($this->id);
        }

        if (!empty($plugin)) {
            return $plugin->files($this->id);
        }

        return collect();
    }

    /**
     * Estensione del salvataggio oggetto per popolare le lingue aggiuntive.
     */
    public function save(array $options = [])
    {
        if ($this->id && method_exists($this, 'getTranslatedFields')) {
            // Lingue aggiuntive disponibili
            $langs = Locale::All()->pluck('id')->toArray();
            $default_lang = Locale::getDefault();
            $other_langs = array_diff($langs, [$default_lang->id]);

            // Popolo inizialmente i campi traducibili o allineo quelli uguali
            foreach ($this->getTranslatedFields() as $field) {
                $value = $this->getTranslation($field);

                foreach ($other_langs as $id_lang) {
                    $translation = database()->table($this->table.'_lang')
                        ->select($field)
                        ->where('id_record', '=', $this->id)
                        ->where('id_lang', '=', $id_lang);

                    // Se la traduzione non è presente la creo...
                    if ($translation->count() == 0) {
                        $this->setTranslation($field, $value, $id_lang);
                    }

                    // ...altrimenti la aggiorno se è uguale (quindi probabilmente non ancora tradotta)
                    /*
                    else{
                        if ($translation->first()->{$field} == $this->{$field}) {
                            $translation->update([
                                $field => $this->{$field},
                            ]);
                        }
                    }
                    */
                }
            }
        }

        parent::save();
    }

    /**
     * Imposta l'attributo all'oggetto.
     */
    public function setTranslation($field, $value, $id_lang = null)
    {
        $id_lang ??= Locale::getDefault()->id;
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->select($field)
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', $id_lang);

        if ($translated->count() > 0) {
            $translated->update([
                $field => $value,
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => $id_lang,
                $field => $value,
            ]);
        }
    }

    /**
     * Legge l'attributo dell'oggetto.
     */
    public function getTranslation($field, $id_lang = null)
    {
        $id_lang ??= Locale::getDefault()->id;

        if (!empty($this)) {
            return database()->table($this->table.'_lang')
            ->select($field)
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', $id_lang)
            ->first()
            ->$field;
        } else {
            return '';
        }

    }

    public function getByField($field, $value, $id_lang = null)
    {
        $id_lang ??= Locale::getDefault()->id;

        if (!empty($this)) {
            return database()->table($this->table.'_lang')
                ->select('id_record')
                ->where($field, '=', $value)
                ->where('id_lang', '=', $id_lang)
                ->first()
                ->id_record;
        } else {
            return '';
        }
    }
}
