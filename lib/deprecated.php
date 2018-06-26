<?php

// trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

/**
 * Sostituisce ", < e > per evitare hacking del database e risolvere vari problemi.
 *
 * @param unknown $text
 *
 * @deprecated 2.3
 *
 * @return string
 */
function save($text)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $text = htmlentities($text, ENT_QUOTES, 'UTF-8');

    return $text;
}

/**
 * Forza una string a essere un numero decimale (sostituisce , con .).
 *
 * @param unknown $str
 *
 * @deprecated 2.3
 *
 * @return number
 */
function force_decimal($str)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $str = str_replace(',', '.', $str);

    return floatval($str);
}

/**
 * Salva l'ora controllando che non vi siano inseriti contenuti inappropriati.
 *
 * @param unknown $time
 *
 * @deprecated 2.3
 *
 * @return mixed
 */
function saveTime($time)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $result = str_replace([',', '.'], ':', $time);

    // Se è presente solo l'ora, aggiunge minuti e secondi
    if (preg_match('/^([0-9]{1,2})$/', $result)) {
        $result = $result.':00:00';
    }

    return $result;
}

/**
 * @param unknown $datetime
 *
 * @deprecated 2.3
 *
 * @return string
 */
function readDateTime($datetime)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $data = substr($datetime, 0, -9);
    $date = explode('-', $data);
    $date = $date[2].'/'.$date[1].'/'.$date[0];
    $time = substr($datetime, -8);
    $result = $time;
    $result = str_replace(',', ':', $result);
    $result = str_replace('.', ':', $result);
    $time = date('H:i', strtotime($result));
    $datetime = $date.' '.$time;

    return $datetime;
}

/**
 * Converte una stringa da un formato ad un altro
 * "2010-01-15 08:30:00" => "15/01/2010 08:30" (con $view='')
 * "2010-01-15 08:30:00" => "15/01/2010" (con $view='date')
 * "2010-01-15 08:30:00" => "08:30" (con $view='time').
 *
 * @param unknown $datetime
 * @param unknown $view
 *
 * @deprecated 2.3
 *
 * @return string
 */
function readDateTimePrint($datetime, $view)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $data = substr($datetime, 0, -9);
    $date = explode('-', $data);
    $date = $date[2].'/'.$date[1].'/'.$date[0];
    $time = substr($datetime, -8);
    $result = $time;
    $result = str_replace(',', ':', $result);
    $result = str_replace('.', ':', $result);
    $time = date('H:i', strtotime($result));
    $datetime = $date.' '.$time;
    if ($view == 'date') {
        return $date;
    }
    if ($view == 'time') {
        return $time;
    }
}

/**
 * Legge i permessi del modulo selezionato e dell'utente corrente e li ritorna come stringa.
 *
 * @param string $nome_modulo
 *
 * @deprecated 2.3
 *
 * @return string
 */
function get_permessi($nome_modulo)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $dbo = \Database::getConnection();
    $query = 'SELECT *, (SELECT idanagrafica FROM zz_users WHERE id='.prepare($_SESSION['id_utente']).') AS idanagrafica FROM zz_permissions WHERE idgruppo=(SELECT idgruppo FROM zz_users WHERE id='.prepare($_SESSION['id_utente']).') AND idmodule=(SELECT id FROM zz_modules WHERE name='.prepare($nome_modulo).')';
    $rs = $dbo->fetchArray($query);
    if (count($rs) <= 0) {
        // Ultimo tentativo: se non ci sono i permessi ma sono l'amministratore posso comunque leggere il modulo
        if (isAdminAutenticated()) {
            return 'rw';
        } else {
            return '-';
        }
    } else {
        if ($rs[0]['permessi'] == '-') {
            return '-';
        } elseif ($rs[0]['permessi'] == 'r') {
            return 'r';
        } elseif ($rs[0]['permessi'] == 'rw') {
            return 'rw';
        }
    }
}

/**
 * @param unknown $datetime
 *
 * @deprecated 2.3
 *
 * @return string
 */
function saveDateTime($datetime)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $data = substr($datetime, 0, -6);
    $date = explode('/', $data);
    $date = $date[1].'/'.$date[0].'/'.$date[2];
    $date = strtotime($date);
    $date = date('Y-m-d', $date);
    $time = substr($datetime, -5);
    $result = $time;
    $result = str_replace(',', ':', $result);
    $result = str_replace('.', ':', $result);
    $time = date('H:i', strtotime($result));
    $datetime = $date.' '.$time;

    return $datetime;
}

/**
 * Sostituisce gli invii a capo e gli apici singoli con il relativo in HTML.
 *
 * @param unknown $text
 *
 * @deprecated 2.3
 *
 * @return mixed
 */
function fix_str($text)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $text = str_replace("\r\n", '<br/>', $text);
    $text = str_replace("'", '&rsquo;', $text);
    $text = mres($text);

    return $text;
}

/**
 * Funzione di sostituzione a mysql_real_escape_string()/mysql_escape_string().
 *
 * @param string $value
 *
 * @deprecated 2.3
 *
 * @return mixed
 */
function mres($value)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $search = ['\\', "\x00", "\n", "\r", "'", '"', "\x1a"];
    $replace = ['\\\\', '\\0', '\\n', '\\r', "\'", '\"', '\\Z'];

    return str_replace($search, $replace, $value);
}

/**
 * Rimuove eventuali carattari speciali dalla stringa.
 *
 * @param unknown $text
 *
 * @deprecated 2.3
 *
 * @return mixed
 */
function clean($string)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    return preg_replace('/-+/', ' ', $string);
    // Replaces multiple hyphens with single one.
}

/**
 * Trasforma un testo in un nome di id valido per l'html (sostituisce gli spazi).
 *
 * @param unknown $text
 *
 * @deprecated 2.3
 *
 * @return mixed
 */
function makeid($text)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $text = strtolower($text);
    $text = str_replace(' ', '_', $text);

    return $text;
}

/**
 * Sostituisce la sequenza &quot; con " (da HTMLEntities a visualizzabile).
 *
 * @deprecated 2.3
 *
 * @param unknown $text
 */
function read($text)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    return str_replace('&quot;', '"', $text);
}

/**
 * Legge l'ora nel formato hh:mm.
 *
 * @param unknown $time
 *
 * @deprecated 2.3
 *
 * @return string
 */
function readTime($time)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $vtime = explode(':', $time);
    $hour = $vtime[0];
    $minutes = $vtime[1];
    $seconds = $vtime[2];

    return "$hour:$minutes";
}

/**
 * Crea il formato della data modificanto il campo di input (JQuery Datepicker).
 *
 * @deprecated 2.3
 *
 * @param string $data
 */
function saveDate($data)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    if (empty($data)) {
        return '0000-00-00';
    } else {
        $date = explode('/', $data);
        $date = $date[1].'/'.$date[0].'/'.$date[2];
        $date = strtotime($date);

        return date('Y-m-d', $date);
    }
}

/**
 * Crea il formato della data leggendo dal database (?) e modificanto il campo di input (JQuery Datepicker).
 *
 * @deprecated 2.3
 *
 * @param unknown $data
 */
function readDate($data)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $date = $data;
    if ($date != '') {
        $date = explode('-', $date);
        $date = $date[2].'/'.$date[1].'/'.$date[0];
    }

    return $date;
}

/**
 * Genera una porzione di codice html a partire da una stringa nei seguenti formati:
 * campo <input> generico:
 * {[ "type": "text", "required": 1, "value": "$idintervento$" ]}.
 *
 * campo di testo normale e non modificabile
 * {[ "type": "span", "value": "$testo$" ]}
 *
 * {[ "type": "select", "required": 1, "values": "query='SELECT id, descrizione FROM co_contratti WHERE idanagrafica=$idanagrafica$"', "value": "$idcontratto$" ]}
 *
 * Il parametro $records contiene il risultato della query di selezione record per fare i vari replace delle variabili racchiuse tra $$ nel template
 *
 *  @deprecated 2.3
 */
function build_html_element($string)
{
    global $docroot;
    global $records;

    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $dbo = \Database::getConnection();

    preg_match('"value(.+?)\] \}"', $string, $script_value);
    if (count($script_value) != 0) {
        $script_value = $script_value[0];
        $string = str_replace($script_value, 'value": "', $string);
    } else {
        unset($script_value);
    }

    $string = str_replace(['{[', ']}'], ['{', '}'], $string);

    $json = json_decode($string, true);

    // Conversione delle variabili con i campi di database ($records)
    if (!empty($json)) {
        foreach ($json as $key => $value) {
            if ($value == '') {
                unset($json[$key]);
            }
            // Sostituzione delle variabili $nome$ col relativo valore da database
            elseif (preg_match_all('/\$([a-z0-9\_]+)\$/i', $json[$key], $m)) {
                for ($i = 0; $i < sizeof($m[0]); ++$i) {
                    $json[$key] = str_replace($m[0][$i], $records[0][$m[1][$i]], $json[$key]);
                }
            }
        }
    }

    $attributi = [];
    $attributi[] = 'class';

    $valori = [];
    $valori['class'] = [];
    $valori['class'][] = 'form-control';

    if (!empty($json['class'])) {
        $classes = explode(' ', $json['class']);
        foreach ($classes as $class) {
            if ($class != '') {
                $valori['class'][] = $class;
            }
        }
    }

    // Attributi particolari
    if (!empty($json['disabled'])) {
        $attributi[] = 'disabled';
    }
    if (!empty($json['readonly'])) {
        $attributi[] = 'readonly';
    }
    if (!empty($json['required'])) {
        $attributi[] = 'required';
    }
    if (!empty($json['maxlength'])) {
        $attributi[] = 'maxlength';
        $valori['maxlength'] = $json['maxlength'];
    }

    $value = (isset($json['value'])) ? $json['value'] : '';
    $element_id = (!empty($json['id'])) ? $json['id'] : str_replace(['[', ']'], '', $json['name']);

    // Rimuove caratteri indesiderati relativi al nome
    $attributi[] = 'id';
    $valori['id'] = $element_id;
    $attributi[] = 'name';
    $valori['name'] = $json['name'];

    // Label
    if (in_array('required', $attributi)) {
        $json['label'] .= '*';
    }

    $html = '
	<div class="form-group">';

    if (empty($json['no-label'])) {
        $html .= '
		<label for="'.$element_id.'">';
        if (!empty($json['help'])) {
            $html .= '<span class="tip" title="'.$json['help'].'">';
        }
        $html .= $json['label'];
        if (!empty($json['help'])) {
            $html .= '</span>';
        }
        $html .= '</label>';
    }

    if (isset($json['icon-before']) || isset($json['icon-after'])) {
        $html .= '
		<div class="input-group">';
    }

    if (isset($json['icon-before'])) {
        $html .= '
			<span class="input-group-addon">'.$json['icon-before'].'</span>';
    }

    switch ($json['type']) {
        case 'text':
        case 'date':
        case 'password':
            if ($json['type'] == 'date') {
                $attributi[] = 'data-inputmask';
                $valori['data-inputmask'] = "'alias': 'dd/mm/yyyy'";
                $valori['class'][] = 'datepicker';

                if ($value == '0000-00-00 00:00:00' || $value == '0000-00-00') {
                    $value = '';
                } else {
                    $value = date('d/m/Y', strtotime($value));
                }

                if ($value == '01/01/1970') {
                    $value = '';
                }
            }

            $tipo = ($json['type'] == 'password') ? $json['type'] : $tipo = 'text';
            $attributi[] = 'type';
            $valori['type'] = $tipo;

            if (!empty($json['placeholder'])) {
                $attributi[] = 'placeholder';
                $valori['placeholder'] = $json['placeholder'];
            }

            $html .= '
			<input |attr|>';

            break;

        case 'select':
            $values = isset($json['values']) ? $json['values'] : '';

            if (!empty($json['multiple'])) {
                $attributi[] = 'multiple';
            }

            if (!empty($json['ajax-source'])) {
                $valori['class'][] = 'superselectajax';
                $attributi[] = 'data-source';
                $valori['data-source'] = $json['ajax-source'];
            } else {
                $valori['class'][] = 'superselect';
            }

            $placeholder = isset($json['placeholder']) ? $json['placeholder'] : "- Seleziona un'opzione -";
            if (strpos($value, 'Seleziona') !== false) {
                $placeholder = $value;
                $value = '';
            }

            $html .= '
			<select |attr|>';

            if (!empty($json['ajax-source']) && $value != '') {
                $id_elemento = $value;
                $op = $json['ajax-source'];

                ob_start();
                include $docroot.'/ajax_select.php';
                $text = ob_get_clean();

                //Per debug, abilitare
                //echo $text;

                $array = json_decode($text);

                unset($id_elemento);
                unset($op);

                foreach ($array as $el) {
                    $el = (array) $el;
                    if (isset($el['children'])) {
                        foreach ($el['children'] as $child) {
                            $child = (array) $child;

                            $sub_attr = [];
                            if (in_array($child['id'], explode(',', $value)) || ($child['id'] == $value)) {
                                $sub_attr[] = 'selected="true"';
                            }
                            if ($child['_bgcolor_'] != '') {
                                $sub_attr[] = 'style="background:'.$child['_bgcolor_'].'; color:'.color_inverse($child['_bgcolor_'].';"');
                            }

                            // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
                            foreach ($child as $k => $v) {
                                if ($k != 'id' && $k != 'text') {
                                    $sub_attr[] = 'data-'.$k.'="'.$v.'"';
                                }
                            }

                            $html .= '
				<option value="'.$child['id'].'" '.implode(' ', $sub_attr).'>'.$child['text'].'</option>';
                        }
                    } else {
                        $sub_attr = [];
                        if (in_array($el['id'], explode(',', $value)) || ($el['id'] == $value)) {
                            $sub_attr[] = 'selected="true"';
                        }
                        if ($el['_bgcolor_'] != '') {
                            $sub_attr[] = 'style="background:'.$el['_bgcolor_'].'; color:'.color_inverse($el['_bgcolor_'].';"');
                        }

                        // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
                        foreach ($el as $k => $v) {
                            if ($k != 'id' && $k != 'text') {
                                $sub_attr[] = 'data-'.$k.'="'.$v.'"';
                            }
                        }

                        $html .= '
				<option value="'.$el['id'].'" '.implode(' ', $sub_attr).'>'.$el['text'].'</option>';
                    }
                }
            }
            // Generazione <select> da query
            elseif (preg_match_all('/^query=(.+?)$/', $values, $m)) {
                $q = $m[1][0];
                $q = str_replace(['<?php echo ', '; ?>', '?>'], ['".', '."', '."'], $q);
                eval('$query = "'.$q.'";');
                $rs = $dbo->fetchArray($query);

                if (empty($json['multiple'])) {
                    $html .= '
				<option></option>';
                }

                // se non presente, carica eventuale valore predefinito
                if (($value == '0' || $value == '') && isset($json['valore_predefinito']) && $json['valore_predefinito'] != '') {
                    $value = get_var($json['valore_predefinito']);
                }

                $prev = '';
                for ($i = 0; $i < sizeof($rs); ++$i) {
                    if (isset($rs[$i]['optgroup'])) {
                        if ($prev != $rs[$i]['optgroup']) {
                            $html .= '
				<optgroup label="'.$rs[$i]['optgroup'].'"></optgroup>';
                            $prev = $rs[$i]['optgroup'];
                        }
                        $rs[$i]['descrizione'] = '&nbsp;&nbsp;&nbsp;'.$rs[$i]['descrizione'];
                    }
                    $sub_attr = [];
                    if (in_array($rs[$i]['id'], explode(',', $value)) || ($rs[$i]['id'] == $value)) {
                        $sub_attr[] = 'selected="true"';
                    }
                    if (!empty($rs[$i]['_bgcolor_'])) {
                        $sub_attr[] = 'style="background:'.$rs[$i]['_bgcolor_'].'; color:'.color_inverse($rs[$i]['_bgcolor_'].';"');
                    }

                    // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
                    foreach ($rs[$i] as $k => $v) {
                        if ($k != 'id' && $k != 'descrizione' && $k != 'optgroup') {
                            $sub_attr[] = 'data-'.$k.'="'.$v.'"';
                        }
                    }

                    $html .= '
				<option value="'.$rs[$i]['id'].'" '.implode(' ', $sub_attr).'>'.$rs[$i]['descrizione'].'</option>';
                }
            }

            // Generazione <select> da JSON
            // esempio creazione select con opzioni: Maschio, Femmina, Unisex
            // {[ "type": "select", "label": "Sesso", "name": "sesso", "values": "list=\"\": \"\", \"M\": \"Maschio\", \"F\": \"Femmina\", \"U\": \"Unisex\"", "value": "$sesso$" ]}
            elseif (preg_match_all('/^list=(.+?)$/', $values, $m)) {
                $data = json_decode('{'.$m[1][0].'}');
                foreach ($data as $id => $etichetta) {
                    $sub_attr = [];
                    if ($id == $value) {
                        $sub_attr[] = 'selected="true"';
                    }
                    $html .= '
				<option value="'.$id.'" '.implode(' ', $sub_attr).'>'.$etichetta.'</option>';
                }
            }

            $attributi[] = 'data-placeholder';
            $valori['data-placeholder'] = $placeholder;

            $html .= '
			</select>';

            if (in_array('disabled', $attributi) || in_array('readonly', $attributi)) {
                $html .= '
			<script>$("#'.$element_id.'").prop("disabled", true);</script>';
            }

            if (in_array('readonly', $attributi)) {
                $html .= '
				<select class="hide" name="'.$json['name'].'"';
                if (in_array('multiple', $attributi)) {
                    $html .= ' multiple';
                }
                $html .= '>';

                $val = explode(',', $value);
                foreach ($val as $v) {
                    $html .= '
				<option value="'.$v.'" selected="true"></option>';
                }

                $html .= '
			</select>';
            }

            break;

        case 'textarea':
            $html .= '
			<textarea |attr|>'.$value.'</textarea>';
            unset($value);
            break;

        case 'checkbox':
            if ($value == 1) {
                $attributi[] = 'checked';
                $valori['checked'] = 'true';
                $value = 'on';
            }

            if (in_array('readonly', $attributi)) {
                $attributi[] = 'disabled';
            }

            $attributi[] = 'type';
            $valori['type'] = 'checkbox';

            $placeholder = (isset($json['placeholder'])) ? $json['placeholder'] : $json['label'];

            $html .= '
			<div class="input-group">
				<span class="input-group-addon">
					<input |attr|>';
            if (in_array('readonly', $attributi)) {
                $html .= '
					<input type="hidden" name="'.$json['name'].'" value="'.$value.'">';
            }
            $html .= '
				</span>
				<input type="text" class="form-control" placeholder="'.$placeholder.'" disabled>
			</div>';

            unset($valori['class'][0]);
            unset($value);

            break;

        case 'image':
            unset($valori['class'][0]);
            // Form upload
            if ($value == '') {
                $attributi[] = 'type';
                $valori['type'] = 'file';
                $html .= '
			<input |attr|>';
            }
            // Visualizzazione immagine e spunta per cancellazione
            else {
                $valori['class'][] = 'img-thumbnail';
                $valori['class'][] = 'img-responsive';
                $html .= '
			<img src="'.$json['value'].'" class="'.implode(' ', $valori['class']).'" id="img_'.$element_id.'" '.$attr.' '.$json['extra'].'><br>
			<label>
				<input type="checkbox" onclick="if( $(this).is(\':checked\') ){ $(\'#'.$element_id.'\').val(\'deleteme\'); }else{ $(\'#'.$element_id.'\').val( $(\'#prev_'.$element_id.'\').val() ); }">
				'.tr('Elimina').'
			</label>
			<input type="hidden" name="'.$json['name'].'" value="'.$json['value'].'" id="'.$element_id.'">
			<input type="hidden" name="prev_'.$json['name'].'" value="'.$json['value'].'" id="prev_'.$element_id.'">';
            }

            unset($value);

            break;

        default:
            $html .= '	<span |attr|>'.$value."</span>\n";
            break;
    }

    if (isset($json['icon-after'])) {
        $html .= '
		<span class="input-group-addon';
        if (strpos('<button', $json['icon-after']) == 0) {
            $html .= ' no-padding';
        }
        $html .= '">'.$json['icon-after'].'</span>';
    }

    if (isset($json['icon-before']) || isset($json['icon-after'])) {
        $html .= '
		</div>';
    }

    $html .= '
	</div>';

    if (!empty($script_value)) {
        $html .= '
	<script>
		$("#'.$element_id.'").val("'.addslashes($script_value).'");
	</script>';
    }

    if (!empty($json['extra'])) {
        $attributi[] = trim($json['extra']);
    }

    if (!empty($value)) {
        $attributi[] = 'value';
        $valori['value'] = $value;
    }

    $result = [];
    foreach ($attributi as $attributo) {
        $valore = $attributo;
        if (isset($valori[$attributo]) && $valori[$attributo] != '') {
            if (is_array($valori[$attributo])) {
                $valore .= '="'.implode(' ', $valori[$attributo]).'"';
            } else {
                $valore .= '="'.$valori[$attributo].'"';
            }
        }
        $result[] = $valore;
    }

    $html = str_replace('|attr|', implode(' ', $result), $html);

    if (!empty($json['help'])) {
        $html .= '
    <span class="help-block pull-left"><small>'.$json['help'].'</small></span>';
    }

    return $html;
}

/**
 * Legge i plugins collegati al modulo in oggetto e restituisce un array nella forma:
 * $plugins[ 'nome_modulo' ] = '/path/dello/script/script.php';.
 *
 * @deprecated 2.3
 */
function get_plugins($module, $position)
{
    global $plugins;
    global $dbo;
    global $docroot;

    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $q = 'SELECT * FROM zz_plugins WHERE idmodule_to=( SELECT id FROM zz_modules WHERE name="'.$module.'" ) AND position="'.$position.'"';
    $rs = $dbo->fetchArray($q);

    for ($i = 0; $i < sizeof($rs); ++$i) {
        // Lettura modulo di origine
        $q2 = "SELECT parent, directory FROM zz_modules WHERE id='".$rs[$i]['idmodule_from']."' AND `enabled`=1";
        $rs2 = $dbo->fetchArray($q2);
        $module_dir = $rs2[0]['directory'];

        // Se c'è un altro livello sopra, devo mettere come prefisso anche quella directory
        if ($rs2[0]['parent'] != '0') {
            $q3 = "SELECT directory FROM zz_modules WHERE id='".$rs2[0]['parent']."' AND `enabled`=1";
            $rs3 = $dbo->fetchArray($q3);
            $module_dir = $rs3[0]['directory'].'/'.$module_dir;
        }

        if (sizeof($rs2) > 0) {
            $script = $docroot.'/modules/'.$module_dir.'/plugins/'.$rs[$i]['script'];
            $plugins[$rs[$i]['name']] = $script;
        }
    }

    return $plugins;
}

/**
 * Restituisce l'estensione del file.
 *
 * @deprecated 2.3
 *
 * @param string $filename
 */
function estensione_del_file($filename)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    return pathinfo($filename, PATHINFO_EXTENSION);
}
/**
 * @deprecated 2.3
 */
class HTMLHelper
{
    /**
     * Function to read parameter from GET or POST request, escape it and filter it by its rules:
     * string every string
     * int integer value
     * decimal decimal value (force conversion of commas into points: 0,01 become 0.01).
     */
    public function form($param, $method = 'get', $escape = true, $rule = 'text')
    {
        trigger_error(tr('Classe deprecata!'), E_USER_DEPRECATED);

        // method
        if ($method == 'get') {
            $value = $_GET[$param];
        } else {
            $value = $_POST[$param];
        }

        if ($value == 'undefined') {
            $value = '';
        }

        // Rules filter
        if ($rule == 'int') {
            $value = intval($value);
        } elseif ($rule == 'decimal') {
            $value = str_replace(',', '.', $value);
        } elseif ($rule == 'date') {
            if (preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/", $value, $m)) {
                $value = $m[3].'-'.$m[2].'-'.$m[1];
            } elseif (preg_match("/^([0-9]{1})\-([0-9]{2})\-([0-9]{2})$/", $value, $m)) {
                $value = $m[1].'-'.$m[2].'-'.$m[3];
            }
        }

        if ($escape) {
            $value = str_replace(['\\', "\x00", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\'", '\"', '\\Z'], $value);
        }

        return $value;
    }
}

/**
 * Funzione per creare la tabella di visualizzazione file e upload nuovo file
 * $nome_modulo	string		Nome del modulo di cui si sta creando il form e la visualizzazione
 * $url_params		string		Parametri da mettere nell'URL oltre a quelli per l'upload (ad esempio "&idintervento=$idintervento"
 *								per evitare che vengano persi dei parametri per il submit del form
 * $id_record		string		Id esterno, per sapere un determinato file di che record fa parte oltre che di che modulo.
 *
 * @deprecated 2.3
 */
function filelist_and_upload($id_module, $id_record, $label = 'Nuovo allegato:', $showpanel = true)
{
    trigger_error(tr('Procedura deprecata!'), E_USER_DEPRECATED);

    global $docroot;
    global $rootdir;

    $dbo = \Database::getConnection();

    echo '
<a name="attachments"></a>';

    if (!empty($showpanel)) {
        echo '
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Allegati').'</h3>
        </div>
        <div class="panel-body">';
    }

    // Visualizzo l'elenco di file già caricati
    $rs = $dbo->fetchArray('SELECT * FROM zz_files WHERE id_module='.prepare($id_module).' AND id_record='.prepare($id_record));

    if (!empty($rs)) {
        echo '
    <table class="table table-condensed table-hover table-bordered">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th>'.tr('File').'</th>
            <th>'.tr('Data').'</th>
            <th style="width:5%;text-align:center;">#</th>
        </tr>';

        foreach ($rs as $r) {
            echo '
        <tr>
            <td align="left">'.$r['nome'].'</td>
            <td>
                <a href="'.$rootdir.'/files/'.\Modules::get($id_module)['directory'].'/'.$r['filename'].'" target="_blank">'.$r['filename'].'</a>
            </td>
            <td>'.\Translator::timestampToLocale($r['created_at']).'</td>
            <td>
                <a class="btn btn-danger ask" data-backto="record-edit" data-msg="'.tr('Vuoi eliminare questo file?').'" data-op="unlink_file" data-id="'.$r['id'].'" data-filename="'.$r['filename'].'">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
        }

        echo '
    </table>';
    }

    echo '
    <div class="clearfix"></div>
    <br>';

    // Form per l'upload di un nuovo file

    echo '
    <b>'.$label.'</b>
    <div class="row">
        <div class="col-lg-4">
            {[ "type": "text", "placeholder": "'.tr('Nome').'", "name": "nome_allegato", "required": 1 ]}
        </div>

        <div class="col-lg-6">
            {[ "type": "file", "placeholder": "'.tr('Nome').'", "name": "blob", "required": 1 ]}
        </div>

        <div class="col-lg-2 text-right">
            <button type="button" class="btn btn-success" id="upload_button"  onclick="SaveFile();">
                <i class="fa fa-upload"></i> '.tr('Carica').'
            </button>
        </div>
    </div>';

    echo '
    <script>
        function SaveFile(){
            if(!$("#blob").val()){
                alert("Devi selezionare un file con il tasto Sfoglia...");
                return false;
            } else if(!$("input[name=nome_allegato]").val()){
                alert("Devi inserire un nome per il file!");
                return false;
            }

            var file_data = $("#blob").prop("files")[0];
            var form_data = new FormData();
            form_data.append("blob", file_data);
            form_data.append("nome_allegato", $("input[name=nome_allegato]").val());
            form_data.append("op","link_file" );
            form_data.append("id_record","'.$id_record.'");
            form_data.append("id_module", "'.$id_module.'");

            $.ajax({
                url: "'.$rootdir.'/actions.php",
                cache: false,
                type: "post",
                processData: false,
                contentType: false,
                dataType : "html",
                data: form_data,
                success: function(data) {
                    location.href = globals.rootdir + "/editor.php?id_module='.$id_module.'&id_record='.$id_record.'";
                },
                error: function(data) {
                    alert(data);
                }
            })
        }
    </script>';

    if (!empty($showpanel)) {
        echo '
    </div>
</div>';
    }
}

/**
 * Rimuove ricorsivamente una directory.
 *
 * @param unknown $path
 *
 * @deprecated 2.3
 *
 * @return bool
 */
function deltree($path)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $path = realpath($path);

    if (is_dir($path)) {
        $files = scandir($path);
        if (empty($files)) {
            $files = [];
        }

        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                deltree($path.DIRECTORY_SEPARATOR.$file);
            }
        }

        return rmdir($path);
    } elseif (file_exists($path)) {
        return unlink($path);
    }
}

/**
 * Carica gli script JavaScript inclusi nell'array indicato.
 *
 *  @deprecated 2.3
 *
 * @param array $jscript_modules_array
 */
function loadJscriptModules($array)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $result = '';

    foreach ($array as $js) {
        $result .= '
<script type="text/javascript" charset="utf-8" src="'.$js.'"></script>';
    }

    echo $result;
}

/**
 * Carica i file di stile CSS inclusi nell'array indicato.
 *
 * @deprecated 2.3
 *
 * @param array $css_modules_array
 */
function loadCSSModules($array)
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    $result = '';

    foreach ($array as $css) {
        if (is_array($css)) {
            $result .= '
<link rel="stylesheet" type="text/css" media="'.$css['media'].'" href="'.$css['dir'].'"/>';
        } else {
            $result .= '
<link rel="stylesheet" type="text/css" media="screen" href="'.$css.'"/>';
        }
    }

    echo $result;
}

/**
 * Individua il codice successivo.
 *
 * @deprecated 2.4
 *
 * @param string $str
 * @param int    $qty
 * @param string $mask
 */
function get_next_code($str, $qty = 1, $mask = '')
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    return Util\Generator::generate($mask, $str, $qty);
}
