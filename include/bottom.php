<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../core.php';

if (Auth::check()) {
    echo '
                </section><!-- /.content -->
            </div><!-- /.content-wrapper -->

			<footer class="main-footer">
                <a class="hidden-xs" href="'.tr('https://www.openstamanager.com').'" title="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione").'." target="_blank"><strong>'.tr('OpenSTAManager').'</strong></a>
				<span class="pull-right hidden-xs">
                    <strong>'.tr('Versione').'</strong> '.$version.'
                    <small class="text-muted">('.(!empty($revision) ? $revision : tr('In sviluppo')).')</small>
                </span>
			</footer>

            <div id="modals">
            </div>';
}
echo '
        </div><!-- ./wrapper -->';

if (Auth::check()) {
    if (!empty($_SESSION['keep_alive'])) {
        echo '
		<script> setInterval("session_keep_alive()", 5*60*1000); </script>';
    }

    if (App::debug()) {
        echo '
        <!-- Rimozione del messaggio automatico riguardante la modifica di valori nella pagina -->
        <script>
            window.onbeforeunload = null;
        </script>';
    }

    $custom_css = html_entity_decode(setting('CSS Personalizzato'));
    if (!empty($custom_css)) {
        echo '
		<style>'.$custom_css.'</style>';
    }

    // Hooks
    echo '
        <script>
            $(document).ready(function() {
                // Toast
                alertPush();

                // Orologio
                clock();';

    // Hooks
    if (!$config['disable_hooks']) {
        echo '
                    startHooks();';
    }

    // Abilitazione del cron autonoma
    if (!$config['disable_cron']) {
        echo '
                    $.get(globals.rootdir + "/cron.php");';
    }
    echo '
            });
        </script>';
}

echo '
        <script>$(document).ready(init)</script>
	</body>
</html>';

// Retrocompatibilità
if (!empty($id_record) || basename((string) $_SERVER['PHP_SELF']) == 'controller.php' || basename((string) $_SERVER['PHP_SELF']) == 'index.php') {
    unset($_SESSION['infos']);
    unset($_SESSION['errors']);
    unset($_SESSION['warnings']);
}
