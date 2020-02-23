<?php

namespace Traits;

use Models\Module;
use Models\Plugin;

trait RecordTrait
{
    public abstract function getModuleAttribute();

    public function getModule()
    {
        return !empty($this->module) ? Module::get($this->module) : null;
    }

    public function getPlugin()
    {
        return !empty($this->plugin) ? Plugin::get($this->plugin) : null;
    }

    public function uploads()
    {
        $module = $this->getModule();
        $plugin = $this->getPlugin();

        if (!empty($module)) {
            return $module->uploads($this->id);
        }

        if (!empty($plugin)) {
            return $plugin->uploads($this->id);
        }

        return collect();
    }
}
