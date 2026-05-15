<?php

namespace API\Controllers;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use DTO\DataTablesLoadRequest\Column;
use DTO\DataTablesLoadRequest\DataTablesLoadRequest;
use DTO\DataTablesLoadResponse\DataTablesLoadResponse;
use Models\Module;
use Util\Query;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Exception;

class InvalidInputException extends Exception {
    public function __construct(\CuyZ\Valinor\Mapper\MappingError $error) {
        $messages = $error->messages();
            
        $formatted = [];
        foreach ($messages as $message) {
            $formatted[] = str_replace(". for", " for", $message->withBody('{original_message} for parameter "{node_path}"')->toString());
        }

        parent::__construct("Invalid input: ".implode("\n", $formatted));
    }
}

abstract class BaseController extends Controller
{
    /**
     * @template T
     * @param class-string<T> $class_reference
     * @return T
     */
    public function _cast(Request $request, string $class_reference): mixed
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
}
