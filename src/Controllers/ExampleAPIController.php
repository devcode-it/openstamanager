<?php

namespace Controllers;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

class CustomRequest
{
    public string $id;
    public string $content;
}

#[Post(
    uriTemplate: '/custom',
    processor: ExampleAPIController::class,
    input: CustomRequest::class,
)]
class CustomResponse
{
    public string $data;
}

final class ExampleAPIController extends APIController
{
    public function __invoke(mixed $data): CustomResponse
    {
        if (!$data instanceof CustomRequest) {
            throw new InvalidArgumentException();
        }
       
        $result = new CustomResponse();
        $result->data = $data->content;
        return $result;
    }
}
