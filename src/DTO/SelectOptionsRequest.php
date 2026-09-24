<?php

namespace DTO;

class SelectOptionsRequest
{
    public ?string $search = null;
    public ?int $page = 0;
    /**
     * @var array<string>|string
     */
    public mixed $retrieve_only_for = [];
}
