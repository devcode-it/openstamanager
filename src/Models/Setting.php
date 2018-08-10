<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'zz_settings';

    protected $appends = [
        'description',
    ];

    public function getDescriptionAttribute()
    {
        $value = $this->valore;

        // Valore corrispettivo
        $query = str_replace('query=', '', $this->tipo);
        if ($query != $this->tipo) {
            $data = $database->fetchArray($query);
            if (!empty($data)) {
                $value = $data[0]['descrizione'];
            }
        }

        return $value;
    }

    /**
     * Restituisce il valore corrente dell'impostazione ricercata.
     * Se l'impostazione viene cercata più volte, il primo valore individuato viene salvato; per costringere a aggiornare i contenuto, usare l'opzione $again.
     *
     * @param string $nome
     * @param string $section
     * @param string $descrizione
     * @param bool   $again
     *
     * @return string
     */
    public static function get($name, $section = null)
    {
        $find = [
            'nome' => $name,
        ];

        if (!empty($section)) {
            $find['section'] = $section;
        }

        $setting = self::where($find)->first();

        return $setting->valore;
    }
}
