<?php

/**
 * Classe per la gestione dei widgets del progetto.
 *
 * @since 2.0
 */
class Widgets
{
    public static $widgets = [];

    /**
     * Funzione addModuleWidgets
     * $id_modulo: modulo in cui ci si trova
     * $location: location all'interno del modulo, per esempio controller oppure inserimento/modifica
     * Prende da database tutti i widget associati al modulo passato come parametro e li aggiunge con createWidget
     * alla pagina.
     */
    public static function addModuleWidgets($id_module, $location)
    {
        if (empty(self::$widgets[$id_module][$location])) {
            $dbo = Database::getConnection();

            // ottengo da db gli id dei widget associati al modulo
            $results = $dbo->fetchArray('SELECT id, location, class FROM zz_widgets WHERE id_module='.prepare($id_module).' AND location='.prepare($location).' AND enabled=1 ORDER BY `order` ASC');

            $result = '';

            if (!empty($results)) {
                $cont = count($results);
                if ($cont > 4 || $cont < 2) {
                    $cont = 4;
                }

                // Aggiungo la riga per bootstrap
                $result = '
			<ul class="row widget" id="widget-'.$location.'" data-class="'.$results[0]['class'].'">';

                // Aggiungo ad uno ad uno tutti i widget
                foreach ($results as $widget) {
                    $result .= self::createWidget($widget['id'], $widget['class'], $cont);
                }

                $result .= '
			</ul>
			<script>
				equalHeight("#widget-'.$location.' .li-widget .box");
			</script>';
            }

            self::$widgets[$id_module][$location] = $result;
        }

        return self::$widgets[$id_module][$location];
    }

    /**
     * Funzione createWidget
     * $id_widget: l'id numerico del widget da creare ed inserire nella pagina
     * Sa seconda del tipo di widget inserisce il codice HTML per la sua creazione nella pagina.
     * Ottiene i dati per la creazione
     * del widget da tabella, in maniera da crearli in maniera dinamica a seconda dei campi.
     */
    protected static function createWidget($id_widget, $class, $totalNumber = 4)
    {
        global $rootdir;

        $dbo = Database::getConnection();

        // ottengo i dati del widget passato come parametro da database
        $results = $dbo->fetchArray("SELECT *, (SELECT name FROM zz_modules WHERE id = zz_widgets.id_module) AS 'module_name' FROM zz_widgets WHERE id=".prepare($id_widget));
        $module_name = $results[0]['module_name'];

        $result = '';
        // a seconda del tipo inserisco il widget in maniera differente
        switch ($results[0]['type']) {
            // widget di tipo statistiche
            case 'print':
            case 'stats':
                $query = $results[0]['query'];

                $additionals = Modules::getAdditionalsQuery($module_name);
                if (!empty($additionals)) {
                    $query = str_replace('1=1', '1=1 '.$additionals, $query);
                }
                $query = str_replace('|period_start|', $_SESSION['period_start'], $query);
                $query = str_replace('|period_end|', $_SESSION['period_end'], $query);

                $dato = '';
                if ($query != '') {
                    $dato = $dbo->fetchArray($query);
                }

                if (!empty($dato)) {
                    $dato = $dato[0]['dato'];

                    // inserisco il widget
                    $result .= '
        <li class="col-xs-12 col-sm-6 col-md-4 col-lg-'.intval(12 / $totalNumber).' li-widget" id="widget_'.$results[0]['id'].'">
            <button type="button" class="close" onclick="if(confirm(\'Disabilitare questo widget?\')) { $.post( \''.$rootdir.'/modules/aggiornamenti/actions.php?id_module='.$results[0]['id_module'].'\', { op: \'disable_widget\', id: \''.$results[0]['id'].'\' }, function(response){ location.href = \''.$rootdir.'/controller.php?id_module='.$results[0]['id_module'].'\';  }); };" ><span aria-hidden="true">&times;</span><span class="sr-only">'._('Chiudi').'</span></button>';
                    if (!empty($results[0]['more_link'])) {
                        $result .= '
            <a class="clickable" ';
                        if ($results[0]['more_link_type'] == 'link') {
                            $result .= 'href="'.$results[0]['more_link'].'"';
                        } elseif ($results[0]['more_link_type'] == 'popup') {
                            $result .= 'data-href="'.$results[0]['more_link'].'" data-toggle="modal" data-title="'.$results[0]['text'].'" data-target="#bs-popup"';
                        } elseif ($results[0]['more_link_type'] == 'javascript') {
                            $link = $results[0]['more_link'];
                            $link = str_replace('|period_start|', $_SESSION['period_start'], $link);
                            $link = str_replace('|period_end|', $_SESSION['period_end'], $link);
                            $result .= 'onclick="'.$link.'"';
                        }
                        $result .= '>';
                    }

                    $result .= '
            <div class="info-box">
                <span class="info-box-icon" style="background-color:'.$results[0]['bgcolor'].'">
                    <i class="'.$results[0]['icon'].'"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">'.$results[0]['text'].'</span>
                    <span class="info-box-number">'.$dato.'</span>
                </div>
            </div>';

                    if (!empty($results[0]['more_link'])) {
                        $result .= '
            </a>';
                    }

                    $result .= '
        </li>';
                }

                break;

                // widget di tipo chart: importa la pagina php specificata nel campo php_include della tabella, non ha l'icona
                case 'chart':
                    $result .= '
						<li class="'.$class.'" id="'.$results[0]['id'].'">
							<!-- small box -->
							<div class="small-box bg-'.$results[0]['bgcolor'].'">
								<div class="inner">';
                    include_once $results[0]['php_include'];
                    $result .= '
								</div>
							</div>
						</li>';
                    break;

                // widget custom con codice php e icona
                case 'custom':
                    $result .= '
						<li class="'.$class.'" id="'.$results[0]['id'].'">
							<!-- small box -->
							<div class="small-box bg-'.$results[0]['bgcolor'].'">
								<div class="inner">';
                    include_once $results[0]['php_include'];
                    $result .= '
								</div>
								<div class="icon">
									<i class="'.$results[0]['icon'].'"></i>
								</div>
							</div>
						</li>';
                    break;
            }

        return $result;
    }
}
