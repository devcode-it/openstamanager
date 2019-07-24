<?php

namespace Common;

use Models\Hook;

abstract class HookManager
{
    abstract public function manage();

    abstract public function response($results);

    public static function update($results)
    {
        $hook = self::getHook();

        $hook->updateCache($results);
    }

    protected static function getHook()
    {
        $class = get_called_class();

        $hook = Hook::where('class', $class)->first();

        return $hook;
    }
}
