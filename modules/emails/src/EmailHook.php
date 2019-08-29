<?php

namespace Modules\Emails;

use Hooks\Manager;
use Notifications\EmailNotification;

class EmailHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        $diff = date('Y-m-d', strtotime('-4 hours'));
        $failed = function ($query) use ($diff) {
            $query->whereDate('failed_at', '<', $diff)
                ->orWhereNull('failed_at');
        };

        $remaining = Mail::whereNull('sent_at')
            ->where($failed)
            ->count();

        return !empty($remaining);
    }

    public function execute()
    {
        $accounts = Account::all();
        $diff = date('Y-m-d', strtotime('-4 hours'));

        $list = [];
        foreach ($accounts as $account) {
            $mail = Mail::whereNull('sent_at')
                ->where('id_account', $account->id)
                ->whereNull('failed_at')
                ->orWhereDate('failed_at', '<', $diff)
                ->orderBy('created_at')
                ->first();

            if (!empty($mail)) {
                $list[] = $mail;
            }
        }

        foreach ($list as $mail) {
            $email = EmailNotification::build($mail);

            try {
                // Invio mail
                $email->send();
            } catch (PHPMailer\PHPMailer\Exception $e) {
            }
        }

        return $list;
    }

    public function response()
    {
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $user = auth()->getUser();

        $current = Mail::whereDate('sent_at', '>', $yesterday)
            ->where('created_by', $user->id)
            ->count();
        $total = Mail::whereDate('sent_at', '>', $yesterday)
            ->orWhereNull('sent_at')
            ->where('created_by', $user->id)
            ->count();

        $message = $total != $current ? tr('Invio email in corso...') : tr('Invio email completato!');
        $message = empty($total) ? tr('Nessuna email presente...') : $message;

        return [
            'icon' => 'fa fa-envelope text-info',
            'message' => $message,
            'show' => true,
            'progress' => [
                'current' => $current,
                'total' => $total,
            ],
        ];
    }
}
