<?php

namespace HTMLBuilder\Handler;

/**
 *
 * @since 2.3
 */
interface HandlerInterface
{
    public function handle(&$values, &$extras);
}
