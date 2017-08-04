<?php

namespace HTMLBuilder\Wrapper;

/**
 *
 * @since 2.3
 */
interface WrapperInterface
{
    public function before(&$values, &$extras);

    public function after(&$values, &$extras);
}
