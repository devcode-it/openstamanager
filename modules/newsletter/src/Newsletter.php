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
use App\Models\User;
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

    public static function build(User $user, Template $template, $name)
    {
        $model = new static();

        $model->user()->associate($user);
        $model->template()->associate($template);
        $model->name = $name;

        $model->subject = $template->subject;
        $model->content = $template->body;

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
        $mails = $this->emails;

        $completed = true;
        foreach ($mails as $mail) {
            if (empty($mail->sent_at)) {
                $completed = false;
                break;
            }
        }

        $this->state = $completed ? 'OK' : $this->state;
        $this->completed_at = $completed ? date('Y-m-d H:i:s') : $this->completed_at;
        $this->save();
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

    /**
     * Metodo per inviare l'email della newsletter a uno specifico destinatario.
     *
     * @return Mail|null
     */
    public function inviaDestinatario(Destinatario $destinatario, $test = false)
    {
        $template = $this->template;
        $uploads = $this->uploads()->pluck('id');

        $origine = $destinatario->getOrigine();

        $anagrafica = $origine instanceof Anagrafica ? $origine : $origine->anagrafica;

        $abilita_newsletter = $origine->enable_newsletter;
        $email = $destinatario->email;
        if (empty($email) || empty($abilita_newsletter) || !v::email()->validate($email)) {
            return null;
        }

        // Inizializzazione email
        $mail = Mail::build(auth()->getUser(), $template, $anagrafica->id);

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
        return $this->belongsToMany(Mail::class, 'em_newsletter_receiver', 'id_newsletter', 'id_email')->withPivot(['record_id', 'record_type']);
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
