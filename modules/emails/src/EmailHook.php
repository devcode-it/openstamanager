<?php

namespace Modules\Emails;

use Hooks\Manager;
use Models\Mail;
use Models\MailAccount;
use Notifications\EmailNotification;

class EmailHook extends Manager
{
    public function execute()
    {
        $accounts = MailAccount::all();

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

        return count($list);
    }

    public function response($data)
    {
        return $this->prepare();
    }

    public function prepare()
    {
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $user = auth()->getUser();

        $remaining = Mail::whereNull('sent_at')
            ->where('created_by', $user->id)
            ->count();
        $total = Mail::whereDate('sent_at', '>', $yesterday)
            ->orWhereNull('sent_at')
            ->where('created_by', $user->id)
            ->count();
        $current = $total - $remaining;

        $total_remaining = Mail::whereNull('sent_at')->count();

        $message = !empty($remaining) ? tr('Invio email in corso...') : tr('Invio email completato!');

        return [
            'icon' => 'fa fa-envelope text-info',
            'message' => $message,
            'execute' => !empty($total_remaining),
            'show' => true,
            'progress' => [
                'current' => $current,
                'total' => $total,
            ],
        ];
    }
}
