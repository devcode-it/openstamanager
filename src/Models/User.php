<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'zz_users';

    protected $appends = [
        'is_admin',
        'gruppo',
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

    public function getIsAdminAttribute()
    {
        if (!isset($this->is_admin)) {
            $this->is_admin = $this->getGruppoAttribute() == 'Amministratori';
        }

        return $this->is_admin;
    }

    public function getGruppoAttribute()
    {
        if (!isset($this->gruppo)) {
            $this->gruppo = $this->group->nome;
        }

        return $this->gruppo;
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
}
