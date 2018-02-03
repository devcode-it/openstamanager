<?php

namespace HTMLBuilder\Handler;

/**
 * Gestione dell'input di tipo "image".
 *
 * @since 2.3
 */
class MediaHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        // Delega della gestione al metodo specifico per il tipo di input richiesto
        $result = $this->{$values['type']}($values, $extras);

        return $result;
    }

    /**
     * Gestione dell'input di tipo "image".
     * Esempio: {[ "type": "image", "label": "Immagine di test", "name": "image", "class": "img-thumbnail", "value": "image_path" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    public function image(&$values, &$extras)
    {
        unset($values['class'][0]);

        // Valore non imposato
        if (empty($values['value'])) {
            $values['type'] = 'file';

            // Generazione del codice HTML
            return '
    <input |attr|>';
        }

        // Valore presente
        // Visualizzazione dell'immagine e della relativa spunta per la cancellazione
        else {
            $values['class'][] = 'img-thumbnail';
            $values['class'][] = 'img-responsive';

            // Generazione del codice HTML
            return '
    <img src="|value|" |attr|><br>
    <label>
        <input type="checkbox" name="delete_|name|" id="delete_|id|"> '.tr('Elimina').'
    </label>
    <input type="hidden" name="|name|" value="|value|" id="|id|">';
        }
    }
}
