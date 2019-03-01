<?php

if (Auth::check()) {
    echo '
                    </div><!-- /.row -->
                </section><!-- /.content -->
            </aside><!-- /.content-wrapper -->

			<footer class="main-footer">
                <a class="hidden-xs" href="https://www.openstamanager.com" title="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione").'." target="_blank"><strong>'.tr('OpenSTAManager').'</strong></a>
				<span class="pull-right hidden-xs">
                    <strong>'.tr('Versione').'</strong> '.$version.'
                    <small class="text-muted">('.(!empty($revision) ? $revision : tr('In sviluppo')).')</small>
                </span>
			</footer>

            <div id="modals">
                <div class="modal fade" id="bs-popup" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true"></div>
                <div class="modal fade" id="bs-popup2" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true"></div>
            </div>';
}
echo '
        </div><!-- ./wrapper -->';

if (Auth::check()) {
    if (!empty($_SESSION['keep_alive'])) {
        echo '
		<script> setInterval("session_keep_alive()", 5*60*1000); </script>';
    }

    if (!empty($debugbar)) {
        echo '
        <!-- Fix per le icone di debug -->
        <style>div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-copy-clipboard:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-database:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-duration:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-memory:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-row-count:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-stmt-id:before {
            font-family: FontAwesome;
        }</style>';

        echo $debugbar->render();
    }

    if (App::debug()) {
        echo '
        <!-- Rimozione del messaggio automatico riguardante la modifica di valori nella pagina -->
        <script>
            window.onbeforeunload = null;
        </script>';
    }

    $custom_css = setting('CSS Personalizzato');
    if (!empty($custom_css)) {
        echo '
		<style>'.$custom_css.'</style>';
    }
}

include App::filepath('resources\views|custom|\layout', 'footer.php');
