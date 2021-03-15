<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Modules\Emails\Mail;

class EmailHistory extends Component
{
    protected $emails;

    /**
     * Create a new component instance.
     *
     * @param string|int $module
     * @param int $record
     */
    public function __construct(
        $module,
        $record
    ) {

        // Visualizzo il log delle operazioni di invio email
        $this->emails = Mail::whereRaw('id IN (SELECT id_email FROM zz_operations WHERE id_record = '.prepare($record).' AND id_module = '.prepare($module).' AND id_email IS NOT NULL)')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.email-history', [
            'emails' => $this->emails,
        ]);
    }
}
