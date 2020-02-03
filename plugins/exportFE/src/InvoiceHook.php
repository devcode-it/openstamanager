<?php

namespace Plugins\ExportFE;

use Hooks\Manager;
use Modules\Fatture\Fattura;

class InvoiceHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        if (!Interaction::isEnabled()) {
            return false;
        }

        $remaining = Fattura::where('hook_send', 1)
            ->where('codice_stato_fe', 'ERR')
            ->count();

        return !empty($remaining);
    }

    public function execute()
    {
        $fattura = Fattura::where('hook_send', 1)
            ->where('codice_stato_fe', 'QUEUE')
            ->first();

        $result = Interaction::sendInvoice($fattura->id);

        if ($result['code'] == 200) {
            $fattura->hook_send = false;
            $fattura->save();
        }

        return $result;
    }

    public function response()
    {
        $completed = !$this->needsExecution();
        $message = tr('Invio fatture elettroniche in corso...');
        $icon = 'text-info';

        $errors = Fattura::where('hook_send', 1)
            ->where('codice_stato_fe', 'ERR')
            ->count();

        if ($completed) {
            if (empty($errors)) {
                $message = tr('Invio fatture elettroniche completato!');
            } else {
                $message = tr('Invio fatture elettroniche completato con errori');
                $icon = 'text-danger';
            }
        }

        return [
            'icon' => 'fa fa-envelope '.$icon,
            'message' => $message,
            'show' => !$completed || !empty($errors),
        ];
    }
}
