<?php

namespace Modules\Utenti\API\v1;

use API\Interfaces\CreateInterface;
use API\Resource;

class Logout extends Resource implements CreateInterface
{
    public function create($request)
    {
        $database = database();
        $user = $this->getUser();

        if (!empty($request['token']) && !empty($user)) {
            // Cancellazione della chiave
            $database->query('DELETE FROM `zz_tokens` WHERE `token` = '.prepare($request['token']).' AND `id_utente` = '.prepare($user['id']));
        } else {
            $response = [
                'status' => API\Response::getStatus()['unauthorized']['code'],
            ];
        }

        return $response;
    }
}
