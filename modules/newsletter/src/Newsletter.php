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

namespace Modules\Newsletter;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\User;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Modules\Emails\Account;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Respect\Validation\Validator as v;
use Traits\RecordTrait;

class Newsletter extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_newsletters';

    public static function build(?User $user = null, ?Template $template = null, $name = null)
    {
        $model = new static();

        $model->user()->associate($user);
        $model->template()->associate($template);
        $model->name = $name;
        $model->subject = $template->getTranslation('subject');
        $model->content = $template->getTranslation('body');

        $model->state = 'DEV';

        $model->save();

        return $model;
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Newsletter';
    }

    public function fixStato()
    {
        if ($this->state !== 'WAIT') {
            return;
        }

        $maxAttempts = (int) setting('Numero massimo di tentativi');
        if ($maxAttempts < 1) {
            $maxAttempts = 1;
        }

        // Verifica se esistono email associate a questa newsletter che devono essere ancora inviate
        // (email non inviate e con tentativi inferiori alla soglia massima)
        $hasUnsentEmails = $this->emails()
            ->whereNull('sent_at')
            ->where('attempt', '<', $maxAttempts)
            ->exists();

        // Se non ci sono più email da inviare in coda, la newsletter è completata
        if (!$hasUnsentEmails) {
            $this->state = 'OK';
            $lastSent = $this->emails()->max('sent_at');
            $this->completed_at = $lastSent ?: date('Y-m-d H:i:s');
            $this->save();
        }
    }

    public function getNumeroDestinatariSenzaEmail()
    {
        $anagrafiche = $this->getDestinatari(Anagrafica::class)
            ->leftJoin('an_anagrafiche', 'an_anagrafiche.id', '=', 'record_id')
            ->where(function ($query) {
                $query->whereNull('an_anagrafiche.email')
                    ->orWhereRaw("TRIM(an_anagrafiche.email) = ''");
            })
            ->count();

        $sedi = $this->getDestinatari(Sede::class)
            ->leftJoin('an_sedi', 'an_sedi.id', '=', 'record_id')
            ->where(function ($query) {
                $query->whereNull('an_sedi.email')
                    ->orWhereRaw("TRIM(an_sedi.email) = ''");
            })
            ->count();

        $referenti = $this->getDestinatari(Referente::class)
            ->leftJoin('an_referenti', 'an_referenti.id', '=', 'record_id')
            ->where(function ($query) {
                $query->whereNull('an_referenti.email')
                    ->orWhereRaw("TRIM(an_referenti.email) = ''");
            })
            ->count();

        return $anagrafiche + $sedi + $referenti;
    }

    public function getNumeroDestinatariSenzaConsenso()
    {
        $anagrafiche = $this->getDestinatari(Anagrafica::class)
            ->join('an_anagrafiche', 'an_anagrafiche.id', '=', 'record_id')
            ->where(function ($query) {
                $query->whereNull('an_anagrafiche.enable_newsletter')
                    ->orWhere('an_anagrafiche.enable_newsletter', '!=', 1);
            })
            ->count();

        $sedi = $this->getDestinatari(Sede::class)
            ->join('an_sedi', 'an_sedi.id', '=', 'record_id')
            ->leftJoin('an_anagrafiche', 'an_anagrafiche.id', '=', 'an_sedi.id_anagrafica')
            ->where(function ($query) {
                $query->whereNull('an_sedi.enable_newsletter')
                    ->orWhere('an_sedi.enable_newsletter', '!=', 1)
                    ->orWhereNull('an_anagrafiche.enable_newsletter')
                    ->orWhere('an_anagrafiche.enable_newsletter', '!=', 1);
            })
            ->count();

        $referenti = $this->getDestinatari(Referente::class)
            ->join('an_referenti', 'an_referenti.id', '=', 'record_id')
            ->leftJoin('an_anagrafiche', 'an_anagrafiche.id', '=', 'an_referenti.id_anagrafica')
            ->where(function ($query) {
                $query->whereNull('an_referenti.enable_newsletter')
                    ->orWhere('an_referenti.enable_newsletter', '!=', 1)
                    ->orWhereNull('an_anagrafiche.enable_newsletter')
                    ->orWhere('an_anagrafiche.enable_newsletter', '!=', 1);
            })
            ->count();

        return $anagrafiche + $sedi + $referenti;
    }

    public function getDestinatari($tipo)
    {
        return $this->destinatari()
            ->where('record_type', '=', $tipo);
    }

    /**
     * Metodo per inviare l'email della newsletter a uno specifico destinatario.
     *
     * @return Mail|null
     */
    public function inviaDestinatario(Destinatario $destinatario, $test = false, $uploads = null, $template = null, $user = null)
    {
        $template = $template ?: $this->template;
        $uploads = $uploads !== null ? $uploads : $this->uploads()->pluck('id');
        $user = $user ?: auth_osm()->getUser();

        $origine = $destinatario->getOrigine();
        if (empty($origine)) {
            return null;
        }

        $anagrafica = $origine instanceof Anagrafica ? $origine : $origine->anagrafica;
        if (empty($anagrafica)) {
            return null;
        }

        $abilita_newsletter = $origine->enable_newsletter;
        $email = $destinatario->email;
        if (empty($email) || empty($abilita_newsletter) || !v::email()->validate($email)) {
            return null;
        }

        // Inizializzazione email (passando false per reset_from_template per evitare overhead inutile di parsing e stampe/pdf)
        $mail = Mail::build($user, $template, $anagrafica->id, null, false);

        // Completamento informazioni
        $mail->addReceiver($email);
        $mail->subject = ($test ? '[Test] ' : '').$this->subject;
        $mail->content = $this->content;
        $mail->id_newsletter = $this->id;

        // Registrazione allegati
        foreach ($uploads as $upload) {
            $mail->addUpload($upload);
        }

        $mail->save();

        return $mail;
    }

    // Relazione Eloquent

    public function destinatari()
    {
        return $this->hasMany(Destinatario::class, 'id_newsletter');
    }

    public function emails()
    {
        return $this->hasMany(Mail::class, 'id_newsletter');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'id_account');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'id_template');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
