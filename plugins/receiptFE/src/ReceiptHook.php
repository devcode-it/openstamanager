<?php

namespace Plugins\ReceiptFE;

use Hooks\Manager;
use Models\Cache;

class ReceiptHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        // Lettura cache
        $todo_cache = Cache::get('Ricevute Elettroniche');

        return !$todo_cache->isValid() || !empty($todo_cache->content);
    }

    public function execute()
    {
        // Lettura cache
        $todo_cache = Cache::get('Ricevute Elettroniche');
        $completed_cache = Cache::get('Ricevute Elettroniche importate');

        // Refresh cache
        if (!$todo_cache->isValid()) {
            $list = Interaction::getRemoteList();

            $todo_cache->set($list);
            $completed_cache->set([]);

            return;
        }

        // Caricamento elenco di importazione
        $todo = $todo_cache->content;
        if (empty($todo)) {
            return;
        }

        // Caricamento elenco di ricevute imporate
        $completed = $completed_cache->content;
        $count = count($todo);

        // Esecuzione di 10 imporazioni
        for ($i = 0; $i < 10 && $i < $count; ++$i) {
            $element = $todo[$i];

            // Importazione ricevuta
            $name = $element['name'];
            Interaction::getReceiptList($name);

            try {
                $receipt = new Ricevuta($name);
                $receipt->save();

                $receipt->delete();
                Interaction::processReceipt($name);

                $completed[] = $element;
                unset($todo[$i]);
            } catch (UnexpectedValueException $e) {
            }
        }

        // Aggiornamento cache
        $todo_cache->set($todo);
        $completed_cache->set($completed);
    }

    public function response()
    {
        // Lettura cache
        $todo_cache = Cache::get('Ricevute Elettroniche');
        $completed_cache = Cache::get('Ricevute Elettroniche importate');

        $completed_number = count($completed_cache->content);
        $total_number = $completed_number + count($todo_cache->content);

        // Messaggio di importazione
        $message = tr('Sono state importate _NUM_ ricevute su _TOT_', [
            '_NUM_' => $completed_number,
            '_TOT_' => $total_number,
        ]);

        // Notifica sullo stato dell'importazione
        $notify = $total_number != 0;
        $color = $total_number == $completed_number ? 'success' : 'yellow';

        return [
            'icon' => 'fa fa-ticket text-'.$color,
            'message' => $message,
            'show' => $notify,
        ];
    }
}
