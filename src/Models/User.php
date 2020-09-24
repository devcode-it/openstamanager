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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManagerStatic;
use Modules\Anagrafiche\Anagrafica;

class User extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_users';

    protected $appends = [
        'is_admin',
        'gruppo',
        'id_anagrafica',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $is_admin;
    protected $gruppo;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Crea un nuovo utente.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @return self
     */
    public static function build(Group $gruppo, $username, $email, $password)
    {
        $model = new static();

        $model->group()->associate($gruppo);

        $model->username = $username;
        $model->email = $email;
        $model->password = $password;

        $model->enabled = 1;

        $model->save();

        return $model;
    }

    public function getIsAdminAttribute()
    {
        if (!isset($this->is_admin)) {
            $this->is_admin = $this->getGruppoAttribute() == 'Amministratori';
        }

        return $this->is_admin;
    }

    public function getIdAnagraficaAttribute()
    {
        return $this->attributes['idanagrafica'];
    }

    public function setIdAnagraficaAttribute($value)
    {
        $this->attributes['idanagrafica'] = $value;
    }

    public function getGruppoAttribute()
    {
        if (!isset($this->gruppo)) {
            $this->gruppo = $this->group->nome;
        }

        return $this->gruppo;
    }

    public function getSediAttribute()
    {
        $database = database();

        // Estraggo le sedi dell'utente loggato
        $sedi = $database->fetchArray('SELECT idsede FROM zz_user_sedi WHERE id_user='.prepare($this->id));

        // Se l'utente non ha sedi, è come se ce le avesse tutte disponibili per retrocompatibilità
        if (empty($sedi)) {
            $sedi = $database->fetchArray('SELECT "0" AS idsede UNION SELECT id AS idsede FROM an_sedi WHERE idanagrafica='.prepare($this->idanagrafica));
        }

        return array_column($sedi, 'idsede');
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = \Auth::hashPassword($value);
    }

    public function getPhotoAttribute()
    {
        if (empty($this->image_file_id)) {
            return null;
        }

        $image = Upload::find($this->image_file_id);

        return base_path().'/'.$image->filepath;
    }

    public function setPhotoAttribute($value)
    {
        $module = \Modules::get('Utenti e permessi');

        $data = [
            'id_module' => $module->id,
            'id_record' => $this->id,
        ];

        // Foto precedenti
        $old_photo = Upload::where($data)->get();

        // Informazioni sull'immagine
        $filepath = is_array($value) ? $value['tmp_name'] : $value;
        $info = Upload::getInfo(is_array($value) ? $value['name'] : $value);
        $file = base_dir().'/files/temp_photo.'.$info['extension'];

        // Ridimensionamento
        $driver = extension_loaded('gd') ? 'gd' : 'imagick';
        ImageManagerStatic::configure(['driver' => $driver]);

        $img = ImageManagerStatic::make($filepath)->resize(100, 100, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save(slashes($file));

        // Aggiunta nuova foto
        $upload = Upload::build($file, $data);

        // Rimozione foto precedenti
        delete($file);
        if (!empty($upload)) {
            foreach ($old_photo as $old) {
                $old->delete();
            }
        }

        $this->image_file_id = $upload->id;
    }

    public function getNomeCompletoAttribute()
    {
        $anagrafica = $this->anagrafica;
        if (empty($anagrafica)) {
            return $this->username;
        }

        return $anagrafica->ragione_sociale.' ('.$this->username.')';
    }

    public function getApiTokens()
    {
        $query = 'SELECT * FROM `zz_tokens` WHERE `enabled` = 1 AND `id_utente` = '.prepare($this->id);
        $database = database();

        // Generazione del token per l'utente
        $tokens = $database->fetchArray($query);
        if (empty($tokens)) {
            $token = secure_random_string();

            $database->insert('zz_tokens', [
                'id_utente' => $this->id,
                'token' => $token,
            ]);
        }

        return $database->fetchArray($query);
    }

    /* Relazioni Eloquent */

    public function group()
    {
        return $this->belongsTo(Group::class, 'idgruppo');
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'id_utente');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'id_utente');
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function image()
    {
        return $this->belongsTo(Upload::class, 'image_file_id');
    }

    public function modules()
    {
        return $this->group->modules();
    }
}
