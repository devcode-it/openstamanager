<?php

namespace HTMLBuilder\Handler;

/**
 * Intefaccia utilizzata per interagire con la classe HTMLBuilder.
 *
 * @since 2.3
 */
interface HandlerInterface
{
    /**
     * Gestore pubblico, liberamente implementabile per la creazione del codice HTML.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    public function handle(&$values, &$extras);
}
