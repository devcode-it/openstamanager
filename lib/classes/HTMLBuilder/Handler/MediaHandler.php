<?php

namespace HTMLBuilder\Handler;

/**
 * @since 2.3
 */
class MediaHandler implements HandlerInterface
{
    /**
     * @since 2.3
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    public function handle(&$values, &$extras)
    {
        unset($values['class'][0]);

        // Form upload
        if (empty($values['value'])) {
            $values['type'] = 'file';

            return '
    <input |attr|>';
        } else {
            // Visualizzazione dell'immagine e della relativa spunta per la cancellazione
            $values['class'][] = 'img-thumbnail';
            $values['class'][] = 'img-responsive';

            return '
    <img src="|value|" |attr|><br>
    <label>
        <input type="checkbox" name="delete_|name|" id="delete_|id|"> '._('Elimina').'
    </label>
    <input type="hidden" name="|name|" value="|value|" id="|id|">';
        }
    }
}
