<?php

include_once __DIR__.'/../core.php';

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
        <!-- Fix per le icone di debug -->
        <style>div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-copy-clipboard:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-database:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-duration:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-memory:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-row-count:before, div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-stmt-id:before {
            font-family: FontAwesome;
        }</style>

        <!-- Rimozione del messaggio automatico riguardante la modifica di valori nella pagina -->
        <script>
            window.onbeforeunload = null;
        </script>';

        echo $debugbarRenderer->render();
    }

    $custom_css = setting('CSS Personalizzato');
    if (!empty($custom_css)) {
        echo '
		<style>'.$custom_css.'</style>';
    }

    // Hooks
    echo '
        <script>
        $(document).ready(function() {
            alertPush();
            
            $.ajax({
                url: globals.rootdir + "/ajax.php",
                type: "get",
                data: {
                    op: "hooks",
                },
                success: function(data) {
                    hooks = JSON.parse(data);
    
                    $("#hooks-header").text(globals.translations.hooksExecuting);
    
                    if (hooks.length == 0) {
                        $("#hooks-loading").hide();
                        $("#hooks-number").text(0);
                        $("#hooks-header").text(globals.translations.hookNone);
                    }
                    
                    hooks.forEach(function(item, index){
                        executeHook(item, hooks.length);
                    });
                },
            });
        });
        
        </script>';

    echo '
        <script>$(document).ready(init)</script>';
}

echo '
	</body>
</html>';

// Retrocompatibilità
if (!empty($id_record) || basename($_SERVER['PHP_SELF']) == 'controller.php' || basename($_SERVER['PHP_SELF']) == 'index.php') {
    unset($_SESSION['infos']);
    unset($_SESSION['errors']);
    unset($_SESSION['warnings']);
}
