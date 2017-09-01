<?php

namespace HTMLBuilder\Wrapper;

/**
 * Interfaccia di base per la gestione della struttura HTML contenente gli input effettivi (generati dai tag di input).
 *
 * @since 2.3
 */
interface WrapperInterface
{
    public function before(&$values, &$extras);

    public function after(&$values, &$extras);
}
