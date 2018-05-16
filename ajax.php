<?php

include_once __DIR__.'/core.php';

switch (get('op')) {
    // Imposta un valore ad un array di $_SESSION
    // esempio: push di un valore in $_SESSION['dashboard']['idtecnici']
    // iversed: specifica se rimuovere dall'array il valore trovato e applicare quindi una deselezione (valori 0 o 1, default 1)
    case 'session_set_array':
        $array = explode(',', get('session'));
        $value = "'".get('value')."'";
        $inversed = get('inversed');

        $found = false;

        // Ricerca valore nell'array
        foreach ($_SESSION[$array[0]][$array[1]] as $idx => $val) {
            // Se il valore esiste lo tolgo
            if ($val == $value) {
                $found = true;

                if ((int) $inversed == 1) {
                    unset($_SESSION[$array[0]][$array[1]][$idx]);
                }
            }
        }

        if (!$found) {
            array_push($_SESSION[$array[0]][$array[1]], $value);
        }

        // print_r($_SESSION[$array[0]][$array[1]]);

        break;

    // Imposta un valore ad una sessione
    case 'session_set':
        $array = explode(',', get('session'));
        $value = get('value');
        $clear = get('clear');

        if ($clear == 1 || $value == '') {
            unset($_SESSION[$array[0]][$array[1]]);
        } else {
            $_SESSION[$array[0]][$array[1]] = $value;
        }

        break;
		
	case 'list_attachments':
		
		 $id_module = get('id_module');
		 $id_record = get('id_record');
		 $id_plugin = get('id_plugin');
		   
		echo 	'{( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "id_plugin": "'.$id_plugin.'", "ajax": "true" )}';
		
		break;
	
}
