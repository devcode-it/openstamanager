<?php

namespace Modules\Emails;

use Carbon\Carbon;
use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Notifications\EmailNotification;
use Traits\StoreTrait;

class Account extends Model
{
    use StoreTrait;
    use SoftDeletes;

    protected $table = 'em_accounts';

    public function testConnection()
    {
        // Impostazione di connected_at a NULL
        $this->connected_at = null;
        $this->save();

        // Creazione email di test
        $mail = new EmailNotification($this->id);
        // Tentativo di connessione
        $result = $mail->testSMTP();

        // Salvataggio della data di connessione per test riuscito
        if ($result) {
            $this->connected_at = Carbon::now();
            $this->save();
        }

        return $result;
    }

    /* Relazioni Eloquent */

    public function templates()
    {
        return $this->hasMany(Template::class, 'id_account');
    }

    public function emails()
    {
        return $this->hasMany(Mail::class, 'id_account');
    }
}
