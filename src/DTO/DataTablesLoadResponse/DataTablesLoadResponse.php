<?php

declare(strict_types=1);

namespace DTO\DataTablesLoadResponse;

class DataTablesLoadResponse
{
    /**
     * @param array<string, string> $data     To be defined as DTO in the future
     * @param array<string>         $summable
     * @param array<string>         $avg
     */
    public function __construct(
        public int $draw,
        public int $recordsTotal = 0,
        public int $recordsFiltered = 0,
        public array $data = [],
        public array $summable = [], // Custom for OpenSTAManager
        public array $avg = [], // Custom for OpenSTAManager
        public ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'draw' => $this->draw,
            'recordsTotal' => $this->recordsTotal,
            'recordsFiltered' => $this->recordsFiltered,
            'data' => $this->data,
            'summable' => $this->summable,
            'avg' => $this->avg,
            $this->error ? ['error' => $this->error] : [],
        ];
    }
}
