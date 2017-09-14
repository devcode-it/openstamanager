<?php

include_once __DIR__.'/../core.php';

if (Auth::check()) {
    echo '
                    </div><!-- /.row -->
                </section><!-- /.content -->
            </aside><!-- /.content-wrapper -->

			<footer class="main-footer">
				<span class="pull-right hidden-xs">
                    <strong>'.tr('Versione').' '.$version.'</strong>
                    <small class="text-muted">('.(!empty($revision) ? $revision : tr('In sviluppo')).')</small>
                </span>
				'.tr('OpenSTAManager').'
			</footer>

			<div class="modal fade" id="bs-popup" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false"></div>
			<div class="modal fade" id="bs-popup2" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false"></div>';
}
echo '
        </div><!-- ./wrapper -->';

if (Auth::check()) {
    if (!empty($_SESSION['keep_alive'])) {
        echo '
		<script> setInterval("session_keep_alive()", 5*60*1000); </script>';
    }

    $custom_css = get_var('CSS Personalizzato');
    if (!empty($debug)) {
        $custom_css .= 'div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-copy-clipboard:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-database:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-duration:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-memory:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-row-count:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-stmt-id:before {
            font-family: FontAwesome;
        }';
    }

    if (!empty($custom_css)) {
        echo '
		<style>'.$custom_css.'</style>';
    }

    if (!empty($debugbarRenderer)) {
        echo $debugbarRenderer->render();
    }
}

echo '
        <script>
            // Rimozione del messaggio automatico riguardante la modifica di valori nella pagina
            window.onbeforeunload = null;
        </script>
	</body>
</html>';

unset($_SESSION['infos']);
unset($_SESSION['errors']);
unset($_SESSION['warnings']);
