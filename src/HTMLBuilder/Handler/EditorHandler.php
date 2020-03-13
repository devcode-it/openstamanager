<?php

namespace HTMLBuilder\Handler;

/**
 *  Gestione dell'input di tipo "editor".
 *
 * @since 2.4.2
 */
class EditorHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        $lang = trans()->getCurrentLocale();
        $lang = str_replace('_', '-', $lang);

        // Generazione del codice HTML
        return '
    <textarea |attr|>|value|</textarea>

    <link rel="stylesheet" type="text/css" media="all" href="'.ROOTDIR.'/assets/dist/js/summernote/summernote.css"/>
    <script src="'.ROOTDIR.'/assets/dist/js/summernote/summernote.js"></script>
    <script src="'.ROOTDIR.'/assets/dist/js/summernote/lang/summernote-'.$lang.'.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#'.prepareToField($values['id']).'").summernote({
                lang: "'.$lang.'",
                height: 250,
                placeholder: `'.prepareToField($values['placeholder']).'`,
            });
        });
    </script>';

        unset($values['placeholder']);
    }
}
