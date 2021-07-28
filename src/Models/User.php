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
use Intervention\Image\ImageManagerStatic;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;

class User extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_users';

    protected $appends = [
        'is_admin',
        'gruppo',
        'nome_completo',
        'id_sede_principale',
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

    public function getIdSedePrincipaleAttribute()
    {
        $sede_principale = $this->sediAbilitate()->first();

        return $sede_principale ? $sede_principale->id : 0;
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

    public function getNomeCompletoAttribute()
    {
        $anagrafica = $this->anagrafica;
        if (empty($anagrafica)) {
            return $this->username;
        }

        return $anagrafica->ragione_sociale.' ('.$this->username.')';
    }

    // Funzioni

    public function setPhoto($upload_data)
    {
        $module = \Modules::get('Utenti e permessi');

        $data = [
            'id_module' => $module->id,
            'id_record' => $this->id,
        ];

        // Foto precedenti
        $old_photo = Upload::where($data)->get();

        // Informazioni sull'immagine
        $filepath = is_array($upload_data) ? $upload_data['tmp_name'] : $upload_data;
        $info = Upload::getInfo(is_array($upload_data) ? $upload_data['name'] : $upload_data);
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

    public function note()
    {
        return $this->hasMany(Note::class, 'id_utente');
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function sediAbilitate()
    {
        return $this->belongsToMany(Sede::class, 'zz_user_sede', 'id_utente', 'id_sede');
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
