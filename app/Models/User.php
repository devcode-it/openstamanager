<?php

namespace App\Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic;
use Models\Group;
use Models\Log;
use Models\Module;
use Models\Note;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SimpleModelTrait;

    protected $table = 'zz_users';

    protected $is_admin;
    protected $gruppo;
    protected $first_module;

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
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Crea un nuovo utente.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @return User
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

    public function isAdmin()
    {
        return $this->getIsAdminAttribute();
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
        $this->attributes['password'] = Hash::make($value);
    }

    public function getPhotoAttribute()
    {
        if (empty($this->image_file_id)) {
            return null;
        }

        $image = Upload::find($this->image_file_id);

        return base_url().'/'.$image->filepath;
    }

    public function setPhotoAttribute($value)
    {
        $module = \module('Utenti e permessi');

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

    /**
     * Individua il primo modulo accessibile per l'utente.
     * Restituisce null in caso non sia disponibile nessun modulo con i permessi adeguati.
     */
    public function getFirstAvailableModule()
    {
        if (empty($this->first_module)) {
            $modules = $this->isAdmin() ? Module::withoutGlobalScope('permission') : $this->group->modules();

            // Moduli disponibili e navigabili
            $available_modules = $modules
                ->where('options', '!=', '')
                ->where('options', '!=', 'menu')
                ->whereNotNull('options');

            // Modulo indicato nelle Impostazioni
            $first_setting = setting('Prima pagina');
            $setting_module = $available_modules->clone()
                ->where('id', '=', $first_setting)
                ->first();

            // Primo modulo disponibile in assoluto
            $first_module = $available_modules->clone()
                ->first();

            $this->first_module = $setting_module ?: $first_module;
        }

        return $this->first_module;
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

    public function getModules()
    {
        return $this->isAdmin() ? Module::all() : $this->group->modules()->all();
    }
}
