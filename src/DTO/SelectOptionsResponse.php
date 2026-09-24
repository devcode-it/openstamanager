<?php

namespace DTO;

class SelectOptionsResponse
{
    /**
     * @var SelectOptionsRecord[]
     */
    public array $results = [];
    public int $recordsFiltered = 0;
}
