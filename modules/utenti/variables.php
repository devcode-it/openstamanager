<?php

$record = \Models\User::find($id_record);

$reset_token = $record->reset_token;

return [
    'username' => $record->username,
    'reset_token' => $reset_token,
    'reset_link' => BASEURL.'/reset.php?reset_token='.$reset_token,
];
