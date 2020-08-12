<?php

namespace HTMLBuilder\Handler;

/**
 *  Gestione dell'input di tipo "ckeditor".
 *
 * @since 2.4.2
 */
class CKEditorHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        // Generazione del codice HTML
        return '
    <textarea |attr|>|value|</textarea>
    <script src="'.ROOTDIR.'/assets/dist/js/ckeditor/ckeditor.js"></script>
    <script>
        CKEDITOR.addCss(".cke_editable img { max-width: 100% !important; height: auto !important; }");

        CKEDITOR.replace("'.prepareToField($values['id']).'", {
            toolbar: globals.ckeditorToolbar,
            language: globals.locale,
            scayt_autoStartup: true,
            scayt_sLang: globals.full_locale,
            disableNativeSpellChecker: false,
        });
    </script>';
    }
}
