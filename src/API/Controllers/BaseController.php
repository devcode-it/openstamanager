<?php

namespace API\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Models\Module;
use Models\Plugin;

class InvalidInputException extends \Exception
{
    public function __construct(\CuyZ\Valinor\Mapper\MappingError $error)
    {
        $messages = $error->messages();

        $formatted = [];
        foreach ($messages as $message) {
            $formatted[] = str_replace('. for', ' for', $message->withBody('{original_message} for parameter "{node_path}"')->toString());
        }

        parent::__construct('Invalid input: '.implode("\n", $formatted));
    }
}

abstract class BaseController extends Controller
{
    abstract protected function hasAccess($request): bool;

    protected function _auth($request): void
    {
        if (!$this->hasAccess($request)) {
            throw new AuthorizationException();
        }
    }

    protected function hasModuleReadAccess($name): bool
    {
        $structure = $this->getModule($name);

        return $structure && ($structure->permission == 'r' || $structure->permission == 'rw');
    }

    protected function hasModuleWriteAccess($name): bool
    {
        $structure = $this->getModule($name);

        return $structure && $structure->permission == 'rw';
    }

    protected function hasPluginReadAccess($name): bool
    {
        $structure = $this->getPlugin($name);

        return $structure && ($structure->permission == 'r' || $structure->permission == 'rw');
    }

    protected function hasPluginWriteAccess($name): bool
    {
        $structure = $this->getPlugin($name);

        return $structure && $structure->permission == 'rw';
    }

    /**
     * @template T
     *
     * @param class-string<T>|Request $class_reference
     *
     * @return T
     */
    protected function init(Request $request, ?string $class_reference = null): mixed
    {
        $parsed_request = $class_reference ? $this->_cast($request, $class_reference) : $request;
        $this->_auth($request);

        return $parsed_request;
    }

    /**class_reference
     * @template T
     * @param class-string<T> $class_reference
     * @return T
     */
    protected function _cast(Request $request, string $class_reference): mixed
    {
        try {
            return (new \CuyZ\Valinor\MapperBuilder())
                ->allowUndefinedValues()
                ->allowSuperfluousKeys()
                ->allowScalarValueCasting()
                ->mapper()
                ->map(
                    $class_reference,
                    [...$request->route()->parameters(), ...$request->all()]
                );
        } catch (\CuyZ\Valinor\Mapper\MappingError $error) {
            throw new InvalidInputException($error);
        }
    }

    private function getModule($name): ?Module
    {
        if (!Auth::user()) {
            return null;
        }

        $structure = Module::where('name', $name)->first();
        \Modules::setCurrent($structure->id);

        return $structure;
    }

    private function getPlugin($name): ?Plugin
    {
        if (!Auth::user()) {
            return null;
        }

        $structure = Plugin::where('name', $name)->first();
        \Plugins::setCurrent($structure->id);
        \Modules::setCurrent($structure->id_module_from);

        return $structure;
    }
}
